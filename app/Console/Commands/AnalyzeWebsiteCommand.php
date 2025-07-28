<?php

namespace App\Console\Commands;

use App\Models\Website;
use Illuminate\Console\Command;

class AnalyzeWebsiteCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'website:analyze {url} {--sync : Run analysis synchronously for testing}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze a website with full analysis chain (Company, Sitemap, Lighthouse, SEO)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $sync = $this->option('sync');
        
        $this->info("Starting website analysis for: {$url}");
        
        // Find or create website
        $website = Website::where('url', $url)->first();
        
        if (!$website) {
            $this->info("Creating new website record...");
            $website = Website::create([
                'url' => $url,
                'analysis_status' => 'queued'
            ]);
            $this->info("Created website with ID: {$website->id}");
        } else {
            $this->info("Found existing website with ID: {$website->id}");
        }
        
        // Trigger analysis
        if ($sync) {
            $this->info("Running analysis synchronously...");
            $website->runAnalysisSync();
            $this->info("✅ Synchronous analysis completed!");
        } else {
            $this->info("Dispatching analysis to queue...");
            $website->runAnalysis();
            $this->info("✅ Analysis jobs dispatched to queue!");
        }
        
        $this->newLine();
        $this->info("Website analysis initiated for: {$website->url}");
        $this->info("Dashboard URL: /dashboard/{$website->slug}");
        
        return Command::SUCCESS;
    }
}
