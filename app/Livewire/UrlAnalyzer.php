<?php

namespace App\Livewire;

use App\Jobs\WebsiteAnalysisJob;
use App\Models\Website;
use Livewire\Component;

class UrlAnalyzer extends Component
{
    public $url = '';
    public $isLoading = false;

    public function rules()
    {
        return [
            'url' => 'required|min:3',
        ];
    }

    private function normalizeUrl($input)
    {
        $input = trim($input);
        
        // If it already has a scheme, parse and validate
        if (preg_match('/^https?:\/\//', $input)) {
            $parsed = parse_url($input);
            if ($parsed && isset($parsed['host'])) {
                return $input;
            }
        }
        
        // Remove any protocol if present
        $input = preg_replace('/^(https?:\/\/)/', '', $input);
        
        // Basic domain validation - must contain at least one dot
        if (!str_contains($input, '.')) {
            return null;
        }
        
        // Add https:// prefix
        return 'https://' . $input;
    }

    public function createWebsite($validatedUrl)
    {
        // Create website record
        $website = Website::create([
            'url' => $validatedUrl,
            'analysis_status' => 'queued'
        ]);
        
        // Dispatch the analysis job
        WebsiteAnalysisJob::dispatch($website);
        
        // Redirect to dashboard
        return $this->redirect('/dashboard/' . $website->slug);
    }

    public function analyzeWebsite()
    {
        $this->validate();
        
        $normalizedUrl = $this->normalizeUrl($this->url);
        
        if (!$normalizedUrl) {
            $this->addError('url', 'Indtast venligst en gyldig URL (f.eks. example.com)');
            return;
        }
        
        $this->isLoading = true;
        
        // Fallback for when JavaScript fails
        return $this->createWebsite($normalizedUrl);
    }

    public function render()
    {
        return view('livewire.url-analyzer');
    }
}
