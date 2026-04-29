<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Lead;
use App\Models\Contact;
use Exception; // <=== أضف هذا السطر هنا

class EnrichLeadsCommand extends Command
{
    protected $signature = 'leads:enrich';
    protected $description = 'Multi-Vendor Waterfall Enrichment (Smart Retry on Empty Credits)';

    public function handle()
    {
        $this->info("==========================================");
        $this->info(" STARTING SMART WATERFALL ENGINE");
        $this->info("==========================================");

        // التعديل 1: جلب الشركات الجديدة، أو الشركات التي نفذ رصيدنا سابقاً أثناء معالجتها!
        $leads = Lead::whereIn('enrichment_stage', ['pending', 'pending_retry'])->get();

        if ($leads->count() === 0) {
            $this->info("All facilities are fully enriched!");
            return;
        }

        foreach ($leads as $lead) {
            $this->info("\nTarget: {$lead->company}");
            
            // متغير لتتبع ما إذا كان سبب الفشل هو نفاذ الرصيد في أي منصة
            $creditIssueDetected = false;

            // ==========================
            // المستوى الأول: Apollo
            // ==========================
            $apolloStatus = $this->enrichViaApollo($lead);
            if ($apolloStatus === 'success') continue;
            if ($apolloStatus === 'no_credits') $creditIssueDetected = true;

            // ==========================
            // التجهيز للمستويات القادمة
            // ==========================
            $domain = $this->extractDomain($lead);

            if ($domain) {
                // المستوى الثاني: RocketReach
                $rocketStatus = $this->enrichViaRocketReach($lead, $domain);
                if ($rocketStatus === 'success') continue;
                if ($rocketStatus === 'no_credits') $creditIssueDetected = true;

                // المستوى الثالث: Hunter
                $hunterStatus = $this->enrichViaHunter($lead, $domain);
                if ($hunterStatus === 'success') continue;
                if ($hunterStatus === 'no_credits') $creditIssueDetected = true;
            } else {
                $this->line("<fg=red>-> Skipped RocketReach & Hunter: No valid website domain found.</>");
            }

            // ==========================
            // التقييم النهائي للشركة
            // ==========================
            if ($creditIssueDetected) {
                // التعديل 2: إذا فشلنا وكان السبب نفاذ الرصيد في أحد المنصات
                $lead->update([
                    'enrichment_stage' => 'pending_retry',
                    'is_email_verified' => false // نتركها false لكي تعود في الدورة القادمة
                ]);
                $this->warn("-> Paused: Out of API credits or rate limit hit. Will retry later.");
            } else {
                // التعديل 3: فشل حقيقي (الرصيد متوفر لكن لا توجد داتا للشركة)
                $lead->update([
                    'enrichment_stage' => 'failed_all',
                    'is_email_verified' => true // نغلق ملف الشركة للأبد
                ]);
                $this->error("-> Exhausted all APIs. No contacts exist for this company.");
            }
            
            sleep(1); 
        }

        $this->info("\n==========================================");
        $this->info(" ENRICHMENT CYCLE COMPLETE!");
    }

