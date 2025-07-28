<?php

namespace App\Jobs;

use App\Jobs\LighthouseAnalysisJob;
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

            foreach ($this->sitemapUrls as $sitemapUrl) {
                $urls = $this->parseSitemap($sitemapUrl);
                $allUrls = array_merge($allUrls, $urls);
            }

            $allUrls = array_unique($allUrls);
            
            $categorizedUrls = $this->categorizeUrls($allUrls);
            
            $sitemapData = [
                'total_pages' => count($allUrls),
                'categories' => $categorizedUrls,
                'last_analyzed' => now()->toISOString(),
            ];

            $this->website->update([
                'sitemap_data' => $sitemapData,
                'analysis_status' => 'sitemap_analyzed'
            ]);

            $selectedUrls = $this->selectRandomUrls($allUrls, 25);
            
            foreach ($selectedUrls as $url) {
                $category = $this->determineUrlCategory($url);
                
                Page::updateOrCreate(
                    ['website_id' => $this->website->id, 'url' => $url],
                    ['type' => $category]
                );
            }

            if (!empty($selectedUrls)) {
                Log::info("Dispatching Lighthouse analysis for " . count($selectedUrls) . " pages");
                LighthouseAnalysisJob::dispatch($this->website, $selectedUrls)->delay(now()->addSeconds(10));
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

            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemap) {
                    $subSitemapUrl = (string) $sitemap->loc;
                    $subUrls = $this->parseSitemap($subSitemapUrl);
                    $urls = array_merge($urls, $subUrls);
                }
            }
            
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
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }
        
        return $categories;
    }

    private function determineUrlCategory(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $path = strtolower(rtrim($path, '/'));
        
        if (preg_match('/\/(docs|documentation|guide|api)/', $path)) {
            return 'documentation';
        }
        
        if (preg_match('/\/(products?|items?|shop)/', $path)) {
            return 'product';
        }
        
        if (preg_match('/\/(collections?|categories?|catalog)/', $path)) {
            return 'collection';
        }
        
        if (preg_match('/\/(blogs?|news|articles?|posts?)/', $path)) {
            return 'blog';
        }
        
        if (preg_match('/\/(about|contact|privacy|terms|faq|help|support)/', $path)) {
            return 'page';
        }
        
        if (preg_match('/\/(tags?|tagged)/', $path)) {
            return 'tag';
        }
        
        if (preg_match('/\/(search|results)/', $path)) {
            return 'search';
        }
        
        if ($path === '' || $path === '/') {
            return 'homepage';
        }
        
        if (preg_match('/\.(pdf|doc|docx|jpg|jpeg|png|gif|svg)$/i', $path)) {
            return 'file';
        }
        
        if (preg_match('/\/api\//', $path)) {
            return 'api';
        }
        
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
}
