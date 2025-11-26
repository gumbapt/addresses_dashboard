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
use App\Models\ReportStateProvider;
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
            // Log para debug - verificar se providers está presente
            $stateCode = $stateData['code'] ?? 'unknown';
            $hasProviders = isset($stateData['providers']) && is_array($stateData['providers']);
            $providersCount = $hasProviders ? count($stateData['providers']) : 0;
            
            Log::debug('Processing state', [
                'report_id' => $reportId,
                'state_code' => $stateCode,
                'has_providers' => $hasProviders,
                'providers_count' => $providersCount,
                'state_data_keys' => array_keys($stateData),
            ]);
            
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
            
            // Process providers for this state (if provided)
            if ($hasProviders && !empty($stateData['providers'])) {
                Log::debug('Calling processStateProviders', [
                    'report_id' => $reportId,
                    'state_id' => $state->getId(),
                    'state_code' => $stateCode,
                    'providers_count' => $providersCount,
                ]);
                $this->processStateProviders($reportId, $state->getId(), $stateData['providers']);
            } else {
                Log::debug('Skipping processStateProviders', [
                    'report_id' => $reportId,
                    'state_code' => $stateCode,
                    'has_providers' => $hasProviders,
                    'providers_empty' => $hasProviders && empty($stateData['providers']),
                ]);
            }
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

    /**
     * Process providers for a specific state
     */
    private function processStateProviders(int $reportId, int $stateId, array $providersData): void
    {
        if (empty($providersData)) {
            Log::debug('processStateProviders: providersData está vazio', [
                'report_id' => $reportId,
                'state_id' => $stateId,
            ]);
            return;
        }

        Log::debug('🔵 ANTES de processar state providers', [
            'report_id' => $reportId,
            'state_id' => $stateId,
            'provider_count' => count($providersData),
            'providers_data' => $providersData, // Log completo dos dados
        ]);

        $processedCount = 0;
        foreach ($providersData as $index => $providerData) {
            $providerName = $providerData['name'] ?? null;
            $requestCount = $providerData['count'] ?? 0;
            
            Log::debug('🔵 Processando provider individual', [
                'report_id' => $reportId,
                'state_id' => $stateId,
                'index' => $index,
                'provider_name' => $providerName,
                'request_count' => $requestCount,
                'provider_data' => $providerData,
            ]);
            
            if (!$providerName || $requestCount <= 0) {
                Log::debug('⚠️ Provider inválido, pulando', [
                    'report_id' => $reportId,
                    'state_id' => $stateId,
                    'provider_name' => $providerName,
                    'request_count' => $requestCount,
                ]);
                continue; // Skip invalid providers
            }
            
            // Normalize provider name (use same helper as processProviders)
            $normalizedName = ProviderHelper::normalizeName($providerName);
            
            Log::debug('🔵 ANTES de findOrCreate provider', [
                'report_id' => $reportId,
                'state_id' => $stateId,
                'original_name' => $providerName,
                'normalized_name' => $normalizedName,
            ]);
            
            // Find or create provider (use same repository)
            $provider = $this->providerRepository->findOrCreate(
                name: $normalizedName,
                technologies: [] // Technology not provided in state providers field
            );
            
            Log::debug('🔵 Provider encontrado/criado', [
                'report_id' => $reportId,
                'state_id' => $stateId,
                'provider_id' => $provider->getId(),
                'provider_name' => $provider->getName(),
            ]);
            
            // Create state-provider cross-reference record
            try {
                Log::debug('🔵 ANTES de criar ReportStateProvider', [
                    'report_id' => $reportId,
                    'state_id' => $stateId,
                    'provider_id' => $provider->getId(),
                    'original_name' => $providerName,
                    'request_count' => $requestCount,
                    'success_rate' => $providerData['success_rate'] ?? null,
                    'avg_speed' => $providerData['avg_speed'] ?? null,
                ]);
                
                $created = ReportStateProvider::firstOrCreate([
                    'report_id' => $reportId,
                    'state_id' => $stateId,
                    'provider_id' => $provider->getId(),
                ], [
                    'original_name' => $providerName,
                    'request_count' => $requestCount,
                    'success_rate' => $providerData['success_rate'] ?? null,
                    'avg_speed' => $providerData['avg_speed'] ?? null,
                ]);

                Log::debug('✅ DEPOIS de criar ReportStateProvider', [
                    'report_id' => $reportId,
                    'state_id' => $stateId,
                    'provider_id' => $provider->getId(),
                    'was_recently_created' => $created->wasRecentlyCreated,
                    'id' => $created->id,
                    'request_count' => $created->request_count,
                ]);
                
                $processedCount++;

            } catch (QueryException|UniqueConstraintViolationException|PDOException $e) {
                Log::error('❌ Erro ao criar ReportStateProvider', [
                    'report_id' => $reportId,
                    'state_id' => $stateId,
                    'provider_id' => $provider->getId(),
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                ]);
                
                // Handle race condition: if duplicate entry, try to update existing record
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    $existing = ReportStateProvider::where('report_id', $reportId)
                        ->where('state_id', $stateId)
                        ->where('provider_id', $provider->getId())
                        ->first();
                    
                    if ($existing) {
                        Log::debug('🔄 Atualizando ReportStateProvider existente', [
                            'report_id' => $reportId,
                            'state_id' => $stateId,
                            'provider_id' => $provider->getId(),
                            'existing_id' => $existing->id,
                        ]);
                        
                        $existing->update([
                            'request_count' => $requestCount,
                            'success_rate' => $providerData['success_rate'] ?? null,
                            'avg_speed' => $providerData['avg_speed'] ?? null,
                        ]);
                        
                        $processedCount++;
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

        Log::debug('✅ DEPOIS de processar state providers', [
            'report_id' => $reportId,
            'state_id' => $stateId,
            'processed_count' => $processedCount,
            'total_providers' => count($providersData),
        ]);
    }
}
