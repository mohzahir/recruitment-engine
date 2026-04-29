<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\Campaign;

class WakeUpDormantContactsCommand extends Command
{
    // اسم الأمر الذي سنشغله
    protected $signature = 'contacts:wakeup';
    protected $description = 'Finds contacts sleeping for 90 days and moves them to the Re-engagement Campaign.';

    public function handle()
    {
        $this->info("جاري البحث عن أصحاب القرار الخاملين لإيقاظهم...");

        // 1. التأكد من وجود "حملة إعادة التواصل" في النظام (أو إنشاؤها إذا لم تكن موجودة)
        $reEngageCampaign = Campaign::firstOrCreate(
            ['name' => '90-Day Re-engagement Outreach'],
            ['target_role' => 'all', 'status' => 'active']
        );

        // 2. جلب الأشخاص الذين انتهت فترة خمولهم (90 يوماً مرت)
        $dormantContacts = Contact::whereNotNull('dormant_until')
                                  ->where('dormant_until', '<=', now())
                                  ->where('status', 'completed') // يجب أن يكون قد أكمل حملته السابقة
                                  ->get();

        if ($dormantContacts->count() === 0) {
            $this->info("لا يوجد أشخاص جاهزين للإيقاظ اليوم. الجميع إما نشطون أو في فترة السبات.");
            return;
        }

        // 3. إيقاظهم وإعادة توجيههم!
        foreach ($dormantContacts as $contact) {
            $contact->update([
                'campaign_id' => $reEngageCampaign->id, // نقله للحملة الجديدة
                'current_step' => 0,          // العودة للخطوة الأولى في الحملة الجديدة
                'status' => 'new',            // تغيير الحالة لـ "جديد" ليبدأ مدير الإرسال باستهدافه مجدداً
                'dormant_until' => null,      // مسح تاريخ الخمول
                'last_emailed_at' => null     // تصفير عداد الأيام
            ]);
            
            $this->line("<fg=green>تم إيقاظ {$contact->email} ونقله لحملة (إعادة التواصل).</>");
        }

        $this->info("نجاح: تم إعادة تدوير {$dormantContacts->count()} مدير مستشفى ووضعهم في مسار الإرسال!");
    }
}