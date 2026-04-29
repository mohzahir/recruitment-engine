<div class="p-6 bg-gray-50 min-h-screen" dir="rtl">
    
    @if(session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg border border-green-200 font-bold shadow-sm flex items-center gap-2">
            <span>✅</span> {{ session('message') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg border border-red-200 font-bold shadow-sm flex items-center gap-2">
            <span>⚠️</span> {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">قاعدة المنشآت (Prospecting & Enrichment)</h2>
            <p class="text-sm text-gray-500 mt-1">إدارة شاملة للتنقيب، الإثراء الآلي، وتعديل البيانات</p>
        </div>
        
        <div class="flex flex-wrap w-full md:w-auto gap-3 items-center">
            
            <div class="relative w-full md:w-40">
                <select wire:model.live="filterIndustry" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 text-sm text-gray-700 bg-white font-bold cursor-pointer">
                    <option value="">جميع القطاعات</option>
                    @foreach($industries as $industry)
                        <option value="{{ $industry }}">{{ $industry }}</option>
                    @endforeach
                </select>
            </div>

            <div class="relative w-full md:w-48">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">🔍</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ابحث في الشركات..." class="w-full pr-10 pl-2 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 text-sm">
            </div>
            
            <button wire:click="enrichAllCompanies" wire:loading.attr="disabled" class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow hover:bg-indigo-700 transition font-bold text-sm flex items-center gap-2 disabled:opacity-50">
                <span wire:loading.remove wire:target="enrichAllCompanies">⚡ إثراء جماعي (Apollo)</span>
                <span wire:loading wire:target="enrichAllCompanies">جاري الجلب...</span>
            </button>

            <button wire:click="$set('showAddModal', true)" class="bg-gray-900 text-white px-4 py-2 rounded-md shadow hover:bg-gray-800 transition font-bold text-sm flex items-center gap-2">
                <span>+</span> إضافة منشآت (Maps)
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
        
        <div class="px-6 py-4 bg-gray-100 flex justify-between items-center text-xs font-bold text-gray-600 uppercase">
            <div class="w-1/2">اسم المنشأة وموقعها</div>
            <div class="w-1/4 text-center">أصحاب القرار</div>
            <div class="w-1/4 text-left">الإجراءات</div>
        </div>

        @forelse($leads as $lead)
            <div x-data="{ expanded: false }" class="group border-b last:border-0">
                
                <div class="px-6 py-4 flex justify-between items-center hover:bg-blue-50 transition duration-150">
                    
                    <div class="w-1/2 flex items-center gap-3">
                        <span @click="expanded = !expanded" class="text-gray-400 cursor-pointer" :class="expanded ? 'transform rotate-90 text-blue-600' : ''">▶</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span @click="expanded = !expanded" class="font-bold text-gray-900 text-base cursor-pointer hover:text-blue-700">{{ $lead->company }}</span>
                                
                                @if($lead->phone) <a href="tel:{{ $lead->phone }}" title="اتصال: {{ $lead->phone }}" class="text-gray-400 hover:text-green-600" @click.stop>📞</a> @endif
                                @if($lead->website) <a href="{{ $lead->website }}" target="_blank" title="زيارة الموقع" class="text-gray-400 hover:text-blue-600" @click.stop>🌐</a> @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 flex gap-2">
                                <span class="bg-gray-100 px-2 py-0.5 rounded border">{{ $lead->industry ?? 'عام' }}</span>
                                <span class="text-gray-400 truncate max-w-[200px]" title="{{ $lead->location }}">🌍 {{ $lead->location ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div @click="expanded = !expanded" class="w-1/4 text-center cursor-pointer">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full {{ $lead->contacts->count() > 0 ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-red-50 text-red-600 border border-red-100' }}">
                            @if($lead->contacts->count() > 0) 👥 {{ $lead->contacts->count() }} مسجل @else ⚠️ لا يوجد داتا @endif
                        </span>
                    </div>

                    <div class="w-1/4 text-left flex justify-end gap-3 items-center">
                        @if($lead->contacts->count() == 0 && $lead->enrichment_stage !== 'completed_apollo')
                            <button wire:click="enrichSingleCompany({{ $lead->id }})" wire:loading.attr="disabled" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded text-xs font-bold border border-indigo-200 transition shadow-sm disabled:opacity-50" title="جلب الموظفين آلياً">
                                <span wire:loading.remove wire:target="enrichSingleCompany({{ $lead->id }})">🔍 إثراء</span>
                                <span wire:loading wire:target="enrichSingleCompany({{ $lead->id }})">...</span>
                            </button>
                        @endif
                        <button wire:click="openEditLeadModal({{ $lead->id }})" class="text-blue-500 hover:text-blue-700 text-sm" title="تعديل الشركة">✏️</button>
                        <button wire:click="deleteLead({{ $lead->id }})" onclick="confirm('هل أنت متأكد من حذف المنشأة وجميع موظفيها نهائياً؟') || event.stopImmediatePropagation()" class="text-red-500 hover:text-red-700 text-sm" title="حذف الشركة">🗑️</button>
                    </div>
                </div>

                <div x-show="expanded" x-collapse x-cloak class="bg-gray-50 border-t border-b border-gray-100 px-10 py-4 shadow-inner">
                    
                    <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-2">
                        <h4 class="text-sm font-bold text-gray-700">هيكل صناع القرار والتوجيه لحملات التوظيف</h4>
                        <button wire:click="openContactModal({{ $lead->id }})" class="text-xs bg-gray-800 text-white px-3 py-1.5 rounded shadow hover:bg-black font-bold transition">+ إضافة شخص يدوياً</button>
                    </div>

                    @if($lead->contacts->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($lead->contacts as $contact)
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex justify-between items-center group/contact hover:border-blue-300 transition">
                                    <div>
                                        <div class="font-bold text-gray-800 text-sm">{{ $contact->full_name }}</div>
                                        <div class="text-xs text-blue-600 font-bold mb-1">{{ $contact->job_title }}</div>
                                        <div class="text-xs text-gray-500 font-mono flex items-center gap-1">
                                            ✉️ {{ $contact->email }}
                                            @if($contact->phone_number) <br> 📱 {{ $contact->phone_number }} @endif
                                        </div>
                                    </div>
                                    <div class="text-left flex flex-col items-end gap-2">
                                        @if($contact->status == 'replied') <span class="px-2 py-1 bg-green-100 text-green-800 text-[10px] font-bold rounded-md uppercase">💬 تم الرد</span>
                                        @elseif($contact->status == 'opened') <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-[10px] font-bold rounded-md uppercase">👁️ تم الفتح</span>
                                        @else <span class="px-2 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold rounded-md uppercase">{{ $contact->status }}</span> @endif
                                        
                                        <span class="text-[10px] bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded border border-indigo-100">🎯 {{ $contact->campaign->name ?? 'بدون حملة' }}</span>
                                        
                                        <div class="opacity-0 group-hover/contact:opacity-100 transition flex gap-3 mt-1">
                                            <button wire:click="openContactModal({{ $lead->id }}, {{ $contact->id }})" class="text-blue-500 hover:text-blue-700 text-xs font-bold">تعديل</button>
                                            <button wire:click="deleteContact({{ $contact->id }})" onclick="confirm('حذف هذا الشخص نهائياً؟') || event.stopImmediatePropagation()" class="text-red-500 hover:text-red-700 text-xs font-bold">حذف</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg bg-white">
                            <p class="text-sm text-gray-500 mb-2">هذه المنشأة لا تمتلك أصحاب قرار في قاعدة بياناتك حالياً.</p>
                            <button wire:click="enrichSingleCompany({{ $lead->id }})" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm font-bold shadow hover:bg-indigo-700">🔍 جلب الداتا الآن من أبولو</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center text-gray-500">
                <span class="text-4xl block mb-3">🏢</span>
                <p class="font-bold">قاعدة البيانات فارغة.</p>
                <p class="text-sm mt-1">اضغط على زر "إضافة منشآت" للبدء في استخراج الشركات.</p>
            </div>
        @endforelse
    </div>
    
    <div class="mt-4">{{ $leads->links() }}</div>

    @if($showAddModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl w-[500px] overflow-hidden flex flex-col">
                <div class="bg-gray-900 text-white p-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold">إضافة منشآت جديدة 🏢</h3>
                    <button wire:click="$set('showAddModal', false)" class="text-gray-400 hover:text-white font-bold text-xl">✕</button>
                </div>

                <div class="flex border-b border-gray-200">
                    <button wire:click="$set('addMode', 'bulk_companies')" class="flex-1 py-3 text-sm font-bold text-center transition border-b-2 {{ $addMode == 'bulk_companies' ? 'border-gray-900 text-gray-900 bg-gray-50' : 'border-transparent text-gray-500 hover:bg-gray-50' }}">
                        📍 سحب من خرائط جوجل
                    </button>
                    <button wire:click="$set('addMode', 'manual_company')" class="flex-1 py-3 text-sm font-bold text-center transition border-b-2 {{ $addMode == 'manual_company' ? 'border-gray-900 text-gray-900 bg-gray-50' : 'border-transparent text-gray-500 hover:bg-gray-50' }}">
                        ✍️ إدخال شركة مخصصة
                    </button>
                </div>

                <div class="p-6 bg-gray-50 flex-1">
                    @if($addMode == 'bulk_companies')
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 mb-2">حدد المدينة والمجال، وسيقوم النظام بسحب أسماء الشركات، هواتفها، ومواقعها الإلكترونية بدقة عالية.</p>
                            <div>
                                <label class="block text-sm font-bold text-gray-700">المدينة / المنطقة</label>
                                <input type="text" wire:model="targetLocation" placeholder="مثال: الرياض، دبي، جدة..." class="mt-1 w-full border border-gray-300 rounded-md p-2 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700">مجال العمل (Industry)</label>
                                <select wire:model="targetIndustry" class="mt-1 w-full border border-gray-300 rounded-md p-2 shadow-sm text-sm">
                                    <option value="">اختر المجال...</option>
                                    <option value="Hospitals">مستشفيات</option>
                                    <option value="Clinics">مجمعات طبية وعيادات</option>
                                    <option value="Software Companies">شركات برمجيات وتقنية</option>
                                    <option value="Real Estate">شركات عقارية</option>
                                </select>
                            </div>
                            <div class="pt-4">
                                <button wire:click="fetchCompaniesOnly" wire:loading.attr="disabled" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow hover:bg-blue-700 transition flex justify-center items-center gap-2 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="fetchCompaniesOnly">🗺️ فحص وسحب البيانات الآن</span>
                                    <span wire:loading wire:target="fetchCompaniesOnly">جاري فحص الخرائط (قد يستغرق ثواني)...</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if($addMode == 'manual_company')
                        <div class="space-y-4">
                            <div><label class="block text-sm font-bold text-gray-700">اسم المنشأة *</label>
                                <input type="text" wire:model="m_company" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm"></div>
                            <div><label class="block text-sm font-bold text-gray-700">المجال (Industry)</label>
                                <input type="text" wire:model="m_industry" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm" placeholder="مثال: Healthcare"></div>
                            <div class="pt-4">
                                <button wire:click="saveManualCompany" class="w-full bg-gray-900 text-white font-bold py-3 rounded-lg shadow hover:bg-black transition">
                                    💾 حفظ الشركة
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($showEditLeadModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl w-[500px] overflow-hidden">
                <div class="bg-blue-800 text-white p-4 flex justify-between items-center">
                    <h3 class="font-bold">تعديل بيانات المنشأة</h3>
                    <button wire:click="$set('showEditLeadModal', false)" class="text-blue-200 hover:text-white font-bold text-xl">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <div><label class="block text-xs font-bold text-gray-700">اسم الشركة</label>
                        <input type="text" wire:model="e_company" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm focus:ring-blue-500 focus:border-blue-500"></div>
                    <div><label class="block text-xs font-bold text-gray-700">المجال</label>
                        <input type="text" wire:model="e_industry" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm"></div>
                    <div><label class="block text-xs font-bold text-gray-700">الموقع الجغرافي (العنوان التفصيلي)</label>
                        <input type="text" wire:model="e_location" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm"></div>
                    <div class="flex gap-3">
                        <div class="flex-1"><label class="block text-xs font-bold text-gray-700">رقم الهاتف العام</label>
                            <input type="text" wire:model="e_phone" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm font-mono text-left direction-ltr"></div>
                        <div class="flex-1"><label class="block text-xs font-bold text-gray-700">الموقع الإلكتروني</label>
                            <input type="text" wire:model="e_website" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm font-mono text-left direction-ltr"></div>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 text-left flex justify-end gap-3 border-t">
                    <button wire:click="$set('showEditLeadModal', false)" class="bg-gray-200 text-gray-800 px-4 py-2 rounded font-bold hover:bg-gray-300 transition">إلغاء</button>
                    <button wire:click="updateLead" class="bg-blue-600 text-white px-4 py-2 rounded font-bold shadow hover:bg-blue-700 transition">💾 حفظ التحديثات</button>
                </div>
            </div>
        </div>
    @endif

    @if($showContactModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl w-[600px] overflow-hidden">
                <div class="bg-gray-900 text-white p-4 flex justify-between items-center">
                    <h3 class="font-bold">{{ $editingContactId ? 'تعديل بيانات صانع القرار ✏️' : 'إضافة صانع قرار جديد 👤' }}</h3>
                    <button wire:click="$set('showContactModal', false)" class="text-gray-400 hover:text-white font-bold text-xl">✕</button>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <div class="col-span-2"><label class="block text-xs font-bold text-gray-700">الاسم الكامل *</label>
                        <input type="text" wire:model="c_full_name" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm focus:ring-blue-500 focus:border-blue-500"></div>
                    
                    <div><label class="block text-xs font-bold text-gray-700">المسمى الوظيفي</label>
                        <input type="text" wire:model="c_job_title" placeholder="e.g. HR Director" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm text-left direction-ltr"></div>
                    
                    <div><label class="block text-xs font-bold text-gray-700">البريد الإلكتروني *</label>
                        <input type="email" wire:model="c_email" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm text-left direction-ltr font-mono">
                        @error('c_email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div><label class="block text-xs font-bold text-gray-700">الهاتف الشخصي (اختياري)</label>
                        <input type="text" wire:model="c_phone" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm font-mono text-left direction-ltr"></div>

                    <div><label class="block text-xs font-bold text-gray-700">الحالة في النظام</label>
                        <select wire:model="c_status" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm">
                            <option value="new">جديد (New)</option>
                            <option value="emailed">تم الإرسال (Emailed)</option>
                            <option value="opened">تم الفتح (Opened)</option>
                            <option value="replied">تم الرد (Replied)</option>
                            <option value="bounced">إيميل غير صالح (Bounced)</option>
                            <option value="archived">مؤرشف / لا تراسله (Archived)</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-700">توجيه الشخص لحملة توظيف</label>
                        <select wire:model="c_campaign_id" class="mt-1 w-full border border-gray-300 rounded p-2 text-sm">
                            <option value="">-- توجيه آلي حسب مسماه الوظيفي --</option>
                            @foreach($campaigns as $camp)
                                <option value="{{ $camp->id }}">{{ $camp->name }} (الكلمات: {{ $camp->target_role }})</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1">إذا تركت هذا الخيار فارغاً، سيقوم المحرك الذكي بقراءة المسمى الوظيفي للشخص وإرساله للحملة المناسبة.</p>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 text-left flex justify-end gap-3 border-t">
                    <button wire:click="$set('showContactModal', false)" class="bg-gray-200 text-gray-800 px-4 py-2 rounded font-bold hover:bg-gray-300 transition">إلغاء</button>
                    <button wire:click="saveContact" class="bg-gray-900 text-white px-5 py-2 rounded font-bold shadow hover:bg-black transition">💾 حفظ صانع القرار</button>
                </div>
            </div>
        </div>
    @endif
</div>