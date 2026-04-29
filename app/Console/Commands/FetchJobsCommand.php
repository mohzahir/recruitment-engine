<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Lead;

class FetchJobsCommand extends Command
{
    protected $signature = 'jobs:fetch {keyword?} {location?}';
    protected $description = 'Fetch job postings from Apify and store them as leads.';

    public function handle()
    {
        $keyword = $this->argument('keyword') ?? 'Software Engineer';
        $location = $this->argument('location') ?? 'Dubai';

        $this->info("Starting job fetch for '{$keyword}' in '{$location}' via Apify...");

        $apiToken = env('APIFY_API_TOKEN');
        $rawActorId = env('APIFY_LINKEDIN_ACTOR_ID');
        $actorId = str_replace('/', '~', $rawActorId);

        $searchUrl = "https://www.linkedin.com/jobs/search/?keywords=" . urlencode($keyword) . "&location=" . urlencode($location);

        $inputPayload = [
            'urls' => [$searchUrl],
            'maxItems' => 5, 
            'limit' => 5,    
        ];

        // 1. START THE RUN (Asynchronous)
        $runEndpoint = "https://api.apify.com/v2/acts/{$actorId}/runs";
        
        $response = Http::withToken($apiToken)
            ->withoutVerifying()
            ->post($runEndpoint, $inputPayload);

        if ($response->failed()) {
            $this->error("Failed to start the Apify run.");
            $this->error($response->body());
            return;
        }

        $runData = $response->json()['data'];
        $runId = $runData['id'];
        $datasetId = $runData['defaultDatasetId'];

        $this->info("Scraper started successfully! Run ID: {$runId}");
        $this->info("Waiting for it to finish (this may take a few minutes)...");

        // 2. WAIT FOR IT TO FINISH (The Polling Loop)
        $status = $runData['status'];
        
        while (!in_array($status, ['SUCCEEDED', 'FAILED', 'ABORTED', 'TIMED-OUT'])) {
            sleep(10); // Wait 10 seconds before asking again
            $this->output->write('.'); 
            
            try {
                // We add retry(3, 2000) so if a connection fails, it automatically 
                // tries 3 more times (waiting 2 seconds between tries) before throwing an error.
                $statusResponse = Http::withToken($apiToken)
                    ->withoutVerifying()
                    ->timeout(20) 
                    ->retry(3, 2000) 
                    ->get("https://api.apify.com/v2/actor-runs/{$runId}");
                    
                $status = $statusResponse->json()['data']['status'];
            } catch (\Exception $e) {
                // If all retries fail (e.g., your internet goes down temporarily), 
                // we print an exclamation mark and just keep the loop going instead of crashing.
                $this->output->write('!'); 
                continue; 
            }
        }

        $this->line(''); // Add a new line after the dots finish

        if ($status !== 'SUCCEEDED') {
            $this->error("The scrape stopped with status: {$status}");
            return;
        }

        $this->info("Scrape finished! Downloading data...");

        // 3. FETCH THE DATA
        $datasetEndpoint = "https://api.apify.com/v2/datasets/{$datasetId}/items";
        $itemsResponse = Http::withToken($apiToken)
            ->withoutVerifying()
            ->get($datasetEndpoint);

        $jobs = $itemsResponse->json();

        // 4. SAVE THE DATA TO THE DATABASE
        $count = 0;

        foreach ($jobs as $job) {
            // updateOrCreate prevents duplicate entries if you scrape the same job twice
            Lead::updateOrCreate(
                ['source_url' => $job['link']], // This is the unique identifier
                [
                    'platform' => 'linkedin',
                    'company' => $job['companyName'] ?? 'Unknown Company',
                    'job_title' => $job['title'] ?? 'Unknown Title',
                    'location' => $job['location'] ?? null,
                    'job_description' => $job['descriptionText'] ?? null,
                    // Note: 'publisher_name' is not provided by this specific scraper, 
                    // so we leave it blank. Email and Phone will be filled by Apollo later.
                ]
            );
            $count++;
        }

        $this->info("Success! Imported {$count} jobs into the database.");
    }
}