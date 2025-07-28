<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\Website;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SitemapAnalysisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Website $website,
        public array $sitemapUrls
    ) {}

    public function handle(): void
    {
        Log::info("Starting sitemap analysis for: {$this->website->url}");
        
        $this->website->update(['analysis_status' => 'analyzing_sitemap']);

        try {
            $allUrls = [];
            $sitemapData = [
                'total_pages' => 0,
                'categories' => [],
                'last_analyzed' => now()->toISOString(),
            ];

            foreach ($this->sitemapUrls as $sitemapUrl) {
                $urls = $this->parseSitemap($sitemapUrl);
                $allUrls = array_merge($allUrls, $urls);
            }

            // Remove duplicates
            $allUrls = array_unique($allUrls);
            
            // Categorize URLs
            $categorizedUrls = $this->categorizeUrls($allUrls);
            
            // Update sitemap data
            $sitemapData['total_pages'] = count($allUrls);
            $sitemapData['categories'] = $categorizedUrls;
            
            $this->website->update([
                'sitemap_data' => $sitemapData,
                'analysis_status' => 'sitemap_analyzed'
            ]);

            // Select random pages for detailed analysis (max 25)
            $selectedUrls = $this->selectRandomUrls($allUrls, 25);
            
            // Create Page records
            foreach ($selectedUrls as $url) {
                $category = $this->determineUrlCategory($url);
                
                Page::updateOrCreate(
                    ['website_id' => $this->website->id, 'url' => $url],
                    ['type' => $category]
                );
            }

            // Dispatch lighthouse analysis for selected pages
            if (!empty($selectedUrls)) {
                Log::info("Dispatching Lighthouse analysis for " . count($selectedUrls) . " pages");
                LighthouseAnalysisJob::dispatch($this->website, $selectedUrls)->delay(now()->addSeconds(10));
            }

            // Dispatch SEO analysis for sample pages from each category
            $seoUrls = $this->selectSeoSampleUrls($categorizedUrls);
            if (!empty($seoUrls)) {
                Log::info("Dispatching SEO analysis for " . count($seoUrls) . " sample pages");
                SeoAnalysisJob::dispatch($this->website, $seoUrls)->delay(now()->addSeconds(15));
            }

            Log::info("Sitemap analysis completed for: {$this->website->url}");

        } catch (\Exception $e) {
            Log::error("Sitemap analysis failed: {$e->getMessage()}", [
                'website_id' => $this->website->id,
                'sitemap_urls' => $this->sitemapUrls
            ]);
            
            $this->website->update(['analysis_status' => 'sitemap_failed']);
            throw $e;
        }
    }

    private function parseSitemap(string $sitemapUrl): array
    {
        $urls = [];
        
        try {
            $response = Http::timeout(30)->get($sitemapUrl);
            
            if (!$response->successful()) {
                Log::warning("Failed to fetch sitemap: {$sitemapUrl}");
                return $urls;
            }

            $xml = simplexml_load_string($response->body());
            
            if ($xml === false) {
                Log::warning("Failed to parse XML from: {$sitemapUrl}");
                return $urls;
            }

            // Handle sitemap index
            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemap) {
                    $subSitemapUrl = (string) $sitemap->loc;
                    $subUrls = $this->parseSitemap($subSitemapUrl);
                    $urls = array_merge($urls, $subUrls);
                }
            }
            
            // Handle regular sitemap
            if (isset($xml->url)) {
                foreach ($xml->url as $url) {
                    $urls[] = (string) $url->loc;
                }
            }

        } catch (\Exception $e) {
            Log::error("Error parsing sitemap {$sitemapUrl}: {$e->getMessage()}");
        }
        
        return $urls;
    }

    private function categorizeUrls(array $urls): array
    {
        $categories = [];
        
        foreach ($urls as $url) {
            $category = $this->determineUrlCategory($url);
            
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            
            $categories[$category][] = $url;
        }
        
        // Convert to counts
        $categoryCounts = [];
        foreach ($categories as $category => $urls) {
            $categoryCounts[$category] = count($urls);
        }
        
        return $categoryCounts;
    }

    private function determineUrlCategory(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $path = strtolower($path);
        
        // Common e-commerce patterns
        if (preg_match('/\/(products?|items?|shop)\//', $path)) {
            return 'product';
        }
        
        if (preg_match('/\/(collections?|categories?|catalog)\//', $path)) {
            return 'collection';
        }
        
        if (preg_match('/\/(blogs?|news|articles?)\//', $path)) {
            return 'blog';
        }
        
        if (preg_match('/\/(pages?|about|contact|privacy|terms)\//', $path)) {
            return 'page';
        }
        
        // Check for common patterns
        if (preg_match('/\/tag\//', $path)) {
            return 'tag';
        }
        
        if (preg_match('/\/search\//', $path)) {
            return 'search';
        }
        
        // Default to page if no specific pattern is found
        return 'page';
    }

    private function selectRandomUrls(array $urls, int $maxCount): array
    {
        if (count($urls) <= $maxCount) {
            return $urls;
        }
        
        $keys = array_rand($urls, $maxCount);
        
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        return array_map(fn($key) => $urls[$key], $keys);
    }

    private function selectSeoSampleUrls(array $categorizedUrls): array
    {
        $sampleUrls = [];
        
        // Get up to 5 URLs from each category for SEO analysis
        foreach ($categorizedUrls as $category => $count) {
            $categoryUrls = array_filter($this->getAllUrlsForCategory($category), fn($url) => !empty($url));
            
            if (empty($categoryUrls)) {
                continue;
            }
            
            $sampleCount = min(5, count($categoryUrls));
            $selectedKeys = array_rand($categoryUrls, $sampleCount);
            
            if (!is_array($selectedKeys)) {
                $selectedKeys = [$selectedKeys];
            }
            
            foreach ($selectedKeys as $key) {
                $sampleUrls[] = $categoryUrls[$key];
            }
        }
        
        return $sampleUrls;
    }

    private function getAllUrlsForCategory(string $category): array
    {
        // This would need to store URLs by category during processing
        // For now, return empty array as this is a simplified implementation
        return [];
    }
}
