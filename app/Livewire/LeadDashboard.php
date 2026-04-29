<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Campaign;

class LeadDashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function render()
    {
        // حساب إحصائيات الاستخراج الشاملة
        $stats = [
            'total_leads' => Lead::count(), // إجمالي الشركات/المستشفيات
            'total_contacts' => Contact::count(), // إجمالي أصحاب القرار
            'new_contacts_today' => Contact::whereDate('created_at', today())->count(), // حصيلة اليوم
            'active_campaigns' => Campaign::where('status', 'active')->count(), // الحملات التي تعمل
        ];

        // جلب أحدث أصحاب القرار لرؤية كيف قام الرادار الذكي بتوجيههم
        $recentContacts = Contact::with(['lead', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.lead-dashboard', compact('stats', 'recentContacts'));
    }
}