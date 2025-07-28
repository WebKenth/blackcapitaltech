<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\Website;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Lighthouse\Lighthouse;

class LighthouseAnalysisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Website $website,
        public array $urls
    ) {}

    public function handle(): void
    {
        Log::info("Starting Lighthouse analysis for: {$this->website->url}");
        
        $this->website->update(['analysis_status' => 'analyzing_lighthouse']);

        try {
            $allScores = [];
            
            foreach ($this->urls as $url) {
                $lighthouseData = $this->runLighthouseAnalysis($url);
                
                if ($lighthouseData) {
                    // Update the Page record
                    $page = Page::where('website_id', $this->website->id)
                               ->where('url', $url)
                               ->first();
                    
                    if ($page) {
                        $page->update([
                            'lighthouse' => $lighthouseData,
                            'is_analyzed' => true,
                            'analyzed_at' => now()
                        ]);
                    }
                    
                    $allScores[] = $lighthouseData;
                }
                
                // Rate limiting - wait between requests
                sleep(2);
            }

            // Calculate average scores for the website
            if (!empty($allScores)) {
                $averageScores = $this->calculateAverageScores($allScores);
                
                $this->website->update([
                    'lighthouse' => $averageScores,
                    'analysis_status' => 'lighthouse_analyzed'
                ]);
            }

            Log::info("Lighthouse analysis completed for: {$this->website->url}");

        } catch (\Exception $e) {
            Log::error("Lighthouse analysis failed: {$e->getMessage()}", [
                'website_id' => $this->website->id,
                'urls' => $this->urls
            ]);
            
            $this->website->update(['analysis_status' => 'lighthouse_failed']);
        }
    }

    private function runLighthouseAnalysis(string $url): ?array
    {
        try {
            Log::info("Running Lighthouse analysis for: {$url}");
            
            $result = Lighthouse::url($url)->run();
            
            $scores = $result->scores();
            
            return [
                'performance' => $scores['performance'] ?? 0,
                'accessibility' => $scores['accessibility'] ?? 0,
                'best-practices' => $scores['best-practices'] ?? 0,
                'seo' => $scores['seo'] ?? 0,
                'pwa' => $scores['pwa'] ?? 0,
                'source' => 'spatie_lighthouse',
                'analyzed_at' => now()->toISOString(),
            ];
            
        } catch (\Exception $e) {
            Log::error("Lighthouse analysis failed for {$url}: {$e->getMessage()}");
            return null;
        }
    }

    private function calculateAverageScores(array $allScores): array
    {
        $totals = [
            'performance' => 0,
            'accessibility' => 0,
            'best-practices' => 0,
            'seo' => 0,
            'pwa' => 0,
        ];
        
        $count = count($allScores);
        
        foreach ($allScores as $scores) {
            foreach ($totals as $key => $total) {
                $totals[$key] += $scores[$key] ?? 0;
            }
        }
        
        return [
            'performance' => round($totals['performance'] / $count),
            'accessibility' => round($totals['accessibility'] / $count),
            'best-practices' => round($totals['best-practices'] / $count),
            'seo' => round($totals['seo'] / $count),
            'pwa' => round($totals['pwa'] / $count),
            'pages_analyzed' => $count,
            'last_analyzed' => now()->toISOString(),
        ];
    }
}
