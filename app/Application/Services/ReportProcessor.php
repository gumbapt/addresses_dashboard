<?php

namespace App\Application\Services;

use App\Domain\Repositories\ProviderRepositoryInterface;
use App\Domain\Repositories\StateRepositoryInterface;
use App\Domain\Repositories\CityRepositoryInterface;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
use App\Helpers\ProviderHelper;
use App\Models\ReportSummary;
use App\Models\ReportProvider;
use App\Models\ReportState;
use App\Models\ReportCity;
use App\Models\ReportZipCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use PDOException;

class ReportProcessor
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
        private StateRepositoryInterface $stateRepository,
        private CityRepositoryInterface $cityRepository,
        private ZipCodeRepositoryInterface $zipCodeRepository
    ) {}

    /**
     * Process a complete report
     */
    public function process(int $reportId, array $reportData): void
    {
        Log::info('Processing report sections', ['report_id' => $reportId]);

        // Process each section of the report
        $this->processSummary($reportId, $reportData['summary'] ?? []);
        $this->processProviders($reportId, $reportData['providers'] ?? []);
        $this->processGeographic($reportId, $reportData['geographic'] ?? []);
        
        // Additional sections can be processed here:
        // $this->processPerformance($reportId, $reportData['performance'] ?? []);
        // $this->processSpeedMetrics($reportId, $reportData['speed_metrics'] ?? []);
        // $this->processTechnologyMetrics($reportId, $reportData['technology_metrics'] ?? []);
        // $this->processExclusions($reportId, $reportData['exclusion_metrics'] ?? []);
        // $this->processHealth($reportId, $reportData['health'] ?? []);

        Log::info('Report processing completed', ['report_id' => $reportId]);
    }

    /**
     * Process summary data
     */
    private function processSummary(int $reportId, array $summaryData): void
    {
        if (empty($summaryData)) {
            return;
        }

        Log::debug('Processing summary', ['report_id' => $reportId]);

        // Use updateOrCreate to handle duplicate report_id (retry scenarios)
        // However, in high concurrency scenarios, a race condition can still occur where two workers
        // try to create the same summary simultaneously. We handle this with a try-catch to retry.
        try {
            ReportSummary::updateOrCreate(
                ['report_id' => $reportId], // Search criteria
                [
                    'total_requests' => $summaryData['total_requests'] ?? 0,
                    'success_rate' => $summaryData['success_rate'] ?? 0,
                    'failed_requests' => $summaryData['failed_requests'] ?? 0,
                    'avg_requests_per_hour' => $summaryData['avg_requests_per_hour'] ?? 0,
                    'unique_providers' => $summaryData['unique_providers'] ?? 0,  
                    'unique_states' => $summaryData['unique_states'] ?? 0,
                    'unique_zip_codes' => $summaryData['unique_zip_codes'] ?? 0,
                ]
            );
        } catch (QueryException|UniqueConstraintViolationException|PDOException $e) {
            // Handle race condition: if duplicate entry error (1062), try to find and update the existing record
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $summary = ReportSummary::where('report_id', $reportId)->first();
                
                if ($summary) {
                    // Update the existing record
                    $summary->update([
                        'total_requests' => $summaryData['total_requests'] ?? 0,
                        'success_rate' => $summaryData['success_rate'] ?? 0,
                        'failed_requests' => $summaryData['failed_requests'] ?? 0,
                        'avg_requests_per_hour' => $summaryData['avg_requests_per_hour'] ?? 0,
                        'unique_providers' => $summaryData['unique_providers'] ?? 0,  
                        'unique_states' => $summaryData['unique_states'] ?? 0,
                        'unique_zip_codes' => $summaryData['unique_zip_codes'] ?? 0,
                    ]);
                } else {
                    // If still not found, throw the original exception
                    throw $e;
                }
            } else {
                // For other database errors, re-throw
                throw $e;
            }
        }
    }

    /**
     * Process provider data
     */
    private function processProviders(int $reportId, array $providersData): void
    {
        if (empty($providersData['top_providers'])) {
            return;
        }

        Log::debug('Processing providers', [
            'report_id' => $reportId,
            'provider_count' => count($providersData['top_providers'])
        ]);

        foreach ($providersData['top_providers'] as $index => $providerData) {
            // Normalize provider name
            $normalizedName = ProviderHelper::normalizeName($providerData['name']);
            $technology = ProviderHelper::normalizeTechnology($providerData['technology'] ?? 'Unknown');
            
            // Find or create provider
            $provider = $this->providerRepository->findOrCreate(
                name: $normalizedName,
                technologies: [$technology]
            );
            
            // Create report provider record
            ReportProvider::create([
                'report_id' => $reportId,
                'provider_id' => $provider->getId(),
                'original_name' => $providerData['name'], // Keep original name
                'technology' => $technology,
                'total_count' => $providerData['total_count'] ?? 0,
                'success_rate' => $providerData['success_rate'] ?? 0,
                'avg_speed' => $providerData['avg_speed'] ?? 0,
                'rank_position' => $index + 1, // Position in top providers
            ]);
        }

        Log::debug('Provider processing completed', [
            'report_id' => $reportId,
            'processed_count' => count($providersData['top_providers'])
        ]);
    }

    /**
     * Process geographic data
     */
    private function processGeographic(int $reportId, array $geoData): void
    {
        $this->processStates($reportId, $geoData['states'] ?? []);
        $this->processCities($reportId, $geoData['top_cities'] ?? []);
        $this->processZipCodes($reportId, $geoData['top_zip_codes'] ?? []);
    }

    /**
     * Process states data
     */
    private function processStates(int $reportId, array $statesData): void
    {
        if (empty($statesData)) {
            return;
        }

        Log::debug('Processing states', [
            'report_id' => $reportId,
            'state_count' => count($statesData)
        ]);

        foreach ($statesData as $stateData) {
            // Find or create state
            $state = $this->stateRepository->findOrCreateByCode(
                $stateData['code'],
                $stateData['name'] ?? null
            );
            
            // Create report state record
            ReportState::create([
                'report_id' => $reportId,
                'state_id' => $state->getId(),
                'request_count' => $stateData['request_count'] ?? 0,
                'success_rate' => $stateData['success_rate'] ?? 0,
                'avg_speed' => $stateData['avg_speed'] ?? 0,
            ]);
        }
    }

    /**
     * Process cities data
     */
    private function processCities(int $reportId, array $citiesData): void
    {
        if (empty($citiesData)) {
            return;
        }

        Log::debug('Processing cities', [
            'report_id' => $reportId,
            'city_count' => count($citiesData)
        ]);

        foreach ($citiesData as $cityData) {
            // Find or create city (without specific state since it's not provided in JSON)
            $city = $this->cityRepository->findOrCreateByName($cityData['name']);
            
            // Create report city record
            ReportCity::create([
                'report_id' => $reportId,
                'city_id' => $city->getId(),
                'request_count' => $cityData['request_count'] ?? 0,
                'zip_codes' => $cityData['zip_codes'] ?? [],
            ]);
        }
    }

    /**
     * Process zip codes data
     */
    private function processZipCodes(int $reportId, array $zipCodesData): void
    {
        if (empty($zipCodesData)) {
            return;
        }

        Log::debug('Processing zip codes', [
            'report_id' => $reportId,
            'zip_count' => count($zipCodesData)
        ]);

        foreach ($zipCodesData as $zipData) {
            // Find or create zip code
            $zipCode = $this->zipCodeRepository->findOrCreateByCode($zipData['zip_code']);
            
            // Create report zip code record
            ReportZipCode::create([
                'report_id' => $reportId,
                'zip_code_id' => $zipCode->getId(),
                'request_count' => $zipData['request_count'] ?? 0,
                'percentage' => $zipData['percentage'] ?? 0,
            ]);
        }
    }
}
