<?php

namespace App\Livewire;

use App\Models\Website;
use Livewire\Component;

class SitemapSection extends Component
{
    public Website $website;

    public function mount(Website $website)
    {
        $this->website = $website;
    }

    public function getSitemapDataProperty()
    {
        return $this->website->sitemap_data ?? [];
    }

    public function getTotalPagesProperty()
    {
        return $this->sitemapData['total_pages'] ?? 0;
    }

    public function getCategoryDetailsProperty()
    {
        return $this->sitemapData['category_details'] ?? [];
    }

    public function getLastAnalyzedProperty()
    {
        if (isset($this->sitemapData['last_analyzed'])) {
            return \Carbon\Carbon::parse($this->sitemapData['last_analyzed'])->diffForHumans();
        }
        return null;
    }

    public function render()
    {
        return view('livewire.sitemap-section');
    }
}