    // ==========================================
    // TIER 1: APOLLO.IO
    // ==========================================
    private function enrichViaApollo($lead)
    {
        $this->output->write("-> Tier 1: Querying Apollo... ");
        $apiKey = env('APOLLO_API_KEY');
        if (!$apiKey) { $this->line("<fg=yellow>Skipped (No API Key).</>"); return 'not_found'; }

        try {
            $response = Http::withHeaders(['X-Api-Key' => $apiKey])->withoutVerifying()
                            ->timeout(10) // <=== ننتظر 10 ثوان كحد أقصى
                            ->post('https://api.apollo.io/v1/mixed_people/api_search', [
                                'q_organization_name' => $lead->company,
                                'person_titles' => ['hr', 'human resources', 'talent acquisition', 'director of nursing', 'chief medical officer', 'manager'],
                                'page' => 1,
                            ]);

            if (in_array($response->status(), [401, 402, 403, 429])) {
                $this->line("<fg=yellow>Failed (Out of Credits / Rate Limit).</>");
                return 'no_credits';
            }

            if ($response->successful() && !empty($response->json()['people'])) {
                // سحب أول 10 شخصيات قيادية بدلاً من 3
                $people = array_slice($response->json()['people'], 0, 10);
                $savedCount = 0;

                foreach ($people as $person) {
                    // حماية محاولة فتح الإيميل أيضاً
                    try {
                        $unlock = Http::withHeaders(['X-Api-Key' => $apiKey])->withoutVerifying()->timeout(10)
                                      ->post('https://api.apollo.io/v1/people/match', ['id' => $person['id']]);

                        if ($unlock->successful() && isset($unlock->json()['person']['email'])) {
                            $pData = $unlock->json()['person'];
                            $name = trim(($pData['first_name'] ?? '') . ' ' . ($pData['last_name'] ?? ''));
                            $this->saveContact($lead->id, $name, $pData['title'] ?? 'Decision Maker', $pData['email'], 'apollo');
                            $savedCount++;
                        }
                    } catch (Exception $e) {
                        // تجاهل خطأ الفتح الفردي وأكمل
                    }
                    sleep(1);
                }

                if ($savedCount > 0) {
                    $lead->update(['enrichment_stage' => 'completed_apollo', 'is_email_verified' => true]);
                    $this->line("<fg=green>Success ({$savedCount} contacts saved).</>");
                    return 'success';
                }
            }
        } catch (Exception $e) {
            // <=== إذا حدث Timeout، نطبعه ونتجاوزه
            $this->line("<fg=yellow>Failed (Connection Timeout).</>");
            return 'not_found';
        }
        
        $this->line("<fg=red>Not Found.</>");
        return 'not_found';
    }

    // ==========================================
    // TIER 2: ROCKETREACH
    // ==========================================
    private function enrichViaRocketReach($lead, $domain)
    {
        $this->output->write("-> Tier 2: Querying RocketReach... ");
        $apiKey = env('ROCKETREACH_API_KEY'); 
        if (!$apiKey) { $this->line("<fg=yellow>Skipped (No API Key).</>"); return 'not_found'; }

        try {
            $response = Http::withHeaders(['Api-Key' => $apiKey])->withoutVerifying()
                            ->timeout(10) // <=== ننتظر 10 ثوان كحد أقصى
                            ->post('https://api.rocketreach.co/v2/api/search', [
                                'query' => ['current_employer' => [$lead->company], 'title' => ['HR', 'Human Resources', 'Director']],
                                'start' => 1, 'pageSize' => 10 // توسيع نطاق البحث لـ 10 أشخاص
                            ]);

            if (in_array($response->status(), [401, 402, 403, 429])) {
                $this->line("<fg=yellow>Failed (Out of Credits / Rate Limit).</>");
                return 'no_credits';
            }

            if ($response->successful() && !empty($response->json()['profiles'])) {
                $savedCount = 0;
                foreach ($response->json()['profiles'] as $person) {
                    $email = $person['current_work_email'] ?? $person['teaser']['emails'][0] ?? null;
                    if ($email && !str_contains($email, '*****')) { 
                        $name = trim($person['name'] ?? 'Facility Contact');
                        $this->saveContact($lead->id, $name, $person['current_title'] ?? 'HR / Management', $email, 'rocketreach');
                        $savedCount++;
                    }
                }

                if ($savedCount > 0) {
                    $lead->update(['enrichment_stage' => 'completed_rocketreach', 'is_email_verified' => true]);
                    $this->line("<fg=green>Success ({$savedCount} contacts saved).</>");
                    return 'success';
                }
            }
        } catch (Exception $e) {
            $this->line("<fg=yellow>Failed (Connection Timeout).</>");
            return 'not_found';
        }

        $this->line("<fg=red>Not Found.</>");
        return 'not_found';
    }

