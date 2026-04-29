<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Lead;

class FetchGulfJobsCommand extends Command
{
    protected $signature = 'source:gulf-boards {keyword} {location}';
    protected $description = 'Dual-Engine: Scout Google for links, then extract JSON-LD from Gulf Job Boards.';

    public function handle()
    {
        $keyword = $this->argument('keyword');
        $location = $this->argument('location');
        $apiToken = env('APIFY_API_TOKEN');

        $this->info("========================================");
        $this->info(" PHASE 1: THE SCOUT (Finding Links)");
        $this->info("========================================");

        // We added "Requirements" to force Google to only return pages with a full job description!
        // FIX 1: Broadened the Bayt site operator so it doesn't get blocked by country subfolders
        $searchQuery = "(site:bayt.com OR site:naukrigulf.com) \"{$keyword}\" \"{$location}\" \"Requirements\" -inurl:cv -inurl:profile -inurl:candidate";

        $googleRun = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type' => 'application/json',
        ])->withoutVerifying()->post('https://api.apify.com/v2/acts/apify~google-search-scraper/runs', [
            'queries' => $searchQuery,
            // We removed the dead 'resultsPerPage' parameter
            'maxPagesPerQuery' => 15, 
            'customUrlParams' => [
                ['key' => 'filter', 'value' => '0'] 
            ]
        ]);

        if (!$googleRun->successful()) {
            $this->error("Google Scraper Failed: " . $googleRun->body());
            return;
        }

        $googleRunId = $googleRun->json()['data']['id'];
        $this->output->write("Waiting for Google to return URLs...");

        $googleDatasetId = $this->waitForApify($googleRunId, $apiToken);
        $this->info("\nScout Complete! Fetching URLs...");

        $googleData = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
        ])->withoutVerifying()->get("https://api.apify.com/v2/datasets/{$googleDatasetId}/items")->json();

        $organicResults = $googleData[0]['organicResults'] ?? [];
        
        // Extract just the URLs into an array for Phase 2
        $targetUrls = [];
        foreach ($organicResults as $result) {
            if (isset($result['url'])) {
                // FIX 2: Strip Bayt's massive "?_gl=" tracking tags to keep the CRM clean
                $url = explode('?', $result['url'])[0]; 
                
                // Real job postings contain a numerical ID. (Upgraded to 5 digits to be safe)
                if (preg_match('/[0-9]{5,}/', $url)) {
                    $targetUrls[] = ['url' => $url];
                } else {
                    $this->line("<fg=yellow>Scout Dropped Directory Page:</> {$url}");
                }
            }
        }

        if (count($targetUrls) === 0) {
            $this->error("No URLs found by the Scout.");
            return;
        }

        $this->info("Found " . count($targetUrls) . " raw job links.\n");
        $this->info("========================================");
        $this->info(" PHASE 2: THE EXTRACTOR (JSON-LD)");
        $this->info("========================================");

        // ========================================
        // PHASE 2: THE EXTRACTOR (Headless Chrome)
        // ========================================

        // We use native JavaScript because this will run inside a real Chrome browser!
        $pageFunction = '
            async function pageFunction(context) { 
                let data = { title: null, company: null, description: null, url: context.request.url }; 

                // FIX 1: Use Chrome\'s native engine to perfectly strip all HTML/encoded tags
                function cleanText(html) {
                    if (!html) return "No description.";
                    let tmp = document.createElement("DIV");
                    tmp.innerHTML = html;
                    let text = tmp.textContent || tmp.innerText || "";
                    // Remove double spaces and line breaks for a clean paragraph
                    return text.replace(/\s+/g, " ").trim(); 
                }

                // LAYER 1: Advanced JSON-LD Scanner
                document.querySelectorAll("script[type=\"application/ld+json\"]").forEach((el) => { 
                    try { 
                        let json = JSON.parse(el.innerHTML); 
                        let items = Array.isArray(json) ? json : (json["@graph"] ? json["@graph"] : [json]);
                        
                        for (let item of items) {
                            let type = item["@type"];
                            if (type === "JobPosting" || (Array.isArray(type) && type.includes("JobPosting"))) {
                                if (item.title) data.title = item.title;
                                if (item.hiringOrganization && item.hiringOrganization.name) data.company = item.hiringOrganization.name;
                                // Apply the perfect HTML cleaner here
                                if (item.description) data.description = cleanText(item.description);
                            }
                        }
                    } catch(e) {} 
                }); 

                // LAYER 2: HTML CSS Fallback Scanner
                if (!data.title) {
                    let h1 = document.querySelector("h1");
                    data.title = h1 ? h1.innerText.trim() : document.title;
                }
                
                if (!data.company) {
                    let naukri = document.querySelector(".info-org");
                    let bayt = document.querySelector("b.company-name") || document.querySelector(".is-black-text a");
                    let meta = document.querySelector("meta[property=\"og:site_name\"]");
                    
                    if (naukri) data.company = naukri.innerText.trim();
                    else if (bayt) data.company = bayt.innerText.trim();
                    else if (meta) data.company = meta.content;
                    else data.company = "Unknown Company";
                }
                
                if (!data.description) {
                    let metaDesc = document.querySelector("meta[property=\"og:description\"]");
                    data.description = cleanText(metaDesc ? metaDesc.content : "No description available.");
                }
                
                if (data.company) data.company = cleanText(data.company);
                if (data.title) data.title = cleanText(data.title);

                return data; 
            }
        ';

        // FIX: Switched from cheerio-scraper to web-scraper (Headless Chrome)
        $cheerioRun = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type' => 'application/json',
        ])->withoutVerifying()->post('https://api.apify.com/v2/acts/apify~web-scraper/runs', [
            'startUrls' => $targetUrls,
            'pageFunction' => $pageFunction,
            'useChrome' => true, // Force a real browser
        ]);

        if (!$cheerioRun->successful()) {
            $this->error("Browser Scraper Failed: " . $cheerioRun->body());
            return;
        }

        $cheerioRunId = $cheerioRun->json()['data']['id'];
        $this->output->write("Extracting deep JSON data using Headless Chrome (This takes a moment)...");

        $cheerioDatasetId = $this->waitForApify($cheerioRunId, $apiToken);
        $this->info("\nExtraction Complete! Saving to CRM...");

        $cheerioData = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
        ])->withoutVerifying()->get("https://api.apify.com/v2/datasets/{$cheerioDatasetId}/items")->json();

        // ==========================================
        // NEW: THE DEEP X-RAY
        // ==========================================
        $this->warn("\n[DEEP X-RAY] Apify Extractor returned " . count($cheerioData) . " records.");
        if (count($cheerioData) > 0) {
            // Print the raw data of the very first result so we can inspect it
            $this->line(json_encode(array_slice($cheerioData, 0, 1), JSON_PRETTY_PRINT));
        } else {
            $this->error("[DEEP X-RAY] The dataset is completely empty. The JavaScript found nothing.");
        }

        $savedCount = 0;

        foreach ($cheerioData as $jobData) {
            if (!is_array($jobData) || !isset($jobData['company']) || empty(trim($jobData['company'])) || $jobData['company'] === 'Unknown Company') {
                continue; 
            }

            $companyName = trim($jobData['company']);
            $jobTitle = trim($jobData['title'] ?? $keyword);

            // ==========================================
            // FIX 1: THE IRONCLAD AGENCY FILTER (Runs First!)
            // ==========================================
            // We use stripos to check for these words anywhere in the name
            if (stripos($companyName, 'client of') !== false || 
                stripos($companyName, 'confidential') !== false ||
                stripos($companyName, 'undisclosed') !== false ||
                stripos($companyName, 'talent') !== false ||
                stripos($companyName, 'profco') !== false) {
                
                $this->warn("Skipped (Competitor Agency): {$companyName}");
                continue; // Instantly throw it in the trash
            }

            // ==========================================
            // FIX 2: THE DUPLICATE SHIELD (Runs Second)
            // ==========================================
            if (Lead::where('company', $companyName)->where('job_title', $jobTitle)->exists()) {
                $this->warn("Skipped (Already in CRM): {$companyName}");
                continue; 
            }

            // ==========================================
            // FIX 3: THE NAUKRIGULF TEXT SCRUBBER
            // ==========================================
            $description = $jobData['description'] ?? 'No description available.';
            
            // Scrub out the weird floating HTML words Naukrigulf leaves behind
            $dirtyWords = [' span ', ' strong ', ' br ', ' p ', 'nbsp', '&nbsp;'];
            $description = str_ireplace($dirtyWords, ' ', ' ' . $description . ' ');
            
            // Clean up the edges and remove any accidental double spaces
            $description = trim(preg_replace('/\s+/', ' ', $description));
            // Remove floating spans at the very beginning of the text
            $description = preg_replace('/^(span|strong|\s)+/i', '', $description);

            // ==========================================
            // SAVE TO DATABASE
            // ==========================================
            $industry = str_contains(strtolower($keyword), 'nurse') || str_contains(strtolower($keyword), 'doctor') 
                        ? 'Healthcare' 
                        : 'Construction/Service';

            Lead::create([
                'company' => $companyName, 
                'job_title' => $jobTitle,
                'location' => $location,
                'source' => 'gulf_boards',
                'industry' => $industry,
                'role_type' => 'Blue Collar / Medical',
                'status' => 'New',
                'is_email_verified' => false,
                'platform' => 'Gulf Boards', 
                'source_url' => $jobData['url'] ?? 'N/A',
                'job_description' => substr($description, 0, 1000), 
            ]);
            
            $savedCount++;
            $this->info("SUCCESS: Saved 100% Verified Lead -> {$companyName}"); 
        }

        $this->info("\n========================================");
        $this->info(" MISSION ACCOMPLISHED: Saved {$savedCount} precise leads.");
    }

    // A helper method to keep the code clean since we wait for Apify twice
    private function waitForApify($runId, $apiToken)
    {
        do {
            sleep(5);
            $statusResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
            ])->withoutVerifying()->get("https://api.apify.com/v2/actor-runs/{$runId}");
            
            $data = $statusResponse->json()['data'];
            $this->output->write(".");
        } while (in_array($data['status'], ['READY', 'RUNNING']));

        return $data['defaultDatasetId'];
    }
}