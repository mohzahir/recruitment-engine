<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Campaign;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use App\Mail\ColdOutreachMail;
use Illuminate\Support\Facades\Http;
use Livewire\WithFileUploads; // أضف هذا السطر

class CampaignManager extends Component
{
    use WithFileUploads; // أضف هذا السطر
    
    public $campaigns;
    public $activeCampaign = null;
    public $templates = [];
    public $testEmail = '';

    // متغيرات إضافة/تعديل رسالة (قالب)
    public $showTemplateModal = false;
    public $editingTemplateId = null; // لتحديد ما إذا كنا في وضع التعديل
    public $t_name, $t_subject, $t_body, $t_delay_days;

    // متغيرات إضافة/تعديل حملة
    public $showCampaignModal = false;
    public $editingCampaignId = null; // لتحديد ما إذا كنا في وضع التعديل
    public $c_name, $c_target_role;

    // أضف هذه المتغيرات الجديدة
    public $c_cc_emails;
    public $t_attachment; // لاستقبال الملف المرفوع
    public $current_attachment_name; // لعرض اسم الملف الحالي عند التعديل

    public function mount()
    {
        $this->loadCampaigns();
    }

    public function loadCampaigns()
    {
        $this->campaigns = Campaign::all();
        if ($this->campaigns->count() > 0 && !$this->activeCampaign) {
            $this->selectCampaign($this->campaigns->first()->id);
        } elseif ($this->campaigns->count() == 0) {
            $this->activeCampaign = null;
            $this->templates = [];
        }
    }

    public function selectCampaign($id)
    {
        $this->activeCampaign = Campaign::find($id);
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        if ($this->activeCampaign) {
            $this->templates = EmailTemplate::where('campaign_id', $this->activeCampaign->id)
                                            ->orderBy('step_number', 'asc')
                                            ->get();
        }
    }

    // ==========================================
    // إدارة الحملات (Campaign Management)
    // ==========================================

    public function openCampaignModal($id = null)
    {
        $this->resetCampaignForm();
        if ($id) {
            $campaign = Campaign::find($id);
            $this->editingCampaignId = $campaign->id;
            $this->c_name = $campaign->name;
            $this->c_target_role = $campaign->target_role;
            $this->c_cc_emails = $campaign->cc_emails; // أضف هذا
        }
        $this->showCampaignModal = true;
    }

    public function saveCampaign()
    {
        $this->validate([
            'c_name' => 'required|string|max:255',
            'c_target_role' => 'nullable|string|max:255',
            'c_cc_emails' => 'nullable|string', // أضف هذا
        ]);

        if ($this->editingCampaignId) {
            // تحديث حملة موجودة
            $campaign = Campaign::find($this->editingCampaignId);
            $campaign->update([
                'name' => $this->c_name,
                'target_role' => $this->c_target_role ?? 'general',
                'cc_emails' => $this->c_cc_emails,
            ]);
            session()->flash('message', 'تم تحديث بيانات الحملة بنجاح!');
        } else {
            // إنشاء حملة جديدة
            $newCampaign = Campaign::create([
                'name' => $this->c_name,
                'target_role' => $this->c_target_role ?? 'general',
                'status' => 'active',
                'cc_emails' => $this->c_cc_emails,
            ]);
            $this->selectCampaign($newCampaign->id);
            session()->flash('message', 'تم إنشاء الحملة بنجاح!');
        }

        $this->resetCampaignForm();
        $this->loadCampaigns();
    }

    public function toggleCampaignStatus()
    {
        if ($this->activeCampaign) {
            $newStatus = $this->activeCampaign->status === 'active' ? 'paused' : 'active';
            $this->activeCampaign->update(['status' => $newStatus]);
            $this->loadCampaigns(); // تحديث القائمة
            session()->flash('message', "تم تغيير حالة الحملة إلى: " . ($newStatus == 'active' ? 'نشطة' : 'متوقفة'));
        }
    }

    public function deleteCampaign()
    {
        if ($this->activeCampaign) {
            // حذف جميع الرسائل المرتبطة أولاً
            EmailTemplate::where('campaign_id', $this->activeCampaign->id)->delete();
            // حذف الحملة
            $this->activeCampaign->delete();
            
            $this->activeCampaign = null;
            $this->loadCampaigns();
            session()->flash('message', 'تم حذف الحملة وجميع رسائلها نهائياً.');
        }
    }

    public function resetCampaignForm()
    {
        $this->c_name = ''; $this->c_target_role = ''; $this->editingCampaignId = null;
        $this->showCampaignModal = false;
    }

    // ==========================================
    // إدارة الرسائل (Template Management)
    // ==========================================

