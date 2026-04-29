<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;
use App\Models\Campaign;
use Illuminate\Support\Facades\Artisan;

class CampaignDashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // متغير لتخزين الحملة المحددة في الفلتر (فارغ يعني عرض الكل)
    public $selectedCampaignId = '';

    // تصفير الصفحة عند تغيير الفلتر
    public function updatingSelectedCampaignId()
    {
        $this->resetPage();
    }

    public function triggerCampaignManually()
    {
        Artisan::call('campaign:send_drip');
        session()->flash('message', 'نجاح: ' . Artisan::output());
    }

    public function render()
    {
        // جلب كل الحملات لاستخدامها في قائمة الفلتر
        $campaigns = Campaign::all();

        // بناء الاستعلام الأساسي
        $query = Contact::query();

        // إذا تم اختيار حملة معينة، نطبق الفلتر
        if ($this->selectedCampaignId !== '') {
            $query->where('campaign_id', $this->selectedCampaignId);
        }

        // حساب الإحصائيات (نستخدم clone لكي لا نؤثر على الاستعلام الأساسي للجدول)
        // أضفنا 'completed' لأن من أنهى الحملة تم الإرسال له أيضاً
        $stats = [
            'total_sent' => (clone $query)->whereIn('status', ['emailed', 'opened', 'replied', 'completed'])->count(),
            'opened'     => (clone $query)->where('status', 'opened')->count(),
            'replied'    => (clone $query)->where('status', 'replied')->count(),
            'bounced'    => (clone $query)->where('status', 'bounced')->count(),
        ];

        $stats['open_rate'] = $stats['total_sent'] > 0 
            ? round(($stats['opened'] / $stats['total_sent']) * 100) 
            : 0;

        // جلب أحدث جهات الاتصال مع علاقاتها (Lead و Campaign)
        $contacts = (clone $query)->with(['lead', 'campaign'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return view('livewire.campaign-dashboard', compact('stats', 'contacts', 'campaigns'));
    }
}