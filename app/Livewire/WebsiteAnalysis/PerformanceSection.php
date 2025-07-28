<?php

namespace App\Livewire\WebsiteAnalysis;

use App\Models\Website;
use Livewire\Component;

class PerformanceSection extends Component
{
    public Website $website;

    public function mount(Website $website)
    {
        $this->website = $website;
    }

    public function render()
    {
        return view('livewire.website-analysis.performance-section');
    }
}
