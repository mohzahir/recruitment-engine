<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Lead;

class FetchFacilitiesCommand extends Command
{
    // Usage: php artisan source:facilities "Private Hospital" "Riyadh"
    protected $signature = 'source:facilities {category} {city}';
    protected $description = 'Map physical medical facilities from Google Maps into the CRM.';

    public function handle()
    {
        $category = $this->argument('category');
        $city = $this->argument('city');
        $apiToken = env('APIFY_API_TOKEN');

        $this->info("========================================");
        $this->info(" MAPPING MARKET: {$category} in {$city}");
        $this->info("========================================");

        // FIX: Switched to the dedicated Google Maps Scraper!
        $runResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type' => 'application/json',
        ])->withoutVerifying()->post('https://api.apify.com/v2/acts/compass~crawler-google-places/runs', [
            // The Maps Actor uses specific JSON keys instead of a Dork
            'searchStringsArray' => [$category],
            'locationQuery' => $city,
            'maxCrawledPlacesPerSearch' => 100, // Tell it to grab the first 100 clinics it finds
            'language' => 'en',
            'skipClosedPlaces' => true, // Don't scrape permanently closed hospitals
        ]);

        if (!$runResponse->successful()) {
            $this->error("Apify Request Failed: " . $runResponse->body());
            return;
        }

        $runId = $runResponse->json()['data']['id'];
        $this->output->write("Scanning the physical map (This might take a minute or two)...");

        $datasetId = $this->waitForApify($runId, $apiToken);
        $this->info("\nMap Scan Complete! Processing facilities...");

        // The Maps Actor returns a direct array of businesses, not a nested search result
        $facilities = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
        ])->withoutVerifying()->get("https://api.apify.com/v2/datasets/{$datasetId}/items")->json();

        $savedCount = 0;

        foreach ($facilities as $place) {
            $name = trim($place['title'] ?? '');
            
            if (empty($name) || strlen($name) < 3) continue;

            // Duplicate Shield
            if (Lead::where('company', $name)->where('location', $city)->exists()) {
                $this->warn("Skipped (Already in CRM): {$name}");
                continue;
            }

            // Extract beautiful, structured Maps data
            $website = $place['website'] ?? 'No Website';
            $phone = $place['phone'] ?? $place['phoneUnformatted'] ?? 'No Phone';
            $address = $place['address'] ?? 'No Address';
            $mapsUrl = $place['url'] ?? 'N/A';

            // ==========================================
            // FIX: UPGRADED DUPLICATE SHIELD
            // ==========================================
            // Checks if the clinic name exists, OR if we already saved this exact map pin
            if (Lead::where('company', $name)->where('location', $city)->exists() || 
                Lead::where('source_url', $mapsUrl)->exists()) {
                $this->warn("Skipped (Already in CRM): {$name}");
                continue;
            }

            Lead::create([
                'company' => $name,
                'job_title' => 'Direct Facility Lead', 
                'location' => $city,
                'source' => 'google_maps',
                'industry' => 'Healthcare',
                'role_type' => 'Medical Facility',
                'status' => 'New',
                'platform' => 'Google Maps', 
                'is_email_verified' => false, 
                // ==========================================
                // FIX: GUARANTEE UNIQUENESS
                // ==========================================
                'source_url' => $mapsUrl, // Every map pin is unique, so the DB will never crash!
                'job_description' => "Website: {$website} \nPhone: {$phone} \nAddress: {$address}",
            ]);

            $savedCount++;
            $this->info("SUCCESS: Mapped -> {$name}");
        }

        $this->info("\n========================================");
        $this->info(" MARKET MAPPING COMPLETE: {$savedCount} new facilities added.");
    }

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