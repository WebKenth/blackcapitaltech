<?php

namespace App\Jobs;

use App\Models\Website;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompanyLookupJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Website $website,
        public string $cvrNumber
    ) {}

    public function handle(): void
    {
        Log::info("Starting CVR lookup for: {$this->cvrNumber}");
        
        $this->website->update(['analysis_status' => 'analyzing_company']);

        try {
            // Use Virk.dk API for CVR lookup
            $companyData = $this->lookupCvr($this->cvrNumber);
            
            if ($companyData) {
                $this->website->update([
                    'company' => $companyData,
                    'analysis_status' => 'company_analyzed'
                ]);
                
                Log::info("CVR lookup completed for: {$this->cvrNumber}");
            } else {
                Log::warning("No company data found for CVR: {$this->cvrNumber}");
            }

        } catch (\Exception $e) {
            Log::error("CVR lookup failed: {$e->getMessage()}", [
                'website_id' => $this->website->id,
                'cvr' => $this->cvrNumber
            ]);
        }
    }

    private function lookupCvr(string $cvrNumber): ?array
    {
        try {
            // Using the free Virk.dk API endpoint
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'BCT Website Analyzer/1.0',
                ])
                ->get("https://cvrapi.dk/api", [
                    'search' => $cvrNumber,
                    'country' => 'dk',
                    'format' => 'json'
                ]);

            if (!$response->successful()) {
                Log::warning("CVR API returned status: {$response->status()}");
                return null;
            }

            $data = $response->json();
            
            if (isset($data['error'])) {
                Log::warning("CVR API error: {$data['error']}");
                return null;
            }

            // Map the response to our format
            return [
                'cvr' => $data['vat'] ?? $cvrNumber,
                'name' => $data['name'] ?? null,
                'industry' => $data['industrycode']['text'] ?? null,
                'industry_code' => $data['industrycode']['code'] ?? null,
                'status' => $data['status'] ?? null,
                'employee_count' => $data['employees'] ?? null,
                'size' => $this->determineCompanySize($data['employees'] ?? 0),
                'location' => $this->formatAddress($data),
                'founded_year' => $data['startdate'] ? date('Y', strtotime($data['startdate'])) : null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['homepage'] ?? null,
                'lookup_date' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error("CVR lookup exception: {$e->getMessage()}");
            return null;
        }
    }

    private function determineCompanySize(int $employeeCount): string
    {
        if ($employeeCount === 0) {
            return 'Ukendt';
        } elseif ($employeeCount <= 10) {
            return 'Mikro (1-10 ansatte)';
        } elseif ($employeeCount <= 50) {
            return 'Små (11-50 ansatte)';
        } elseif ($employeeCount <= 250) {
            return 'Mellem (51-250 ansatte)';
        } else {
            return 'Store (250+ ansatte)';
        }
    }

    private function formatAddress(array $data): string
    {
        $addressParts = [];
        
        if (!empty($data['address'])) {
            $addressParts[] = $data['address'];
        }
        
        if (!empty($data['zipcode']) || !empty($data['city'])) {
            $cityPart = trim(($data['zipcode'] ?? '') . ' ' . ($data['city'] ?? ''));
            if ($cityPart) {
                $addressParts[] = $cityPart;
            }
        }
        
        return implode(', ', $addressParts) ?: 'Ikke angivet';
    }
}
