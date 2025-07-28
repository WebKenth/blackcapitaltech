<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    protected $fillable = [
        'website_id',
        'url',
        'title',
        'description',
        'type',
        'lighthouse',
        'seo',
        'meta_data',
        'is_analyzed',
        'analyzed_at',
    ];

    protected $casts = [
        'lighthouse' => 'array',
        'seo' => 'array',
        'meta_data' => 'array',
        'is_analyzed' => 'boolean',
        'analyzed_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function getLighthouseScoreAttribute(): ?int
    {
        return $this->lighthouse['performance'] ?? null;
    }

    public function getSeoScoreAttribute(): ?int
    {
        return $this->lighthouse['seo'] ?? null;
    }

    public function getAccessibilityScoreAttribute(): ?int
    {
        return $this->lighthouse['accessibility'] ?? null;
    }

    public function getBestPracticesScoreAttribute(): ?int
    {
        return $this->lighthouse['best-practices'] ?? null;
    }

    public function getPwaScoreAttribute(): ?int
    {
        return $this->lighthouse['pwa'] ?? null;
    }
}
