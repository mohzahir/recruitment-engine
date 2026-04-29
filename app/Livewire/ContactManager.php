<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;
use App\Models\Campaign;
use App\Models\Lead;

class ContactManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // متغيرات البحث والفلترة
    public $search = '';
    public $filterCampaign = ''; // فلترة حسب الحملة

    // ==========================================
    // متغيرات التحديد الجماعي (Bulk Actions)
    // ==========================================
    public $selectedContacts = []; // مصفوفة لتخزين IDs الأشخاص المحددين
    public $selectAll = false;     // زر تحديد الكل في الصفحة الحالية
    public $bulkCampaignId = '';   // الحملة المراد التوجيه إليها

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCampaign() { $this->resetPage(); }

    // دالة لتحديد/إلغاء تحديد جميع الأشخاص في الصفحة الحالية
    public function updatedSelectAll($value)
    {
        if ($value) {
            // إذا ضغط "تحديد الكل"، نجلب IDs الأشخاص المعروضين حالياً
            $this->selectedContacts = $this->getContactsQuery()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedContacts = [];
        }
    }

    // إذا قام بإلغاء تحديد شخص واحد، نلغي علامة "تحديد الكل"
    public function updatedSelectedContacts()
    {
        $this->selectAll = false;
    }

    // ==========================================
    // دالة الإجراء الجماعي (توجيه للحملات)
    // ==========================================
    public function assignBulkCampaign()
    {
        $this->validate([
            'bulkCampaignId' => 'required',
            'selectedContacts' => 'required|array|min:1'
        ], [
            'bulkCampaignId.required' => 'يجب اختيار الحملة أولاً.',
            'selectedContacts.required' => 'يجب تحديد شخص واحد على الأقل.'
        ]);

        // جملة سحرية واحدة تحدث جميع الأشخاص المحددين
        Contact::whereIn('id', $this->selectedContacts)->update([
            'campaign_id' => $this->bulkCampaignId
        ]);

        $count = count($this->selectedContacts);
        
        // إعادة تعيين المتغيرات بعد النجاح
        $this->selectedContacts = [];
        $this->selectAll = false;
        $this->bulkCampaignId = '';

        session()->flash('message', "تم توجيه {$count} صانع قرار للحملة بنجاح!");
    }

    // دالة مساعدة للحصول على الاستعلام (لمنع تكرار الكود)
    private function getContactsQuery()
    {
        return Contact::with(['lead', 'campaign'])
            ->when($this->search, function($query) {
                $query->where('full_name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('job_title', 'like', '%' . $this->search . '%')
                      ->orWhereHas('lead', function($q) {
                          $q->where('company', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->filterCampaign, function($query) {
                if ($this->filterCampaign == 'unassigned') {
                    $query->whereNull('campaign_id');
                } else {
                    $query->where('campaign_id', $this->filterCampaign);
                }
            })
            ->orderBy('created_at', 'desc');
    }

    public function render()
    {
        $contacts = $this->getContactsQuery()->paginate(20);
        $campaigns = Campaign::where('status', 'active')->get();

        return view('livewire.contact-manager', compact('contacts', 'campaigns'));
    }
}