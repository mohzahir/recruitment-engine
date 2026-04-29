<div class="p-6 bg-gray-50 min-h-screen flex gap-6" dir="rtl">
    
    <div class="w-1/3 bg-white p-4 rounded-lg shadow h-fit">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">إدارة الحملات</h2>
        </div>
        
        <button wire:click="openCampaignModal" class="w-full mb-4 bg-gray-800 text-white font-bold py-2 rounded-md shadow hover:bg-gray-700 transition flex items-center justify-center gap-2">
            <span>+</span> حملة جديدة
        </button>

        <ul class="space-y-2">
            @forelse($campaigns as $campaign)
                <li wire:click="selectCampaign({{ $campaign->id }})" 
                    class="cursor-pointer p-3 rounded-md transition border-r-4 {{ $activeCampaign && $activeCampaign->id == $campaign->id ? 'bg-blue-50 border-blue-600 shadow-sm' : 'bg-gray-50 border-transparent hover:bg-gray-100 text-gray-700' }}">
                    <div class="flex justify-between items-center">
                        <div class="font-bold text-sm">{{ $campaign->name }}</div>
                        @if($campaign->status == 'active')
                            <span class="w-2 h-2 rounded-full bg-green-500" title="نشطة"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-yellow-500" title="متوقفة"></span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-1 truncate">{{ $campaign->target_role }}</div>
                </li>
            @empty
                <li class="text-center text-gray-500 text-sm py-4">لا توجد حملات، قم بإنشاء واحدة!</li>
            @endforelse
        </ul>
    </div>

    <div class="w-2/3">
        @if(session()->has('message'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded border border-green-200 font-bold">{{ session('message') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200 font-bold">{{ session('error') }}</div>
        @endif

        @if($activeCampaign)
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            {{ $activeCampaign->name }}
                            @if($activeCampaign->status == 'active')
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-bold">نشطة</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full font-bold">متوقفة ⏸️</span>
                            @endif
                        </h2>
                        <p class="text-sm text-gray-500 mt-1 font-mono">الكلمات المفتاحية: {{ $activeCampaign->target_role }}</p>
                    </div>
                    
                    <div class="flex gap-2">
                        <button wire:click="toggleCampaignStatus" class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200">
                            {{ $activeCampaign->status == 'active' ? '⏸️ إيقاف' : '▶️ تشغيل' }}
                        </button>
                        <button wire:click="openCampaignModal({{ $activeCampaign->id }})" class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded hover:bg-blue-200">✏️ تعديل</button>
                        <button wire:click="deleteCampaign" onclick="confirm('هل أنت متأكد من حذف هذه الحملة وجميع رسائلها نهائياً؟') || event.stopImmediatePropagation()" class="px-3 py-1 bg-red-100 text-red-700 text-sm rounded hover:bg-red-200">🗑️ حذف</button>
                    </div>
                </div>

                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🧪</span>
                        <div>
                            <h4 class="text-sm font-bold text-blue-900">تجربة الحملة</h4>
                        </div>
                    </div>
                    <div class="flex flex-1 max-w-md items-center gap-2">
                        <input type="email" wire:model="testEmail" placeholder="أدخل إيميلك للتجربة..." class="flex-1 border-gray-300 rounded-md shadow-sm p-2 text-sm border focus:ring-blue-500 focus:border-blue-500">
                        <button wire:click="sendTestEmail" wire:loading.attr="disabled" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-bold shadow hover:bg-blue-700 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="sendTestEmail">إرسال</span>
                            <span wire:loading wire:target="sendTestEmail">جاري...</span>
                        </button>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-700">سلسلة الرسائل (Sequence)</h3>
                    <button wire:click="openTemplateModal" class="bg-green-600 text-white px-3 py-1 rounded shadow hover:bg-green-700 text-sm">
                        + إضافة رسالة
                    </button>
                </div>

                <div class="relative border-r-2 border-blue-200 pr-6 space-y-8 mt-4">
                    @forelse($templates as $template)
                        <div class="relative bg-gray-50 p-4 rounded-lg border border-gray-200 shadow-sm">
                            <div class="absolute -right-10 top-4 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold border-4 border-white shadow">
                                {{ $template->step_number }}
                            </div>
                            
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="text-md font-bold text-gray-800">
                                        {{ $template->name }}
                                        @if($template->attachment_name) 📎 @endif
                                    </h3>
                                    <p class="text-sm text-gray-500 font-mono mt-1">الموضوع: @{{ $template->subject }}</p>
                                </div>
                                <div class="text-left flex flex-col items-end gap-2">
                                    <span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full font-bold">
                                        @if($template->delay_days == 0) فوري @else بعد {{ $template->delay_days }} أيام @endif
                                    </span>
                                    <div class="flex gap-2">
                                        <button wire:click="openTemplateModal({{ $template->id }})" class="text-blue-500 text-xs hover:underline">تعديل</button>
                                        <button wire:click="deleteTemplate({{ $template->id }})" class="text-red-500 text-xs hover:underline">حذف</button>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded border text-sm text-gray-700 whitespace-pre-line">
                                {!! e($template->body) !!}
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-500 text-center py-6 text-sm">لا توجد رسائل. اضغط على "+ إضافة رسالة" للبدء.</div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="h-full flex items-center justify-center text-gray-400 bg-white border border-gray-200 rounded-lg shadow-sm">
                <p>اختر أو أنشئ حملة للبدء في الإدارة.</p>
            </div>
        @endif
    </div>

    @if($showCampaignModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-1/3 shadow-xl">
                <h3 class="text-xl font-bold mb-4">{{ $editingCampaignId ? '✏️ تعديل الحملة' : '🎯 إنشاء حملة جديدة' }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">اسم الحملة</label>
                        <input type="text" wire:model="c_name" class="mt-1 block w-full border-gray-300 rounded-md p-2 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الكلمات المفتاحية للاستهداف</label>
                        <input type="text" wire:model="c_target_role" class="mt-1 block w-full border-gray-300 rounded-md p-2 border" placeholder="مثال: hr, manager, developer">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">نسخة (CC) تلقائية</label>
                        <input type="text" wire:model="c_cc_emails" class="mt-1 block w-full border-gray-300 rounded-md p-2 border" placeholder="الايميلات مفصولة بفاصلة. مثال: ceo@, sales@">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="resetCampaignForm" class="bg-gray-200 px-4 py-2 rounded font-bold">إلغاء</button>
                    <button wire:click="saveCampaign" class="bg-gray-800 px-4 py-2 rounded text-white font-bold">{{ $editingCampaignId ? 'تحديث' : 'حفظ' }}</button>
                </div>
            </div>
        </div>
    @endif

    @if($showTemplateModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-1/2 shadow-xl">
                <h3 class="text-xl font-bold mb-4">{{ $editingTemplateId ? '✏️ تعديل الرسالة' : '✉️ إضافة رسالة جديدة' }}</h3>
                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium">اسم القالب الداخلي</label>
                            <input type="text" wire:model="t_name" class="mt-1 block w-full border rounded p-2">
                        </div>
                        <div class="w-1/3">
                            <label class="block text-sm font-medium">أيام الانتظار</label>
                            <input type="number" wire:model="t_delay_days" class="mt-1 block w-full border rounded p-2" min="0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">عنوان الإيميل (Subject)</label>
                        <input type="text" wire:model="t_subject" class="mt-1 block w-full border rounded p-2" placeholder="استخدم @{{name}}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">نص الرسالة</label>
                        <textarea wire:model="t_body" rows="6" class="mt-1 block w-full border rounded p-2" placeholder="استخدم @{{ai_icebreaker}} والمزيد..."></textarea>
                    </div>
                    <div class="p-3 bg-gray-50 border rounded-md">
                        <label class="block text-sm font-bold text-gray-700 mb-2">📎 إرفاق ملف (اختياري)</label>
                        <input type="file" wire:model="t_attachment" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <div wire:loading wire:target="t_attachment" class="text-xs text-blue-600 mt-1">جاري رفع الملف...</div>
                        
                        @if($current_attachment_name)
                            <div class="text-xs text-green-600 mt-2 font-bold">الملف الحالي المرفق: {{ $current_attachment_name }}</div>
                        @endif
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="resetTemplateForm" class="bg-gray-200 px-4 py-2 rounded font-bold">إلغاء</button>
                    <button wire:click="saveTemplate" class="bg-blue-600 px-4 py-2 rounded text-white font-bold">{{ $editingTemplateId ? 'تحديث' : 'إضافة للسلسلة' }}</button>
                </div>
            </div>
        </div>
    @endif

</div>