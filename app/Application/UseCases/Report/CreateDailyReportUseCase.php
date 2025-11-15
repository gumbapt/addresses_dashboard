<?php

namespace App\Application\UseCases\Report;

use App\Domain\Entities\Report;
use App\Domain\Repositories\ReportRepositoryInterface;
use App\Models\Domain;
use Carbon\Carbon;

class CreateDailyReportUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    public function execute(
        int $domainId,
        array $dailyData
    ): Report {
        // Extrair dados do formato diário
        $reportDate = $dailyData['data']['date'];
        $source = $dailyData['source'];
        $summary = $dailyData['data']['summary'];
        
        // Verificar se já existe um relatório para esta data+domínio
        $existingReport = \App\Models\Report::where('domain_id', $domainId)
            ->whereDate('report_date', $reportDate)
            ->first();
        
        if ($existingReport) {
            // Comparar conteúdo para verificar se houve mudança
            $existingRawData = $existingReport->raw_data ?? [];
            $hasChanged = $this->hasReportDataChanged($existingRawData, $dailyData);
            
            if (!$hasChanged) {
                // Não houve mudança, retornar report existente sem reprocessar
                return $existingReport->toEntity();
            }
            
            // Converter formato diário para formato do sistema ANTES de salvar
            $convertedData = $this->convertDailyToSystemFormat($dailyData);
            
            // Houve mudança, atualizar o relatório existente
            $existingReport->update([
                'report_period_start' => Carbon::parse($reportDate)->startOfDay(),
                'report_period_end' => Carbon::parse($reportDate)->endOfDay(),
                'generated_at' => Carbon::parse($dailyData['timestamp']),
                'total_processing_time' => 0,
                'data_version' => $dailyData['api_version'],
                'raw_data' => $convertedData,
                'status' => 'pending', // Reset para pending para reprocessar
            ]);
            
            // Limpar dados processados antigos para reprocessar
            \App\Models\ReportSummary::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportProvider::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportState::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportCity::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportZipCode::where('report_id', $existingReport->id)->delete();
            
            return $existingReport->toEntity();
        }
        
        // Converter formato diário para formato do sistema
        $convertedData = $this->convertDailyToSystemFormat($dailyData);
        
        return $this->reportRepository->create(
            domainId: $domainId,
            reportDate: $reportDate,
            reportPeriodStart: Carbon::parse($reportDate)->startOfDay(),
            reportPeriodEnd: Carbon::parse($reportDate)->endOfDay(),
            generatedAt: Carbon::parse($dailyData['timestamp']),
            totalProcessingTime: 0,
            dataVersion: $dailyData['api_version'],
            rawData: $convertedData,
            status: 'pending'
        );
    }

    private function convertDailyToSystemFormat(array $dailyData): array
    {
        $reportDate = $dailyData['data']['date'];
        $summary = $dailyData['data']['summary'];
        
        return [
            'source' => [
                'domain' => $dailyData['source']['site_url'] ?? 'zip.50g.io',
                'site_id' => $dailyData['source']['site_id'],
                'site_name' => $dailyData['source']['site_name'],
            ],
            'metadata' => [
                'report_date' => $reportDate,
                'data_version' => $dailyData['api_version'],
                'generated_at' => $dailyData['timestamp'],
                'report_period' => [
                    'start' => Carbon::parse($reportDate)->startOfDay()->toISOString(),
                    'end' => Carbon::parse($reportDate)->endOfDay()->toISOString(),
                ],
                'total_processing_time' => 0,
            ],
            'summary' => [
                'total_requests' => $summary['total_requests'],
                'failed_requests' => $summary['failed_requests'],
                'success_rate' => $summary['success_rate'],
                'unique_providers' => $summary['unique_providers'],
                'unique_states' => $summary['unique_states'],
                'unique_zip_codes' => $summary['unique_zipcodes'],
                'avg_requests_per_hour' => $summary['total_requests'] / 24,
            ],
            'providers' => $this->convertProviders($dailyData),
            'geographic' => $this->convertGeographic($dailyData),
            'performance' => $this->convertPerformance($dailyData),
            'speed_metrics' => $this->convertSpeedMetrics($dailyData),
            'exclusion_metrics' => $this->convertExclusionMetrics($dailyData),
            'technology_metrics' => $this->convertTechnologyMetrics($dailyData),
        ];
    }

    private function convertProviders(array $dailyData): array
    {
        $providers = [];
        
        if (isset($dailyData['data']['providers']['available'])) {
            foreach ($dailyData['data']['providers']['available'] as $name => $count) {
                // Pegar avg_speed do speed_metrics.by_provider se existir
                $avgSpeed = 0;
                if (isset($dailyData['speed_metrics']['by_provider'][$name]['avg_speed'])) {
                    $avgSpeed = $dailyData['speed_metrics']['by_provider'][$name]['avg_speed'];
                }
                
                $providers[] = [
                    'name' => $name,
                    'technology' => $this->inferTechnology($name),
                    'total_count' => $count,
                    'success_rate' => 0,
                    'avg_speed' => $avgSpeed,
                ];
            }
        }

        return [
            'top_providers' => $providers,
            'by_state' => [],
        ];
    }

    private function convertGeographic(array $dailyData): array
    {
        $states = [];
        $cities = [];
        $zipCodes = [];

        // Converter estados
        if (isset($dailyData['data']['geographic']['states'])) {
            foreach ($dailyData['data']['geographic']['states'] as $code => $count) {
                // Pegar avg_speed do speed_metrics.by_state se existir
                $avgSpeed = 0;
                if (isset($dailyData['speed_metrics']['by_state'][$code]['avg_speed'])) {
                    $avgSpeed = $dailyData['speed_metrics']['by_state'][$code]['avg_speed'];
                }
                
                $states[] = [
                    'code' => $code,
                    'name' => $this->getStateName($code),
                    'request_count' => $count,
                    'success_rate' => 0,
                    'avg_speed' => $avgSpeed,
                ];
            }
        }

        // Converter cidades
        if (isset($dailyData['data']['geographic']['cities'])) {
            foreach ($dailyData['data']['geographic']['cities'] as $name => $count) {
                $cities[] = [
                    'name' => $name,
                    'request_count' => $count,
                    'zip_codes' => [],
                ];
            }
        }

        // Converter CEPs
        if (isset($dailyData['data']['geographic']['zipcodes'])) {
            foreach ($dailyData['data']['geographic']['zipcodes'] as $zip => $count) {
                $zipCodes[] = [
                    'zip_code' => $zip,
                    'request_count' => $count,
                    'percentage' => 0,
                ];
            }
        }

        return [
            'states' => $states,
            'top_cities' => $cities,
            'top_zip_codes' => $zipCodes,
        ];
    }

    private function convertPerformance(array $dailyData): array
    {
        $totalRequests = $dailyData['data']['summary']['total_requests'] ?? 0;
        
        return [
            'search_types' => [
                'direct' => [
                    'count' => $totalRequests * 0.8,
                    'avg_confidence' => 0,
                    'avg_response_time' => 0,
                ],
                'fallback' => [
                    'count' => $totalRequests * 0.2,
                    'avg_confidence' => 0,
                    'avg_response_time' => 0,
                ],
            ],
            'hourly_distribution' => $this->generateHourlyDistribution($dailyData),
        ];
    }

    private function convertSpeedMetrics(array $dailyData): array
    {
        $summary = $dailyData['data']['summary'];
        
        // Se já existem speed_metrics no dailyData (dados sintéticos), use-os
        if (isset($dailyData['speed_metrics'])) {
            return $dailyData['speed_metrics'];
        }
        
        // Senão, crie a estrutura básica
        return [
            'overall' => [
                'avg' => $summary['avg_speed_mbps'] ?? 0,
                'max' => $summary['max_speed_mbps'] ?? 0,
                'min' => $summary['min_speed_mbps'] ?? 0,
            ],
            'by_state' => [],
            'by_provider' => [],
        ];
    }

    private function convertExclusionMetrics(array $dailyData): array
    {
        $exclusions = [];
        
        if (isset($dailyData['data']['providers']['excluded'])) {
            foreach ($dailyData['data']['providers']['excluded'] as $provider => $count) {
                $exclusions[$provider] = $count;
            }
        }

        return [
            'by_provider' => $exclusions,
            'distribution' => [],
        ];
    }

    private function convertTechnologyMetrics(array $dailyData): array
    {
        // Se já existe technology_metrics no formato novo, usar direto
        if (isset($dailyData['technology_metrics'])) {
            return $dailyData['technology_metrics'];
        }
        
        // Converter formato antigo: data.technologies -> technology_metrics.distribution
        if (isset($dailyData['data']['technologies'])) {
            return [
                'distribution' => $dailyData['data']['technologies'],
                'by_state' => [],
                'by_provider' => [],
            ];
        }
        
        // Converter formato antigo: technologies (top-level) -> technology_metrics.distribution
        if (isset($dailyData['technologies'])) {
            return [
                'distribution' => $dailyData['technologies'],
                'by_state' => [],
                'by_provider' => [],
            ];
        }
        
        // Se não encontrou, retornar vazio
        return [
            'distribution' => [],
            'by_state' => [],
            'by_provider' => [],
        ];
    }

    private function generateHourlyDistribution(array $dailyData): array
    {
        $totalRequests = $dailyData['data']['summary']['total_requests'] ?? 0;
        $hourlyData = [];
        
        // Gerar distribuição simulada baseada no total
        for ($hour = 0; $hour < 24; $hour++) {
            $baseCount = $totalRequests / 24;
            $variation = sin(($hour - 6) * M_PI / 12) * 0.5 + 1; // Pico às 18h
            $count = max(0, round($baseCount * $variation));
            
            $hourlyData[] = [
                'hour' => $hour,
                'count' => $count,
            ];
        }

        return $hourlyData;
    }

    private function inferTechnology(string $providerName): string
    {
        $mobileProviders = ['Verizon', 'AT&T', 'T-Mobile', 'Sprint'];
        $cableProviders = ['Spectrum', 'Xfinity', 'Cox Communications', 'Optimum'];
        $fiberProviders = ['Google Fiber', 'Verizon FiOS', 'AT&T Fiber'];
        $satelliteProviders = ['HughesNet', 'Viasat'];
        $dslProviders = ['CenturyLink', 'Frontier', 'Windstream'];

        $providerLower = strtolower($providerName);
        
        foreach ($mobileProviders as $mobile) {
            if (str_contains($providerLower, strtolower($mobile))) {
                return 'Mobile';
            }
        }
        
        foreach ($cableProviders as $cable) {
            if (str_contains($providerLower, strtolower($cable))) {
                return 'Cable';
            }
        }
        
        foreach ($fiberProviders as $fiber) {
            if (str_contains($providerLower, strtolower($fiber))) {
                return 'Fiber';
            }
        }
        
        foreach ($satelliteProviders as $satellite) {
            if (str_contains($providerLower, strtolower($satellite))) {
                return 'Satellite';
            }
        }
        
        foreach ($dslProviders as $dsl) {
            if (str_contains($providerLower, strtolower($dsl))) {
                return 'DSL';
            }
        }

        return 'Unknown';
    }

    private function getStateName(string $code): string
    {
        $states = [
            'CA' => 'California', 'NY' => 'New York', 'TX' => 'Texas', 'FL' => 'Florida',
            'OH' => 'Ohio', 'NH' => 'New Hampshire', 'TN' => 'Tennessee', 'MO' => 'Missouri',
            'MA' => 'Massachusetts', 'NV' => 'Nevada', 'AL' => 'Alabama', 'WI' => 'Wisconsin',
            'WV' => 'West Virginia', 'MN' => 'Minnesota', 'OK' => 'Oklahoma', 'CO' => 'Colorado',
            'MI' => 'Michigan', 'CT' => 'Connecticut', 'PA' => 'Pennsylvania', 'WA' => 'Washington',
            'KY' => 'Kentucky', 'GA' => 'Georgia', 'NC' => 'North Carolina', 'SC' => 'South Carolina',
            'VA' => 'Virginia', 'MD' => 'Maryland', 'DE' => 'Delaware', 'NJ' => 'New Jersey',
            'RI' => 'Rhode Island', 'VT' => 'Vermont', 'ME' => 'Maine', 'NH' => 'New Hampshire',
        ];

        return $states[$code] ?? $code;
    }

    /**
     * Compara dois reports para verificar se houve mudança no conteúdo
     * Ignora campos de timestamp e metadados que podem variar
     */
    private function hasReportDataChanged(array $existingData, array $newData): bool
    {
        // Normalizar dados removendo campos que podem variar sem mudança real
        $normalizeData = function(array $data) {
            $normalized = [];
            
            // Para daily reports, comparar principalmente data.summary, data.providers, data.geographic
            if (isset($data['data']['summary'])) {
                $normalized['data']['summary'] = $data['data']['summary'];
            }
            if (isset($data['data']['providers'])) {
                $normalized['data']['providers'] = $data['data']['providers'];
            }
            if (isset($data['data']['geographic'])) {
                $normalized['data']['geographic'] = $data['data']['geographic'];
            }
            
            // Também comparar speed_metrics e exclusion_metrics se existirem
            if (isset($data['speed_metrics'])) {
                $normalized['speed_metrics'] = $data['speed_metrics'];
            }
            if (isset($data['exclusion_metrics'])) {
                $normalized['exclusion_metrics'] = $data['exclusion_metrics'];
            }
            
            // Para reports convertidos, comparar campos do formato do sistema
            if (isset($data['summary'])) {
                $normalized['summary'] = $data['summary'];
            }
            if (isset($data['providers'])) {
                $normalized['providers'] = $data['providers'];
            }
            if (isset($data['geographic'])) {
                $normalized['geographic'] = $data['geographic'];
            }
            if (isset($data['performance'])) {
                $normalized['performance'] = $data['performance'];
            }
            
            return $normalized;
        };
        
        $existingNormalized = $normalizeData($existingData);
        $newNormalized = $normalizeData($newData);
        
        // Ordenar arrays recursivamente para comparação consistente
        $this->ksort_recursive($existingNormalized);
        $this->ksort_recursive($newNormalized);
        
        // Comparar usando hash MD5 para eficiência
        $existingHash = md5(json_encode($existingNormalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $newHash = md5(json_encode($newNormalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        
        return $existingHash !== $newHash;
    }

    /**
     * Ordena arrays recursivamente por chave
     */
    private function ksort_recursive(array &$array): void
    {
        ksort($array);
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->ksort_recursive($array[$key]);
            }
        }
    }
}
