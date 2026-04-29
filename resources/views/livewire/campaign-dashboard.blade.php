<div class="p-6 bg-gray-50 min-h-screen" wire:poll.10s>
    @if (session()->has('message'))
        <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg shadow font-bold border border-green-200">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">غرفة العمليات الحية 📡</h2>
        
        <div class="flex items-center gap-4 w-full md:w-auto">
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-md shadow-sm border border-gray-200">
                <span class="text-sm font-bold text-gray-600">فلتر الحملة:</span>
                <select wire:model.live="selectedCampaignId" class="border-none bg-transparent text-sm font-bold text-blue-700 focus:ring-0 cursor-pointer">
                    <option value="">جميع الحملات</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </div>

            <button wire:click="triggerCampaignManually" wire:loading.attr="disabled" class="px-4 py-2 bg-blue-600 text-white rounded-md shadow hover:bg-blue-700 transition font-bold disabled:opacity-50">
                <span wire:loading.remove wire:target="triggerCampaignManually">🚀 إرسال دفعة الآن</span>
                <span wire:loading wire:target="triggerCampaignManually">جاري الإرسال...</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-lg shadow border-l-4 border-blue-500 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-16 h-16 bg-blue-50 rounded-br-full -z-10"></div>
            <p class="text-sm font-bold text-gray-500">إجمالي المرسل</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['total_sent'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-l-4 border-yellow-500 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-16 h-16 bg-yellow-50 rounded-br-full -z-10"></div>
            <p class="text-sm font-bold text-gray-500">معدل الفتح (Open Rate)</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['open_rate'] }}%</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-l-4 border-green-500 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-16 h-16 bg-green-50 rounded-br-full -z-10"></div>
            <p class="text-sm font-bold text-gray-500">الردود الإيجابية (Replies)</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['replied'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-l-4 border-red-500 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-16 h-16 bg-red-50 rounded-br-full -z-10"></div>
            <p class="text-sm font-bold text-gray-500">إيميلات خاطئة (Bounced)</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['bounced'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">أحدث تفاعلات المنشآت</h3>
            <span class="text-xs text-gray-500 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> تحديث تلقائي</span>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">المنشأة الطبية</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">صانع القرار</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">الحملة المستهدفة</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">الخطوة</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">الحالة</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($contacts as $contact)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                        🏢 {{ $contact->lead->company ?? 'غير محدد' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="font-bold">{{ $contact->full_name }}</span> <br>
                        <span class="text-xs text-gray-500 font-mono">{{ $contact->email }}</span><br>
                        <span class="text-xs text-blue-600">{{ $contact->job_title }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        @if($contact->campaign)
                            <span class="px-2 py-1 bg-gray-100 rounded-md text-xs font-bold border border-gray-200">
                                {{ $contact->campaign->name }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">بدون حملة</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-900">
                        {{ $contact->current_step }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($contact->status == 'new')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-gray-100 text-gray-800 border border-gray-200">جديد</span>
                        @elseif($contact->status == 'emailed')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-blue-100 text-blue-800 border border-blue-200">تم الإرسال</span>
                        @elseif($contact->status == 'opened')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">👁️ تم الفتح</span>
                        @elseif($contact->status == 'replied')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-green-100 text-green-800 border border-green-200">💬 تم الرد</span>
                        @elseif($contact->status == 'bounced')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-red-100 text-red-800 border border-red-200">خاطئ / محظور</span>
                        @elseif($contact->status == 'completed')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-purple-100 text-purple-800 border border-purple-200">انتهت الحملة</span>
                        @else
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-gray-100 text-gray-800">{{ $contact->status }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
            {{ $contacts->links() }}
        </div>
    </div>
</div>