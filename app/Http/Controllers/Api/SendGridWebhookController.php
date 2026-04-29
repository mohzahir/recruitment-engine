<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class SendGridWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // SendGrid يرسل الإشعارات على شكل مصفوفة (Array) تحتوي على عدة أحداث
        $events = $request->json()->all();

        foreach ($events as $event) {
            $email = $event['email'] ?? null;
            $eventType = $event['event'] ?? null; // نوع الحدث: open, bounce, delivered...

            if (!$email || !$eventType) continue;

            // البحث عن الشخص في قاعدة بياناتنا
            $contact = Contact::where('email', $email)->first();

            if ($contact) {
                // 1. إذا فتح الرسالة أو ضغط على رابط
                if (in_array($eventType, ['open', 'click'])) {
                    // نحدث الحالة فقط إذا لم يقم بالرد سابقاً أو كان الإيميل خاطئاً
                    if (!in_array($contact->status, ['replied', 'completed', 'bounced'])) {
                        $contact->update(['status' => 'opened']);
                    }
                } 
                // 2. إذا كان الإيميل خاطئاً أو قام بالإبلاغ كـ Spam
                elseif (in_array($eventType, ['bounce', 'dropped', 'spamreport'])) {
                    $contact->update(['status' => 'bounced']);
                    // هذه الحالة مهمة جداً لكي لا يحاول مدير الحملات إرسال رسائل المتابعة (Step 2) لإيميل ميت!
                }

                // تسجيل الحدث في ملفات النظام للمراقبة (يُحفظ في storage/logs/laravel.log)
                Log::info("Webhook Triggered: [{$eventType}] for {$email}");
            }
        }

        // يجب دائماً أن نرد على SendGrid بـ 200 OK لكي يعرفوا أننا استلمنا الإشعار
        return response()->json(['message' => 'Events Processed Successfully'], 200);
    }
}