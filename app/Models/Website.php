<?php

namespace App\Models;

use App\Jobs\WebsiteAnalysisJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Website extends Model
{
    protected $fillable = [
        'url',
        'slug',
        'seo',
        'lighthouse',
        'company',
        'goal',
        'tags',
        'is_processed',
        'sitemap_data',
        'analysis_status',
    ];

    protected $casts = [
        'seo' => 'array',
        'lighthouse' => 'array',
        'company' => 'array',
        'tags' => 'array',
        'sitemap_data' => 'array',
        'is_processed' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($website) {
            if (empty($website->slug)) {
                // Extract domain from URL and create slug
                $parsedUrl = parse_url($website->url);
                $domain = $parsedUrl['host'] ?? $website->url;
                
                // Remove www. prefix if present
                $domain = preg_replace('/^www\./', '', $domain);
                
                // Create slug from domain
                $baseSlug = Str::slug($domain);
                
                // Ensure uniqueness by appending number if needed
                $slug = $baseSlug;
                $counter = 1;
                
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $website->slug = $slug;
            }
        });
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function getAnalyzedPagesCountAttribute(): int
    {
        return $this->pages()->where('is_analyzed', true)->count();
    }

    public function getTotalPagesCountAttribute(): int
    {
        return $this->pages()->count();
    }

    /**
     * Run full website analysis (dispatched to queue)
     */
    public function runAnalysis(): void
    {
        $this->update(['analysis_status' => 'queued']);
        WebsiteAnalysisJob::dispatch($this);
    }

    /**
     * Run full website analysis synchronously (for testing)
     */
    public function runAnalysisSync(): void
    {
        $this->update(['analysis_status' => 'queued']);
        WebsiteAnalysisJob::dispatch($this);
    }
}
