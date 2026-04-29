<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;

class InboxManager extends Component
{
    use WithPagination;

    public $selectedContactId = null;
    protected $paginationTheme = 'tailwind';

    // تحديث البيانات تلقائياً لمعرفة من فتح الإيميل في الوقت الفعلي
    public function render()
    {
        // جلب الأشخاص الذين تفاعلوا (ردوا أو فتحوا الرسالة)
        $hotLeads = Contact::with(['lead', 'campaign'])
            ->whereIn('status', ['replied', 'opened'])
            ->orderByRaw("FIELD(status, 'replied', 'opened')") // ترتيب الردود أولاً
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        $selectedContact = $this->selectedContactId 
            ? Contact::with(['lead', 'campaign'])->find($this->selectedContactId) 
            : null;

        return view('livewire.inbox-manager', [
            'hotLeads' => $hotLeads,
            'selectedContact' => $selectedContact
        ]);
    }

    public function selectContact($id)
    {
        $this->selectedContactId = $id;
    }

    // إغلاق الصفقة (العميل وافق على التعاون)
    public function markAsWon()
    {
        if ($this->selectedContactId) {
            $contact = Contact::find($this->selectedContactId);
            $contact->update(['status' => 'deal_won']);
            $this->selectedContactId = null;
            session()->flash('message', 'مبروك! تم تسجيل الصفقة بنجاح ونقل العميل لقائمة العملاء.');
        }
    }

    // تجاهل (العميل غير مهتم)
    public function markAsArchived()
    {
        if ($this->selectedContactId) {
            $contact = Contact::find($this->selectedContactId);
            $contact->update(['status' => 'archived']);
            $this->selectedContactId = null;
            session()->flash('message', 'تم أرشفة جهة الاتصال وإيقاف الحملات عنها.');
        }
    }
}