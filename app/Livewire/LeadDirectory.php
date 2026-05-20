<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Campaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LeadDirectory extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';
    public $search = '';
    public $filterIndustry = ''; // 🌟 المتغير الجديد لفلتر القطاع

    // ==========================================
    // 1. متغيرات التنقيب (Google Maps & Manual Add)
    // ==========================================
    public $showAddModal = false;
    public $addMode = 'bulk_companies'; 
    public $targetLocation = '';
    public $targetIndustry = '';
    public $m_company, $m_industry;

    // ==========================================
    // 2. متغيرات تعديل المنشأة (Edit Lead)
    // ==========================================
    public $showEditLeadModal = false;
    public $editingLeadId;
    public $e_company, $e_industry, $e_location, $e_phone, $e_website;

    // ==========================================
    // 3. متغيرات أصحاب القرار (Add/Edit Contact)
    // ==========================================
    public $showContactModal = false;
    public $editingContactId = null;
    public $c_lead_id; 
    public $c_full_name, $c_job_title, $c_email, $c_phone, $c_status, $c_campaign_id;

    public $selectedContacts = []; // لتخزين الـ IDs

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterIndustry() { $this->resetPage(); } // 🌟 تصفير عند تغيير القطاع

    // ==========================================
    // القسم الأول: التنقيب والإثراء (Prospecting & Enrichment)
    // ==========================================

    public function fetchCompaniesOnly()
    {
        $this->validate([
            'targetLocation' => 'required|string',
            'targetIndustry' => 'required|string'
        ]);

        $apiKey = env('GOOGLE_MAPS_API_KEY');
        if (!$apiKey) {
            session()->flash('error', 'مفتاح Google Maps API غير موجود.');
            return;
        }

        $query = urlencode($this->targetIndustry . ' in ' . $this->targetLocation);
        $baseUrl = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&key={$apiKey}";

        $count = 0;
        $pageToken = null;
        $maxPages = 3; // سحب حتى 3 صفحات (حوالي 60 شركة)

        try {
            for ($i = 0; $i < $maxPages; $i++) {
                $url = $baseUrl . ($pageToken ? "&pagetoken={$pageToken}" : "");
                
                // withoutVerifying لتخطي خطأ شهادة SSL في بيئة التطوير (Localhost)
                $response = Http::withoutVerifying()->timeout(15)->get($url);

                if ($response->successful() && isset($response['results'])) {
                    $companies = $response['results'];

                    foreach ($companies as $place) {
                        $placeId = $place['place_id'] ?? null;
                        $phone = null;
                        $website = null;

                        // جلب الهاتف والموقع عبر Place Details API
                        if ($placeId) {
                            $detailsUrl = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=formatted_phone_number,website&key={$apiKey}";
                            $detailsResponse = Http::withoutVerifying()->get($detailsUrl);
                            
                            if ($detailsResponse->successful() && isset($detailsResponse['result'])) {
                                $phone = $detailsResponse['result']['formatted_phone_number'] ?? null;
                                $website = $detailsResponse['result']['website'] ?? null;
                            }
                        }

                        $uniqueSourceUrl = 'gmaps://place/' . md5($place['name'] . $this->targetLocation);

                        $lead = Lead::firstOrCreate(
                            ['company' => $place['name']],
                            [
                                'platform' => 'google_maps',
                                'job_title' => 'General Company',
                                'source_url' => $uniqueSourceUrl,
                                'industry' => $this->targetIndustry,
                                'location' => $place['formatted_address'] ?? $this->targetLocation,
                                'phone' => $phone,
                                'website' => $website,
                                'source' => 'google_maps',
                                'role_type' => 'B2B Lead',
                                'status' => 'New',
                                'enrichment_stage' => 'pending'
                            ]
                        );

                        if ($lead->wasRecentlyCreated) {
                            $count++;
                        } else {
                            if (!$lead->phone && $phone) $lead->update(['phone' => $phone]);
                            if (!$lead->website && $website) $lead->update(['website' => $website]);
                        }
                    }

                    if (isset($response['next_page_token'])) {
                        $pageToken = $response['next_page_token'];
                        sleep(2); // التوقف لثانيتين لضمان تفعيل التوكن الجديد في جوجل
                    } else {
                        break; 
                    }
                } else {
                    break;
                }
            }

            $this->targetLocation = '';
            $this->targetIndustry = '';
            $this->showAddModal = false;
            
            if ($count > 0) {
                session()->flash('message', "تم استخراج {$count} شركة جديدة مع بياناتها بنجاح!");
            } else {
                session()->flash('message', 'جميع الشركات المتطابقة موجودة مسبقاً، وتم تحديث الهواتف والمواقع إن وجدت.');
            }

        } catch (\Exception $e) {
            session()->flash('error', 'حدث خطأ في الاتصال بجوجل: ' . $e->getMessage());
        }
    }

    public function saveManualCompany()
    {
        $this->validate(['m_company' => 'required|string']);
        $uniqueSourceUrl = 'manual://lead/' . Str::uuid();

        Lead::firstOrCreate(
            ['company' => $this->m_company],
            [
                'platform' => 'manual_entry',
                'job_title' => 'General Company',
                'source_url' => $uniqueSourceUrl,
                'industry' => $this->m_industry ?? 'Healthcare',
                'location' => 'Unknown',
                'source' => 'manual',
                'role_type' => 'B2B Lead',
                'status' => 'New',
                'enrichment_stage' => 'pending'
            ]
        );

        $this->reset(['m_company', 'm_industry', 'showAddModal']);
        session()->flash('message', 'تمت إضافة الشركة بنجاح!');
    }

    public function enrichSingleCompany($leadId)
    {
        $lead = Lead::find($leadId);
        if (!$lead) return;

        $apiKey = env('APOLLO_API_KEY');
        if (!$apiKey) {
            session()->flash('error', 'مفتاح Apollo API غير موجود.');
            return;
        }

        // تجهيز مصفوفة البحث الأساسية
        $payload = [
            'page' => 1,
            'per_page' => 10,
        ];

        // التحقق مما إذا كانت الشركة تمتلك موقعاً إلكترونياً مسجلاً في النظام
        if (!empty($lead->website)) {
            // استخراج النطاق الصافي (Domain) من الرابط (مثال: من https://www.company.com إلى company.com)
            $domain = parse_url($lead->website, PHP_URL_HOST) ?? $lead->website;
            $domain = str_replace('www.', '', $domain);
            
            // توجيه أبولو للبحث بالنطاق (أدق بنسبة 99%)
            $payload['q_organization_domains'] = $domain;
        } else {
            // إذا لم يوجد موقع، نعتمد على البحث بالاسم
            $payload['q_organization_name'] = $lead->company;
        }

        try {
            // 2. أضفنا مفتاح الأمان X-Api-Key هنا في الـ Headers
            $response = Http::withoutVerifying()->withHeaders([
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'application/json',
                'X-Api-Key' => $apiKey 
            ])->timeout(20)->post('https://api.apollo.io/v1/mixed_people/api_search', $payload);

            if ($response->successful() && isset($response['people'])) {
                $people = $response['people'];
                $count = 0;

                foreach ($people as $person) {
                    if (!empty($person['email'])) {
                        $contact = Contact::firstOrCreate(
                            ['email' => $person['email']],
                            [
                                'lead_id' => $lead->id,
                                'full_name' => ($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''),
                                'job_title' => $person['title'] ?? 'Unknown',
                                'linkedin_url' => $person['linkedin_url'] ?? null,
                                'source' => 'Apollo API',
                                'status' => 'new',
                                'campaign_id' => null, // تعيين فارغ افتراضياً
                            ]
                        );
                        if ($contact->wasRecentlyCreated) $count++;
                    }
                }

                $lead->update(['enrichment_stage' => 'completed_apollo']);

                if ($count > 0) {
                    session()->flash('message', "تم جلب وتوجيه {$count} صانع قرار لـ {$lead->company}.");
                } else {
                    session()->flash('error', "لم يتم العثور على إيميلات لموظفي {$lead->company}.");
                }
            } else {
                // التقاط الخطأ الحقيقي من خوادم أبولو
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['error'] ?? $response->body();
                
                session()->flash('error', 'رفض أبولو الطلب. السبب: ' . json_encode($errorMessage));
                
                // تسجيل الخطأ في ملف الـ Logs للرجوع إليه
                \Illuminate\Support\Facades\Log::error('Apollo API Failed', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'company' => $lead->company
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في الاتصال بأبولو: ' . $e->getMessage());
        }
    }

    public function enrichAllCompanies()
    {
        $emptyLeads = Lead::doesntHave('contacts')->where('enrichment_stage', 'pending')->get();

        if ($emptyLeads->count() == 0) {
            session()->flash('error', 'جميع الشركات في النظام تم إثراؤها مسبقاً.');
            return;
        }

        $successCount = 0;
        foreach ($emptyLeads as $lead) {
            $this->enrichSingleCompany($lead->id);
            sleep(1); // حماية من الحظر (Rate Limiting)
            $successCount++;
        }

        session()->flash('message', "تم إرسال أوامر الإثراء لـ {$successCount} شركة.");
    }

    // ==========================================
    // القسم الثاني: الإدارة اليدوية (CRUD)
    // ==========================================

    public function openEditLeadModal($id)
    {
        $lead = Lead::find($id);
        $this->editingLeadId = $lead->id;
        $this->e_company = $lead->company;
        $this->e_industry = $lead->industry;
        $this->e_location = $lead->location;
        $this->e_phone = $lead->phone;
        $this->e_website = $lead->website;
        $this->showEditLeadModal = true;
    }

    public function updateLead()
    {
        $this->validate(['e_company' => 'required|string']);

        Lead::find($this->editingLeadId)->update([
            'company' => $this->e_company,
            'industry' => $this->e_industry,
            'location' => $this->e_location,
            'phone' => $this->e_phone,
            'website' => $this->e_website,
        ]);

        $this->showEditLeadModal = false;
        session()->flash('message', 'تم تحديث بيانات المنشأة بنجاح.');
    }

    public function deleteLead($id)
    {
        Contact::where('lead_id', $id)->delete();
        Lead::find($id)->delete();
        session()->flash('message', 'تم حذف المنشأة وجميع المرتبطين بها نهائياً.');
    }

    public function openContactModal($leadId, $contactId = null)
    {
        $this->resetValidation();
        $this->c_lead_id = $leadId;
        
        if ($contactId) {
            $contact = Contact::find($contactId);
            $this->editingContactId = $contact->id;
            $this->c_full_name = $contact->full_name;
            $this->c_job_title = $contact->job_title;
            $this->c_email = $contact->email;
            $this->c_phone = $contact->phone_number ?? '';
            $this->c_status = $contact->status;
            $this->c_campaign_id = $contact->campaign_id;
        } else {
            $this->editingContactId = null;
            $this->c_full_name = ''; $this->c_job_title = ''; $this->c_email = ''; 
            $this->c_phone = ''; $this->c_status = 'new'; $this->c_campaign_id = '';
        }
        
        $this->showContactModal = true;
    }

    public function saveContact()
    {
        $this->validate([
            'c_full_name' => 'required|string',
            'c_email' => 'required|email',
        ]);

        if ($this->editingContactId) {
            Contact::find($this->editingContactId)->update([
                'full_name' => $this->c_full_name,
                'job_title' => $this->c_job_title,
                'email' => $this->c_email,
                'phone_number' => $this->c_phone,
                'status' => $this->c_status,
                'campaign_id' => $this->c_campaign_id ?: null,
            ]);
            session()->flash('message', 'تم تحديث بيانات صانع القرار.');
        } else {
            Contact::create([
                'lead_id' => $this->c_lead_id,
                'full_name' => $this->c_full_name,
                'job_title' => $this->c_job_title,
                'email' => $this->c_email,
                'phone_number' => $this->c_phone,
                'status' => $this->c_status,
                'campaign_id' => $this->c_campaign_id ?: $this->routeToCampaign($this->c_job_title),
                'source' => 'Manual Entry',
            ]);
            session()->flash('message', 'تمت إضافة صانع القرار للمنشأة.');
        }

        $this->showContactModal = false;
    }

    public function deleteContact($id)
    {
        Contact::find($id)->delete();
        session()->flash('message', 'تم حذف صانع القرار.');
    }

    // ==========================================
    // القسم الثالث: المساعدات والعرض (Helpers & Render)
    // ==========================================

    private function routeToCampaign($title)
    {
        if (empty($title)) return null;

        $titleLower = strtolower($title);
        $activeCampaigns = Campaign::where('status', 'active')->get();

        foreach ($activeCampaigns as $campaign) {
            if (empty($campaign->target_role) || strtolower($campaign->target_role) === 'general') continue;
            
            $keywords = array_map('trim', explode(',', strtolower($campaign->target_role)));
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($titleLower, $keyword)) return $campaign->id;
            }
        }
        $default = Campaign::where('target_role', 'like', '%general%')->first();
        return $default ? $default->id : null;
    }

    public function render()
    {
        // 🌟 1. جلب قائمة ديناميكية بكل القطاعات الموجودة في قاعدة البيانات
        $industries = Lead::select('industry')
            ->whereNotNull('industry')
            ->where('industry', '!=', '')
            ->distinct()
            ->pluck('industry');

        // 🌟 2. بناء استعلام جلب الشركات مع تطبيق الفلاتر
        $leads = Lead::with(['contacts' => function($query) {
            $query->with('campaign')->orderBy('created_at', 'desc');
        }])
        ->when($this->search, function($query) {
            // تجميع البحث في قوسين () لكي لا يتعارض مع فلتر القطاع
            $query->where(function($q) {
                $q->where('company', 'like', '%' . $this->search . '%')
                  ->orWhere('location', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->filterIndustry, function($query) {
            // تطبيق فلتر القطاع إذا تم اختياره
            $query->where('industry', $this->filterIndustry);
        })
        ->orderBy('updated_at', 'desc')
        ->paginate(15);

        $campaigns = Campaign::where('status', 'active')->get();

        return view('livewire.lead-directory', compact('leads', 'campaigns', 'industries'));
    }
}