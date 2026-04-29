<div class="p-6 bg-gray-50 min-h-screen flex gap-6" dir="rtl" wire:poll.15s>
    
    <div class="w-1/3 bg-white border border-gray-200 rounded-lg shadow-sm h-[80vh] overflow-y-auto">
        <div class="sticky top-0 bg-white p-4 border-b z-10">
            <h2 class="text-lg font-bold text-gray-800">صندوق العملاء المحتملين</h2>
            <p class="text-xs text-gray-500 mt-1">يتم التحديث تلقائياً...</p>
        </div>
        
        <ul class="divide-y divide-gray-100">
            @forelse($hotLeads as $lead)
                <li wire:click="selectContact({{ $lead->id }})" 
                    class="p-4 cursor-pointer hover:bg-blue-50 transition {{ $selectedContactId == $lead->id ? 'bg-blue-50 border-r-4 border-blue-600' : '' }}">
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-semibold text-sm text-gray-800">{{ $lead->full_name }}</span>
                        @if($lead->status == 'replied')
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-bold flex items-center gap-1">
                                💬 قام بالرد
                            </span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full flex items-center gap-1">
                                👁️ تم الفتح
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">{{ $lead->job_title }}</div>
                    <div class="text-xs text-blue-600 mt-2 truncate font-mono">
                        🏢 {{ $lead->lead->company ?? 'غير محدد' }}
                    </div>
                </li>
            @empty
                <li class="p-6 text-center text-gray-500 text-sm">
                    لا يوجد تفاعلات حالياً. النظام يعمل في الخلفية لجلب العملاء!
                </li>
            @endforelse
        </ul>
        <div class="p-4 border-t">
            {{ $hotLeads->links() }}
        </div>
    </div>

    <div class="w-2/3">
        @if(session()->has('message'))
            <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg shadow-sm font-semibold">
                {{ session('message') }}
            </div>
        @endif

        @if($selectedContact)
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold">{{ $selectedContact->full_name }}</h2>
                            <p class="text-blue-100 mt-1">{{ $selectedContact->job_title }} | {{ $selectedContact->lead->company ?? 'غير محدد' }}</p>
                        </div>
                        <div class="text-left text-sm text-blue-100 font-mono">
                            {{ $selectedContact->email }}<br>
                            {{ $selectedContact->phone_number ?? 'لا يوجد رقم' }}
                        </div>
                    </div>
                </div>

                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-gray-700 font-bold mb-4">مسار الحملة الحالية:</h3>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-3 py-1 bg-gray-100 rounded text-gray-600">حملة: {{ $selectedContact->campaign->name ?? 'غير محدد' }}</span>
                        <span>➔</span>
                        <span class="px-3 py-1 bg-gray-100 rounded text-gray-600">وصل للخطوة: {{ $selectedContact->current_step }}</span>
                        <span>➔</span>
                        @if($selectedContact->status == 'replied')
                            <span class="px-3 py-1 bg-green-100 text-green-700 font-bold rounded shadow-sm">تلقينا رداً!</span>
                        @else
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 font-bold rounded shadow-sm">فتح الإيميل</span>
                        @endif
                    </div>
                    
                    @if($selectedContact->status == 'replied')
                        <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-md text-gray-600 text-sm">
                            <p class="font-semibold text-gray-800 mb-2">💡 تنبيه من النظام:</p>
                            هذا العميل قام بالرد على رسالتك. يرجى مراجعة بريدك الإلكتروني الفعلي (<span class="font-mono">{{ env('MAIL_FROM_ADDRESS') }}</span>) لقراءة نص رسالته والرد عليه بشرياً، ثم حدد الإجراء المناسب بالأسفل.
                        </div>
                    @endif
                </div>

                <div class="p-6 bg-gray-50 flex gap-4">
                    <button wire:click="markAsWon" class="flex-1 bg-green-600 text-white font-bold py-3 px-4 rounded-lg shadow hover:bg-green-700 transition">
                        ✅ إغلاق الصفقة (Deal Won)
                    </button>
                    <button wire:click="markAsArchived" class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 px-4 rounded-lg shadow hover:bg-gray-300 transition">
                        🚫 غير مهتم / أرشفة
                    </button>
                </div>
            </div>
        @else
            <div class="h-full flex flex-col items-center justify-center text-gray-400 bg-white border border-gray-200 rounded-lg shadow-sm">
                <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.5M6 16h2.5M18 20V10m0 10l-3-3m3 3l3-3"></path></svg>
                <p class="text-lg">اختر جهة اتصال من القائمة لعرض التفاصيل واتخاذ قرار.</p>
            </div>
        @endif
    </div>
</div>