<?php

namespace App\Jobs;

use App\Models\Website;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebsiteAnalysisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Website $website
    ) {}

    public function handle(): void
    {
        Log::info("Starting website analysis for: {$this->website->url}");
        
        $this->website->update(['analysis_status' => 'analyzing_website']);

        try {
            // 1. Fetch basic website information
            $response = Http::timeout(30)->get($this->website->url);
            
            if (!$response->successful()) {
                throw new \Exception("Failed to fetch website: {$response->status()}");
            }

            $html = $response->body();
            
            // Extract basic information
            $basicInfo = $this->extractBasicInfo($html);
            
            // 2. Look for robots.txt and sitemap
            $robotsUrl = rtrim($this->website->url, '/') . '/robots.txt';
            $sitemapUrls = $this->findSitemapUrls($robotsUrl);
            
            // 3. Check for Danish CVR in content
            $cvrNumber = $this->extractCvrNumber($html);
            
            // Update website with basic info
            $this->website->update([
                'seo' => array_merge($this->website->seo ?? [], $basicInfo),
                'analysis_status' => 'dispatching_jobs'
            ]);

            // 4. Dispatch follow-up jobs
            if ($cvrNumber) {
                Log::info("Found CVR {$cvrNumber}, dispatching company lookup");
                CompanyLookupJob::dispatch($this->website, $cvrNumber)->delay(now()->addSeconds(2));
            }

            if (!empty($sitemapUrls)) {
                Log::info("Found sitemaps, dispatching sitemap analysis");
                SitemapAnalysisJob::dispatch($this->website, $sitemapUrls)->delay(now()->addSeconds(5));
            }

            // 5. Dispatch SEO analysis for main page
            SeoAnalysisJob::dispatch($this->website, [$this->website->url])->delay(now()->addSeconds(10));

            Log::info("Website analysis completed for: {$this->website->url}");

        } catch (\Exception $e) {
            Log::error("Website analysis failed: {$e->getMessage()}", [
                'website_id' => $this->website->id,
                'url' => $this->website->url
            ]);
            
            $this->website->update(['analysis_status' => 'failed']);
            throw $e;
        }
    }

    private function extractBasicInfo(string $html): array
    {
        $info = [];
        
        // Extract title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $info['title'] = trim(strip_tags($matches[1]));
        }
        
        // Extract meta description
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            $info['description'] = trim($matches[1]);
        }
        
        // Extract meta keywords
        if (preg_match('/<meta[^>]*name=["\']keywords["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            $info['keywords'] = trim($matches[1]);
        }
        
        // Count headings
        $info['headings'] = [];
        for ($i = 1; $i <= 6; $i++) {
            $count = preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $html);
            if ($count > 0) {
                $info['headings']["h{$i}"] = $count;
            }
        }
        
        // Count images with/without alt tags
        $imgCount = preg_match_all('/<img[^>]*>/i', $html);
        $imgWithAltCount = preg_match_all('/<img[^>]*alt=[^>]*>/i', $html);
        
        $info['images_total'] = $imgCount;
        $info['images_with_alt'] = $imgWithAltCount;
        
        return $info;
    }

    private function findSitemapUrls(string $robotsUrl): array
    {
        $sitemapUrls = [];
        
        try {
            $response = Http::timeout(10)->get($robotsUrl);
            
            if ($response->successful()) {
                $robotsContent = $response->body();
                
                // Find sitemap declarations in robots.txt
                preg_match_all('/Sitemap:\s*(https?:\/\/[^\s]+)/i', $robotsContent, $matches);
                
                if (!empty($matches[1])) {
                    $sitemapUrls = $matches[1];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch robots.txt: {$e->getMessage()}");
        }
        
        // Fallback: try common sitemap locations
        if (empty($sitemapUrls)) {
            $commonSitemaps = [
                rtrim($this->website->url, '/') . '/sitemap.xml',
                rtrim($this->website->url, '/') . '/sitemap_index.xml',
                rtrim($this->website->url, '/') . '/sitemaps/sitemap.xml',
            ];
            
            foreach ($commonSitemaps as $url) {
                try {
                    $response = Http::timeout(5)->head($url);
                    if ($response->successful()) {
                        $sitemapUrls[] = $url;
                        break; // Only add the first one we find
                    }
                } catch (\Exception $e) {
                    // Continue to next URL
                }
            }
        }
        
        return $sitemapUrls;
    }

    private function extractCvrNumber(string $html): ?string
    {
        // Danish CVR numbers are 8 digits
        // Look for patterns like "CVR: 12345678", "CVR-nr: 12345678", "CVR 12345678"
        $patterns = [
            '/CVR[:\-\s]*(\d{8})/i',
            '/CVR[- ]?nr[:\-\s]*(\d{8})/i',
            '/CVR[- ]?nummer[:\-\s]*(\d{8})/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}
