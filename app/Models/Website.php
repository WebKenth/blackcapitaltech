<?php

namespace App\Models;

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
                $website->slug = Str::random(10);
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
}
