<?php

namespace App\Livewire\WebsiteAnalysis;

use App\Models\Website;
use Livewire\Component;

class CompanySection extends Component
{
    public Website $website;

    public function mount(Website $website)
    {
        $this->website = $website;
    }

    public function render()
    {
        return view('livewire.website-analysis.company-section');
    }
}
