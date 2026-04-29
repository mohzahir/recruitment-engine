<div class="p-6 bg-gray-50 min-h-screen relative pb-24" dir="rtl">
    
    @if(session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg border border-green-200 font-bold shadow-sm">
            ✅ {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">إدارة جهات الاتصال 👥</h2>
            <p class="text-sm text-gray-500 mt-1">حدد الأشخاص وقم بتوجيههم للحملات المناسبة</p>
        </div>
        
        <div class="flex flex-wrap w-full md:w-auto gap-3 items-center">
            <select wire:model.live="filterCampaign" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 bg-white font-bold cursor-pointer">
                <option value="">جميع الأشخاص</option>
                <option value="unassigned">⚠️ غير موجهين لحملة (Unassigned)</option>
                <optgroup label="الحملات النشطة">
                    @foreach($campaigns as $camp)
                        <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                    @endforeach
                </optgroup>
            </select>

            <div class="relative w-full md:w-64">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">🔍</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ابحث بالاسم، الإيميل، أو الشركة..." class="w-full pr-10 pl-2 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-right">
                            <input type="checkbox" wire:model.live="selectAll" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">صانع القرار</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">المنشأة (الشركة)</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">الحملة الحالية</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase">حالة الإيميل</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($contacts as $contact)
                        <tr class="hover:bg-blue-50 transition {{ in_array($contact->id, $selectedContacts) ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" wire:model.live="selectedContacts" value="{{ $contact->id }}" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-gray-900 text-sm">{{ $contact->full_name }}</div>
                                <div class="text-xs text-blue-600 font-bold">{{ $contact->job_title }}</div>
                                <div class="text-xs text-gray-500 font-mono mt-1">{{ $contact->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700">
                                🏢 {{ $contact->lead->company ?? 'غير محدد' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($contact->campaign)
                                    <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded border border-indigo-100 text-xs font-bold">
                                        🎯 {{ $contact->campaign->name }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded border text-xs font-bold">
                                        ⚠️ غير محدد
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($contact->status == 'replied') <span class="text-green-600 font-bold text-xs">💬 تم الرد</span>
                                @elseif($contact->status == 'opened') <span class="text-yellow-600 font-bold text-xs">👁️ تم الفتح</span>
                                @elseif($contact->status == 'new') <span class="text-gray-500 font-bold text-xs">جديد</span>
                                @else <span class="text-gray-600 font-bold text-xs uppercase">{{ $contact->status }}</span> @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">لا يوجد أشخاص مطابقين للبحث.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
            {{ $contacts->links() }}
        </div>
    </div>

    @if(count($selectedContacts) > 0)
        <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 w-11/12 md:w-auto bg-gray-900 text-white rounded-xl shadow-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 z-50 border border-gray-700 animate-bounce-short">
            
            <div class="flex items-center gap-3">
                <span class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-inner">
                    {{ count($selectedContacts) }}
                </span>
                <span class="font-bold">أشخاص محددين</span>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <select wire:model="bulkCampaignId" class="flex-1 md:w-64 px-3 py-2 bg-gray-800 border border-gray-700 text-white rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- اختر الحملة المراد توجيههم لها --</option>
                    @foreach($campaigns as $camp)
                        <option value="{{ $camp->id }}">🎯 {{ $camp->name }}</option>
                    @endforeach
                </select>

                <button wire:click="assignBulkCampaign" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-bold transition shadow-lg whitespace-nowrap">
                    تطبيق وتوجيه 🚀
                </button>

                <button wire:click="$set('selectedContacts', [])" class="text-gray-400 hover:text-white px-2" title="إلغاء التحديد">✕</button>
            </div>
            
            @error('bulkCampaignId') <span class="absolute -top-8 bg-red-100 text-red-600 px-3 py-1 rounded text-xs font-bold">{{ $message }}</span> @enderror
        </div>
    @endif
    
</div>

<style>
    .animate-bounce-short {
        animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes slideUp {
        from { transform: translate(-50%, 100%); opacity: 0; }
        to { transform: translate(-50%, 0); opacity: 1; }
    }
</style>