<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use App\Mail\ColdOutreachMail;
use Exception;
use Illuminate\Support\Facades\Http;

class CampaignSendDripCommand extends Command
{
    protected $signature = 'campaign:send_drip';
    protected $description = 'Send 1 targeted drip email every 10 minutes.';

    public function handle()
    {
        $this->info("🚀 Waking up Drip Sequencer...");

        // 1. درع الحماية (Anti-Spam): لا ترسل لأكثر من شخص في نفس المستشفى في نفس اليوم
        $leadsEmailedToday = Contact::whereDate('last_emailed_at', today())->pluck('lead_id')->toArray();

        // 2. جلب المرشحين المحتملين (تطبيق فلتر الحملات النشطة هنا!)
        $potentialContacts = Contact::whereNotIn('lead_id', $leadsEmailedToday)
            ->whereNotIn('status', ['replied', 'bounced', 'archived', 'completed']) // تجاهل من رد أو تم أرشفته
            ->whereNotNull('campaign_id') // يجب أن يكون موجهاً لحملة
            ->whereHas('campaign', function ($query) {
                // 🔴 هنا تم ربط زر التشغيل والإيقاف! (نأخذ الأشخاص في الحملات النشطة فقط)
                $query->where('status', 'active'); 
            })
            ->orderBy('last_emailed_at', 'asc') // إعطاء الأولوية لمن طال انتظارهم
            ->get();

        $selectedContact = null;
        $nextTemplate = null;

        // 3. البحث عن أول شخص حان وقت إرسال رسالته
        foreach ($potentialContacts as $contact) {
            $nextStepNumber = $contact->current_step + 1;
            
            // جلب القالب (الرسالة) التالي لهذا الشخص
            $template = EmailTemplate::where('campaign_id', $contact->campaign_id)
                                     ->where('step_number', $nextStepNumber)
                                     ->first();

            // إذا لم يتبقَ رسائل في سلسلته، نعتبره أنهى الحملة
            if (!$template) {
                $contact->update(['status' => 'completed']);
                $this->info("Contact {$contact->email} completed their campaign.");
                continue; // انتقل للشخص الذي يليه
            }

            // التحقق من أيام الانتظار (Delay Days) المرنة
            if ($contact->current_step == 0) {
                // أول رسالة تُرسل فوراً
                $selectedContact = $contact;
                $nextTemplate = $template;
                break;
            } else {
                // حساب عدد الأيام التي مرت منذ آخر رسالة أرسلت له
                $daysSinceLastEmail = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($contact->last_emailed_at)->startOfDay());
                
                // إذا مرت الأيام المطلوبة أو تجاوزتها، نعتمده كفائز لرسالة اليوم
                if ($daysSinceLastEmail >= $template->delay_days) {
                    $selectedContact = $contact;
                    $nextTemplate = $template;
                    break;
                }
            }
        }

        // إذا لم نجد أحداً جاهزاً الآن
        if (!$selectedContact) {
            $this->info("💤 No eligible contacts ready for their next step. Going back to sleep.");
            return;
        }

        // 4. السحر الذكي: دمج المتغيرات في الرسالة
        $firstName = explode(' ', $selectedContact->full_name)[0] ?? 'there';
        $companyName = $selectedContact->lead->company ?? 'your organization';

        $subject = str_replace(['{{company_name}}', '{{name}}'], [$companyName, $firstName], $nextTemplate->subject);
        $body = str_replace(['{{company_name}}', '{{name}}'], [$companyName, $firstName], $nextTemplate->body);

        // 🤖 تفعيل الذكاء الاصطناعي
        if (str_contains($body, '{{ai_icebreaker}}')) {
            $this->info("🤖 Generating AI Icebreaker for {$companyName}...");
            $icebreaker = $this->generateIcebreaker($selectedContact);
            $body = str_replace('{{ai_icebreaker}}', $icebreaker, $body);
        }

        // 5. إرسال الإيميل مع الـ CC والمرفقات
        try {
            $this->info("📤 Sending Step {$nextTemplate->step_number} to: {$selectedContact->email}");
            
            // تجهيز الإيميل الأساسي
            $mailJob = Mail::to($selectedContact->email);

            // تفعيل الـ CC إذا كان موجوداً في الحملة
            if (!empty($selectedContact->campaign->cc_emails)) {
                // تقسيم الإيميلات إذا كان هناك أكثر من إيميل (مفصولة بفاصلة)
                $ccList = array_map('trim', explode(',', $selectedContact->campaign->cc_emails));
                $mailJob->cc($ccList);
                $this->info("👥 Added CC: " . implode(', ', $ccList));
            }
            
            // الإرسال مع تمرير بيانات المرفق (ستكون null إذا لم يوجد مرفق)
            $mailJob->send(new ColdOutreachMail(
                $subject, 
                $body, 
                $nextTemplate->attachment_path, 
                $nextTemplate->attachment_name
            ));

            // تحديث ذاكرة النظام
            $selectedContact->update([
                'current_step' => $nextTemplate->step_number,
                'last_emailed_at' => now(),
                'status' => 'emailed'
            ]);

            $this->info("✅ Email sent successfully!");

        } catch (Exception $e) {
            $this->error("❌ Failed to send email: " . $e->getMessage());
        }
    }

    private function generateIcebreaker($contact)
    {
        $apiKey = env('GEMINI_API_KEY');
        $company = $contact->lead->company ?? 'your facility';
        $title = $contact->job_title ?? 'Healthcare Leader';

        if (!$apiKey) return "I hope you are having a productive week at {$company}.";

        $prompt = "You are an expert B2B recruiter. Write a single, highly personalized and professional opening sentence (icebreaker) for a cold email to a '{$title}' at '{$company}'. Focus on acknowledging the importance of their role or complimenting their facility. Keep it under 20 words. Do NOT include greetings like 'Hi' or 'Dear'. Do NOT include sign-offs. Just the sentence itself. Language: English.";

        try {
            $response = Http::withoutVerifying()->timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                return trim(preg_replace('/\s+/', ' ', $text));
            }
        } catch (\Exception $e) {
            return "I noticed the great work your team is doing at {$company}.";
        }

        return "I hope this email finds you well.";
    }
}