    public function openTemplateModal($id = null)
    {
        $this->resetTemplateForm();
        if ($id) {
            $template = EmailTemplate::find($id);
            $this->editingTemplateId = $template->id;
            $this->t_name = $template->name;
            $this->t_subject = $template->subject;
            $this->t_body = $template->body;
            $this->t_delay_days = $template->delay_days;
            $this->current_attachment_name = $template->attachment_name; // أضف هذا
        }
        $this->showTemplateModal = true;
    }

    public function saveTemplate()
    {
        $this->validate([
            't_name' => 'required|string',
            't_subject' => 'required|string',
            't_body' => 'required|string',
            't_delay_days' => 'required|integer|min:0',
            't_attachment' => 'nullable|file|max:10240', // حد أقصى 10 ميجا
        ]);

        $attachmentPath = null;
        $attachmentName = null;

        // إذا قام برفع ملف جديد
        if ($this->t_attachment) {
            $attachmentPath = $this->t_attachment->store('campaign_attachments', 'public');
            $attachmentName = $this->t_attachment->getClientOriginalName();
        }

        if ($this->editingTemplateId) {
            $template = EmailTemplate::find($this->editingTemplateId);
            $updateData = [
                'name' => $this->t_name,
                'subject' => $this->t_subject,
                'body' => $this->t_body,
                'delay_days' => $this->t_delay_days,
            ];
            
            // تحديث المرفق فقط إذا رفع ملفاً جديداً
            if ($attachmentPath) {
                $updateData['attachment_path'] = $attachmentPath;
                $updateData['attachment_name'] = $attachmentName;
            }

            $template->update($updateData);
            session()->flash('message', 'تم تحديث الرسالة بنجاح!');
        } else {
            $nextStep = $this->templates->count() + 1;
            EmailTemplate::create([
                'campaign_id' => $this->activeCampaign->id,
                'name' => $this->t_name,
                'subject' => $this->t_subject,
                'body' => $this->t_body,
                'step_number' => $nextStep,
                'delay_days' => $this->t_delay_days,
                'attachment_path' => $attachmentPath, // أضف هذا
                'attachment_name' => $attachmentName, // أضف هذا
            ]);
            session()->flash('message', 'تمت إضافة الرسالة للسلسلة بنجاح!');
        }

        $this->resetTemplateForm();
        $this->loadTemplates();
    }

    public function deleteTemplate($id)
    {
        EmailTemplate::find($id)->delete();
        
        // إعادة ترتيب الخطوات
        $remainingTemplates = EmailTemplate::where('campaign_id', $this->activeCampaign->id)->orderBy('step_number')->get();
        foreach ($remainingTemplates as $index => $template) {
            $template->update(['step_number' => $index + 1]);
        }
        $this->loadTemplates();
    }

    public function resetTemplateForm()
    {
        $this->t_name = ''; $this->t_subject = ''; $this->t_body = ''; $this->t_delay_days = 0;
        $this->editingTemplateId = null;
        $this->showTemplateModal = false;
        $this->t_attachment = null;
        $this->current_attachment_name = null;
    }

    // ==========================================
    // الإرسال التجريبي والذكاء الاصطناعي
    // ==========================================
    public function sendTestEmail()
    {
        $this->validate(['testEmail' => 'required|email']);
        if (!$this->activeCampaign) return;

        $template = EmailTemplate::where('campaign_id', $this->activeCampaign->id)->orderBy('step_number', 'asc')->first();
        if (!$template) {
            session()->flash('error', 'لا توجد رسائل لتجربتها.'); return;
        }

        $companyName = 'مستشفى الأمل (تجربة)';
        $firstName = 'أحمد';

        $subject = str_replace(['{{company_name}}', '{{name}}'], [$companyName, $firstName], $template->subject);
        $body = str_replace(['{{company_name}}', '{{name}}'], [$companyName, $firstName], $template->body);

        if (str_contains($body, '{{ai_icebreaker}}')) {
            $icebreaker = $this->generateTestIcebreaker($companyName, 'HR Manager');
            $body = str_replace('{{ai_icebreaker}}', $icebreaker, $body);
        }

        try {
            Mail::to($this->testEmail)->send(new ColdOutreachMail($subject, $body));
            $this->testEmail = '';
            session()->flash('message', 'تم الإرسال التجريبي بنجاح!');
        } catch (\Exception $e) {
            session()->flash('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    private function generateTestIcebreaker($company, $title)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) return "I hope you're having a great week at {$company}.";

        $prompt = "Write a professional 1-sentence opening icebreaker for a cold email to a '{$title}' at '{$company}'. Focus on their professional impact. No greetings. Max 20 words. Language: English.";

        try {
            $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            if ($response->successful()) {
                return trim($response->json('candidates.0.content.parts.0.text'));
            }
        } catch (\Exception $e) {}

        return "I noticed the impressive work being done at {$company}.";
    }

    public function render()
    {
        return view('livewire.campaign-manager');
    }
}