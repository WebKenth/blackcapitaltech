<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\Website;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeoAnalysisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Website $website,
        public array $urls
    ) {}

    public function handle(): void
    {
        Log::info("Starting SEO analysis for: {$this->website->url}");
        
        $this->website->update(['analysis_status' => 'analyzing_seo']);

        try {
            // Perform Google site search to find top performing pages
            $topPages = $this->performGoogleSiteSearch();
            
            // Analyze individual pages
            foreach ($this->urls as $url) {
                $seoData = $this->analyzePage($url);
                
                if ($seoData) {
                    // Update the Page record if it exists
                    $page = Page::where('website_id', $this->website->id)
                               ->where('url', $url)
                               ->first();
                    
                    if ($page) {
                        $page->update([
                            'seo' => $seoData,
                            'title' => $seoData['title'] ?? null,
                            'description' => $seoData['description'] ?? null,
                        ]);
                    }
                }
                
                // Rate limiting
                sleep(1);
            }

            // Update website SEO data
            $websiteSeoData = array_merge($this->website->seo ?? [], [
                'top_pages' => $topPages,
                'analyzed_pages' => count($this->urls),
                'last_seo_analysis' => now()->toISOString(),
            ]);

            $this->website->update([
                'seo' => $websiteSeoData,
                'analysis_status' => 'seo_analyzed'
            ]);

            Log::info("SEO analysis completed for: {$this->website->url}");

        } catch (\Exception $e) {
            Log::error("SEO analysis failed: {$e->getMessage()}", [
                'website_id' => $this->website->id,
                'urls' => $this->urls
            ]);
            
            $this->website->update(['analysis_status' => 'seo_failed']);
        }
    }

    private function performGoogleSiteSearch(): array
    {
        try {
            $domain = parse_url($this->website->url, PHP_URL_HOST);
            $searchQuery = "site:{$domain}";
            
            // Using Google Custom Search API (if configured)
            $apiKey = config('services.google.search_api_key');
            $searchEngineId = config('services.google.search_engine_id');
            
            if (!$apiKey || !$searchEngineId) {
                Log::info("Google Custom Search API not configured, skipping site search");
                return [];
            }

            $response = Http::timeout(30)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $searchEngineId,
                'q' => $searchQuery,
                'num' => 10
            ]);

            if (!$response->successful()) {
                Log::warning("Google Search API returned status: {$response->status()}");
                return [];
            }

            $data = $response->json();
            $topPages = [];

            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $topPages[] = [
                        'url' => $item['link'],
                        'title' => $item['title'],
                        'snippet' => $item['snippet'],
                    ];
                }
            }

            return $topPages;

        } catch (\Exception $e) {
            Log::error("Google site search failed: {$e->getMessage()}");
            return [];
        }
    }

    private function analyzePage(string $url): ?array
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                Log::warning("Failed to fetch page for SEO analysis: {$url}");
                return null;
            }

            $html = $response->body();
            
            return [
                'title' => $this->extractTitle($html),
                'description' => $this->extractMetaDescription($html),
                'keywords' => $this->extractMetaKeywords($html),
                'headings' => $this->analyzeHeadings($html),
                'images' => $this->analyzeImages($html),
                'links' => $this->analyzeLinks($html),
                'content_analysis' => $this->analyzeContent($html),
                'social_meta' => $this->extractSocialMeta($html),
                'structured_data' => $this->checkStructuredData($html),
                'performance_hints' => $this->getPerformanceHints($html),
                'analyzed_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error("Page SEO analysis failed for {$url}: {$e->getMessage()}");
            return null;
        }
    }

    private function extractTitle(string $html): ?array
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = trim(strip_tags($matches[1]));
            
            return [
                'content' => $title,
                'length' => strlen($title),
                'issues' => $this->checkTitleIssues($title)
            ];
        }
        
        return null;
    }

    private function extractMetaDescription(string $html): ?array
    {
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            $description = trim($matches[1]);
            
            return [
                'content' => $description,
                'length' => strlen($description),
                'issues' => $this->checkDescriptionIssues($description)
            ];
        }
        
        return null;
    }

    private function extractMetaKeywords(string $html): ?string
    {
        if (preg_match('/<meta[^>]*name=["\']keywords["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    private function analyzeHeadings(string $html): array
    {
        $headings = [];
        
        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $html, $matches);
            
            if (!empty($matches[1])) {
                $headings["h{$i}"] = [
                    'count' => count($matches[1]),
                    'content' => array_map(fn($h) => trim(strip_tags($h)), $matches[1])
                ];
            }
        }
        
        return [
            'structure' => $headings,
            'issues' => $this->checkHeadingIssues($headings)
        ];
    }

    private function analyzeImages(string $html): array
    {
        preg_match_all('/<img[^>]*>/i', $html, $imgMatches);
        $totalImages = count($imgMatches[0]);
        
        preg_match_all('/<img[^>]*alt=[^>]*>/i', $html, $altMatches);
        $imagesWithAlt = count($altMatches[0]);
        
        return [
            'total' => $totalImages,
            'with_alt' => $imagesWithAlt,
            'without_alt' => $totalImages - $imagesWithAlt,
            'alt_percentage' => $totalImages > 0 ? round(($imagesWithAlt / $totalImages) * 100, 2) : 0
        ];
    }

    private function analyzeLinks(string $html): array
    {
        preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/i', $html, $matches);
        $allLinks = $matches[1];
        
        $internal = 0;
        $external = 0;
        $domain = parse_url($this->website->url, PHP_URL_HOST);
        
        foreach ($allLinks as $link) {
            $linkDomain = parse_url($link, PHP_URL_HOST);
            
            if (!$linkDomain || $linkDomain === $domain) {
                $internal++;
            } else {
                $external++;
            }
        }
        
        return [
            'total' => count($allLinks),
            'internal' => $internal,
            'external' => $external
        ];
    }

    private function analyzeContent(string $html): array
    {
        // Remove script and style tags
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
        
        // Extract text content
        $textContent = strip_tags($content);
        $textContent = preg_replace('/\s+/', ' ', $textContent);
        $textContent = trim($textContent);
        
        $wordCount = str_word_count($textContent);
        
        return [
            'word_count' => $wordCount,
            'character_count' => strlen($textContent),
            'readability' => $this->calculateReadabilityScore($textContent)
        ];
    }

    private function extractSocialMeta(string $html): array
    {
        $socialMeta = [];
        
        // Open Graph tags
        preg_match_all('/<meta[^>]*property=["\']og:([^"\']*)["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $ogMatches, PREG_SET_ORDER);
        foreach ($ogMatches as $match) {
            $socialMeta['og'][$match[1]] = $match[2];
        }
        
        // Twitter Card tags
        preg_match_all('/<meta[^>]*name=["\']twitter:([^"\']*)["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $twitterMatches, PREG_SET_ORDER);
        foreach ($twitterMatches as $match) {
            $socialMeta['twitter'][$match[1]] = $match[2];
        }
        
        return $socialMeta;
    }

    private function checkStructuredData(string $html): array
    {
        $structuredData = [];
        
        // Check for JSON-LD
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);
        
        if (!empty($matches[1])) {
            $structuredData['json_ld'] = count($matches[1]);
        }
        
        // Check for microdata
        $microdataCount = preg_match_all('/itemscope[^>]*>/i', $html);
        if ($microdataCount > 0) {
            $structuredData['microdata'] = $microdataCount;
        }
        
        return $structuredData;
    }

    private function getPerformanceHints(string $html): array
    {
        $hints = [];
        
        // Check for inline styles
        $inlineStyles = preg_match_all('/style=["\'][^"\']*["\']/', $html);
        if ($inlineStyles > 5) {
            $hints[] = "Consider moving inline styles to external CSS files ({$inlineStyles} found)";
        }
        
        // Check for large images without optimization
        preg_match_all('/<img[^>]*src=["\']([^"\']*)["\'][^>]*>/i', $html, $imgMatches);
        $largeImages = 0;
        foreach ($imgMatches[1] as $src) {
            if (strpos($src, '.jpg') !== false || strpos($src, '.png') !== false) {
                $largeImages++;
            }
        }
        
        if ($largeImages > 10) {
            $hints[] = "Consider optimizing images and using modern formats like WebP";
        }
        
        return $hints;
    }

    private function checkTitleIssues(string $title): array
    {
        $issues = [];
        
        if (strlen($title) < 30) {
            $issues[] = 'Title too short (recommended: 30-60 characters)';
        } elseif (strlen($title) > 60) {
            $issues[] = 'Title too long (recommended: 30-60 characters)';
        }
        
        return $issues;
    }

    private function checkDescriptionIssues(string $description): array
    {
        $issues = [];
        
        if (strlen($description) < 120) {
            $issues[] = 'Description too short (recommended: 120-160 characters)';
        } elseif (strlen($description) > 160) {
            $issues[] = 'Description too long (recommended: 120-160 characters)';
        }
        
        return $issues;
    }

    private function checkHeadingIssues(array $headings): array
    {
        $issues = [];
        
        if (!isset($headings['h1']) || $headings['h1']['count'] === 0) {
            $issues[] = 'Missing H1 tag';
        } elseif ($headings['h1']['count'] > 1) {
            $issues[] = 'Multiple H1 tags found (should be only one)';
        }
        
        return $issues;
    }

    private function calculateReadabilityScore(string $text): float
    {
        // Simple Flesch Reading Ease approximation
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($text);
        $syllables = $this->countSyllables($text);
        
        if (count($sentences) === 0 || $words === 0) {
            return 0;
        }
        
        $avgSentenceLength = $words / count($sentences);
        $avgSyllablesPerWord = $syllables / $words;
        
        $score = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * $avgSyllablesPerWord);
        
        return max(0, min(100, round($score, 1)));
    }

    private function countSyllables(string $text): int
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z]/', '', $text);
        
        $syllables = 0;
        $words = str_split($text);
        
        foreach ($words as $word) {
            $syllables += max(1, preg_match_all('/[aeiouy]+/', $word));
        }
        
        return $syllables;
    }
}
