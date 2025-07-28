<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\Website;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

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
            // Option 1: Use Google PageSpeed Insights API (free with quota)
            $data = $this->usePageSpeedInsights($url);
            
            if ($data) {
                return $data;
            }
            
            // Option 2: Use a local Lighthouse installation (if available)
            return $this->useLocalLighthouse($url);
            
        } catch (\Exception $e) {
            Log::error("Lighthouse analysis failed for {$url}: {$e->getMessage()}");
            return null;
        }
    }

    private function usePageSpeedInsights(string $url): ?array
    {
        try {
            // Using Google PageSpeed Insights API
            $apiKey = config('services.google.pagespeed_api_key'); // Add this to your config
            
            if (!$apiKey) {
                Log::warning("No Google PageSpeed API key configured");
                return null;
            }

            $response = Http::timeout(60)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'url' => $url,
                'key' => $apiKey,
                'category' => ['PERFORMANCE', 'ACCESSIBILITY', 'BEST_PRACTICES', 'SEO'],
                'strategy' => 'DESKTOP'
            ]);

            if (!$response->successful()) {
                Log::warning("PageSpeed API returned status: {$response->status()}");
                return null;
            }

            $data = $response->json();
            
            if (isset($data['error'])) {
                Log::warning("PageSpeed API error: " . json_encode($data['error']));
                return null;
            }

            return $this->parsePageSpeedData($data);

        } catch (\Exception $e) {
            Log::error("PageSpeed Insights API error: {$e->getMessage()}");
            return null;
        }
    }

    private function useLocalLighthouse(string $url): ?array
    {
        try {
            // Check if Lighthouse CLI is available
            $result = Process::run('lighthouse --version');
            
            if (!$result->successful()) {
                Log::info("Lighthouse CLI not available, skipping local analysis");
                return null;
            }

            // Run Lighthouse analysis
            $tempFile = tempnam(sys_get_temp_dir(), 'lighthouse_') . '.json';
            
            $command = "lighthouse {$url} --output=json --output-path={$tempFile} --chrome-flags=\"--headless --no-sandbox\" --quiet";
            
            $result = Process::timeout(120)->run($command);
            
            if (!$result->successful()) {
                Log::warning("Lighthouse CLI failed: {$result->errorOutput()}");
                return null;
            }

            if (!file_exists($tempFile)) {
                Log::warning("Lighthouse output file not found");
                return null;
            }

            $lighthouseData = json_decode(file_get_contents($tempFile), true);
            unlink($tempFile);

            return $this->parseLighthouseData($lighthouseData);

        } catch (\Exception $e) {
            Log::error("Local Lighthouse error: {$e->getMessage()}");
            return null;
        }
    }

    private function parsePageSpeedData(array $data): array
    {
        $categories = $data['lighthouseResult']['categories'] ?? [];
        
        return [
            'performance' => round(($categories['performance']['score'] ?? 0) * 100),
            'accessibility' => round(($categories['accessibility']['score'] ?? 0) * 100),
            'best-practices' => round(($categories['best-practices']['score'] ?? 0) * 100),
            'seo' => round(($categories['seo']['score'] ?? 0) * 100),
            'metrics' => $this->extractMetrics($data['lighthouseResult']['audits'] ?? []),
            'source' => 'pagespeed_insights',
            'analyzed_at' => now()->toISOString(),
        ];
    }

    private function parseLighthouseData(array $data): array
    {
        $categories = $data['categories'] ?? [];
        
        return [
            'performance' => round(($categories['performance']['score'] ?? 0) * 100),
            'accessibility' => round(($categories['accessibility']['score'] ?? 0) * 100),
            'best-practices' => round(($categories['best-practices']['score'] ?? 0) * 100),
            'seo' => round(($categories['seo']['score'] ?? 0) * 100),
            'metrics' => $this->extractMetrics($data['audits'] ?? []),
            'source' => 'lighthouse_cli',
            'analyzed_at' => now()->toISOString(),
        ];
    }

    private function extractMetrics(array $audits): array
    {
        $metrics = [];
        
        $metricKeys = [
            'first-contentful-paint' => 'first-contentful-paint',
            'largest-contentful-paint' => 'largest-contentful-paint',
            'cumulative-layout-shift' => 'cumulative-layout-shift',
            'speed-index' => 'speed-index',
            'total-blocking-time' => 'total-blocking-time',
        ];

        foreach ($metricKeys as $key => $displayKey) {
            if (isset($audits[$key]['displayValue'])) {
                $metrics[$displayKey] = $audits[$key]['displayValue'];
            } elseif (isset($audits[$key]['numericValue'])) {
                $value = $audits[$key]['numericValue'];
                
                // Convert milliseconds to seconds for time-based metrics
                if (in_array($key, ['first-contentful-paint', 'largest-contentful-paint', 'speed-index', 'total-blocking-time'])) {
                    $metrics[$displayKey] = round($value / 1000, 2);
                } else {
                    $metrics[$displayKey] = round($value, 3);
                }
            }
        }

        return $metrics;
    }

    private function calculateAverageScores(array $allScores): array
    {
        $totals = [
            'performance' => 0,
            'accessibility' => 0,
            'best-practices' => 0,
            'seo' => 0,
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
            'pages_analyzed' => $count,
            'last_analyzed' => now()->toISOString(),
        ];
    }
}
