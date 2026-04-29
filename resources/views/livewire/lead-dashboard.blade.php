<div class="p-6 bg-gray-50 min-h-screen" wire:poll.15s>
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">الاستخبارات والتوجيه 🎯</h2>
            <p class="text-sm text-gray-500 mt-1">مراقبة عمليات استخراج البيانات وتوزيعها على الحملات</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-lg shadow border-r-4 border-indigo-500">
            <p class="text-sm font-bold text-gray-500">إجمالي المنشآت (Leads)</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['total_leads'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-r-4 border-blue-500">
            <p class="text-sm font-bold text-gray-500">إجمالي صناع القرار (Contacts)</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['total_contacts'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-r-4 border-green-500">
            <p class="text-sm font-bold text-gray-500">تم اصطيادهم اليوم</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['new_contacts_today'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-r-4 border-purple-500">
            <p class="text-sm font-bold text-gray-500">الحملات النشطة</p>
            <p class="text-3xl font-black text-gray-800 mt-1">{{ $stats['active_campaigns'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">أحدث صناع القرار الذين تم سحبهم وتوجيههم</h3>
            <span class="text-xs text-gray-500 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> المحرك يجمع البيانات
            </span>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">الاسم والمسمى الوظيفي</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">المنشأة</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">التوجيه الذكي (Routing)</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">المصدر</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">تاريخ الاستخراج</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recentContacts as $contact)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-bold text-sm text-gray-900">{{ $contact->full_name }}</div>
                        <div class="text-xs text-blue-600 mt-1 font-bold">{{ $contact->job_title }}</div>
                        <div class="text-xs text-gray-500 font-mono mt-1">{{ $contact->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">
                        🏢 {{ $contact->lead->company ?? 'غير محدد' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($contact->campaign)
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-md text-xs font-bold border border-indigo-200 shadow-sm">
                                🎯 {{ $contact->campaign->name }}
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-md text-xs font-bold border border-gray-200">
                                🗑️ حملة عامة / غير محدد
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                        {{ $contact->source }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $contact->created_at->diffForHumans() }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <span class="text-2xl mb-2 block">📡</span>
                        لا توجد بيانات حتى الآن. الرادار ينتظر تشغيل أمر الاستخراج!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
            {{ $recentContacts->links() }}
        </div>
    </div>
</div>