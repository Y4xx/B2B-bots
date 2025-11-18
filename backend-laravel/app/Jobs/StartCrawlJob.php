<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\CrawlJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StartCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    protected $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    public function handle(): void
    {
        // Create crawl job record
        $crawlJob = CrawlJob::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'started_at' => now(),
            'config' => $this->site->crawl_config,
        ]);

        $this->site->update(['status' => 'crawling']);

        try {
            // Call Python scraper service
            $response = Http::timeout(30)->post(
                config('services.python.scraper_url') . '/scrape',
                [
                    'domain' => 'https://' . $this->site->domain,
                    'tenant_id' => $this->site->tenant_id,
                    'site_id' => $this->site->id,
                    'callback_url' => url('/api/webhooks/scraper'),
                    'config' => $this->site->crawl_config,
                ]
            );

            if ($response->successful()) {
                Log::info("Crawl started for site {$this->site->id}");
            } else {
                throw new \Exception("Scraper service returned error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to start crawl for site {$this->site->id}: " . $e->getMessage());

            $this->site->update([
                'status' => 'failed',
                'error_message' => 'Failed to start crawl: ' . $e->getMessage(),
            ]);

            $crawlJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