    // ==========================================
    // TIER 3: HUNTER.IO
    // ==========================================
    private function enrichViaHunter($lead, $domain)
    {
        $this->output->write("-> Tier 3: Querying Hunter.io... ");
        $apiKey = env('HUNTER_API_KEY'); 
        if (!$apiKey) { $this->line("<fg=yellow>Skipped (No API Key).</>"); return 'not_found'; }

        try {
            $response = Http::withoutVerifying()->timeout(10) // <=== ننتظر 10 ثوان كحد أقصى
                            ->get('https://api.hunter.io/v2/domain-search', [
                                'domain' => $domain, 'api_key' => $apiKey, 'limit' => 10, 'type' => 'personal'
                            ]);

            if (in_array($response->status(), [401, 402, 403, 429])) {
                $this->line("<fg=yellow>Failed (Out of Credits / Rate Limit).</>");
                return 'no_credits';
            }

            if ($response->successful() && !empty($response->json()['data']['emails'])) {
                $savedCount = 0;
                foreach ($response->json()['data']['emails'] as $emailData) {
                    $name = trim(($emailData['first_name'] ?? '') . ' ' . ($emailData['last_name'] ?? '')) ?: 'Facility Contact';
                    $this->saveContact($lead->id, $name, $emailData['position'] ?? 'Staff / Management', $emailData['value'], 'hunter');
                    $savedCount++;
                }

                if ($savedCount > 0) {
                    $lead->update(['enrichment_stage' => 'completed_hunter', 'is_email_verified' => true]);
                    $this->line("<fg=green>Success ({$savedCount} emails saved).</>");
                    return 'success';
                }
            }
        } catch (Exception $e) {
            $this->line("<fg=yellow>Failed (Connection Timeout).</>");
            return 'not_found';
        }

        $this->line("<fg=red>Not Found.</>");
        return 'not_found';
    }

    // ==========================================
    // HELPERS
    // ==========================================
    private function extractDomain($lead)
    {
        $url = $lead->source_url;
        if (str_contains($url, 'http://googleusercontent.com/maps.google.com/') || str_contains($url, 'googleusercontent')) {
            if (preg_match('/Website:\s*(https?:\/\/[^\s]+)/i', $lead->job_description, $matches)) {
                $url = $matches[1];
            } else { return null; }
        }
        $domain = parse_url($url, PHP_URL_HOST);
        return preg_replace('/^www\./', '', $domain);
    }

    // ==========================================
    // HELPER: Save to Contacts Database with Smart Routing
    // ==========================================
    private function saveContact($leadId, $name, $title, $email, $source)
    {
        $titleLower = strtolower($title);
        $campaignId = null;

        // 1. جلب جميع الحملات النشطة من قاعدة البيانات
        $activeCampaigns = \App\Models\Campaign::where('status', 'active')->get();

        // 2. محرك البحث الديناميكي (يطابق المسمى الوظيفي مع الكلمات المفتاحية للحملات)
        foreach ($activeCampaigns as $campaign) {
            // إذا لم يكن للحملة كلمات مفتاحية، نتجاوزها
            if (empty($campaign->target_role) || strtolower($campaign->target_role) === 'general') {
                continue;
            }

            // تحويل الكلمات المفتاحية في قاعدة البيانات (مثل: hr, it, developer) إلى مصفوفة
            $keywords = array_map('trim', explode(',', strtolower($campaign->target_role)));

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($titleLower, $keyword)) {
                    $campaignId = $campaign->id;
                    break 2; // خروج فوري من الحلقتين بمجرد إيجاد التطابق
                }
            }
        }

        // 3. الحملة الافتراضية (إذا لم يجد أي تطابق، ضعه في حملة عامة لكي لا نخسره)
        if (!$campaignId) {
            $defaultCampaign = \App\Models\Campaign::where('target_role', 'like', '%general%')->first();
            $campaignId = $defaultCampaign ? $defaultCampaign->id : null;
        }

        // 4. الحفظ في قاعدة البيانات
        \App\Models\Contact::updateOrCreate(
            ['email' => $email],
            [
                'lead_id' => $leadId,
                'campaign_id' => $campaignId, // تم توجيهه ديناميكياً!
                'full_name' => trim($name),
                'job_title' => $title,
                'source' => $source,
                'status' => 'new'
            ]
        );
    }
}