<?php

namespace App\Livewire;

use App\Models\Website;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website-dashboard-simple')]
class WebsiteDashboard extends Component
{
    public $slug;
    public $website;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->website = Website::where('slug', $slug)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.website-dashboard');
    }
}
