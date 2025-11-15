<?php

namespace App\Application\UseCases\Report\Global;

use App\Application\DTOs\Report\Global\DomainComparisonDTO;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use Illuminate\Support\Facades\DB;

class CompareDomainsUseCase
{
    /**
     * Compare metrics between domains
     * 
     * @param array $domainIds Array of domain IDs to compare
     * @param string|null $metric Specific metric to compare (null = all metrics)
     * @param string|null $dateFrom Filter by date range start
     * @param string|null $dateTo Filter by date range end
     * @return array Array of DomainComparisonDTO
     */
    public function execute(
        array $domainIds,
        ?string $metric = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        if (empty($domainIds)) {
            return [];
        }

        // Get domains
        $domains = Domain::whereIn('id', $domainIds)
            ->where('is_active', true)
            ->get();

        if ($domains->isEmpty()) {
            return [];
        }

        $comparisons = [];
        $baseMetrics = null;

        foreach ($domains as $index => $domain) {
            // Build query for reports
            $reportsQuery = Report::where('domain_id', $domain->id)
                ->where('status', 'processed');

            // Apply date filters
            if ($dateFrom) {
                $reportsQuery->where('report_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $reportsQuery->where('report_date', '<=', $dateTo);
            }

            $reports = $reportsQuery->get();

            if ($reports->isEmpty()) {
                continue;
            }

            $reportIds = $reports->pluck('id')->toArray();

            // Aggregate metrics
            $metrics = $this->aggregateMetrics($reportIds, $metric);

            // First domain is the base for comparison
            if ($index === 0) {
                $baseMetrics = $metrics;
            }

            // Calculate differences vs base domain
            $vsBaseDomain = null;
            if ($index > 0 && $baseMetrics) {
                $vsBaseDomain = $this->calculateDifferences($metrics, $baseMetrics);
            }

            $comparisons[] = new DomainComparisonDTO(
                domainId: $domain->id,
                domainName: $domain->name,
                metrics: $metrics,
                vsBaseDomain: $vsBaseDomain,
            );
        }

        return $comparisons;
    }

    /**
     * Get aggregated provider data across all compared domains
     */
    public function getAggregatedProviderData(array $domainIds, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        // Get all report IDs for these domains
        $reportsQuery = Report::whereIn('domain_id', $domainIds)
            ->where('status', 'processed');
        
        if ($dateFrom) {
            $reportsQuery->where('report_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $reportsQuery->where('report_date', '<=', $dateTo);
        }
        
        $reportIds = $reportsQuery->pluck('id')->toArray();
        
        if (empty($reportIds)) {
            return [
                'all_providers' => [],
                'common_providers' => [],
                'unique_providers_count' => 0,
            ];
        }
        
        // Get all providers with aggregated data
        $allProviders = DB::table('report_providers as rp')
            ->join('providers as p', 'rp.provider_id', '=', 'p.id')
            ->whereIn('rp.report_id', $reportIds)
            ->select(
                'p.id',
                'p.name',
                'rp.technology',
                DB::raw('SUM(rp.total_count) as total_requests'),
                DB::raw('AVG(rp.success_rate) as avg_success_rate'),
                DB::raw('AVG(rp.avg_speed) as avg_speed'),
                DB::raw('COUNT(DISTINCT rp.report_id) as appearances')
            )
            ->groupBy('p.id', 'p.name', 'rp.technology')
            ->orderByDesc('total_requests')
            ->get()
            ->map(fn($p) => [
                'provider_id' => $p->id,
                'provider_name' => $p->name,
                'technology' => $p->technology,
                'total_requests' => (int) $p->total_requests,
                'avg_success_rate' => round($p->avg_success_rate, 2),
                'avg_speed' => round($p->avg_speed, 2),
                'appearances' => (int) $p->appearances,
            ])
            ->toArray();
        
        // Find common providers (present in all domains)
        $domainCount = count($domainIds);
        $providersByDomain = [];
        
        foreach ($domainIds as $domainId) {
            $domainReportIds = Report::where('domain_id', $domainId)
                ->where('status', 'processed')
                ->when($dateFrom, fn($q) => $q->where('report_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('report_date', '<=', $dateTo))
                ->pluck('id')
                ->toArray();
            
            if (!empty($domainReportIds)) {
                $providers = DB::table('report_providers')
                    ->whereIn('report_id', $domainReportIds)
                    ->distinct('provider_id')
                    ->pluck('provider_id')
                    ->toArray();
                
                $providersByDomain[$domainId] = $providers;
            }
        }
        
        // Find intersection (common providers)
        $commonProviderIds = !empty($providersByDomain) 
            ? array_intersect(...array_values($providersByDomain))
            : [];
        
        $commonProviders = collect($allProviders)
            ->filter(fn($p) => in_array($p['provider_id'], $commonProviderIds))
            ->values()
            ->toArray();
        
        return [
            'all_providers' => $allProviders,
            'common_providers' => $commonProviders,
            'unique_providers_count' => count(array_unique(array_column($allProviders, 'provider_id'))),
        ];
    }

    private function aggregateMetrics(array $reportIds, ?string $specificMetric): array
    {
        // Base aggregation
        $summary = DB::table('report_summaries')
            ->whereIn('report_id', $reportIds)
            ->select(
                DB::raw('SUM(total_requests) as total_requests'),
                DB::raw('AVG(success_rate) as avg_success_rate'),
                DB::raw('SUM(failed_requests) as total_failed'),
                DB::raw('COUNT(*) as total_reports')
            )
            ->first();

        $metrics = [
            'total_requests' => (int) ($summary->total_requests ?? 0),
            'success_rate' => round($summary->avg_success_rate ?? 0, 2),
            'total_failed' => (int) ($summary->total_failed ?? 0),
            'total_reports' => (int) ($summary->total_reports ?? 0),
        ];

        // Get average speed from raw_data
        $reports = Report::whereIn('id', $reportIds)->get();
        $speeds = [];
        foreach ($reports as $report) {
            if (isset($report->raw_data['speed_metrics']['overall']['avg'])) {
                $speeds[] = $report->raw_data['speed_metrics']['overall']['avg'];
            }
        }
        $metrics['avg_speed'] = !empty($speeds) ? round(array_sum($speeds) / count($speeds), 2) : 0;

        // Add detailed metrics if requested
        if ($specificMetric === 'geographic' || $specificMetric === null) {
            $metrics['top_states'] = $this->getTopStates($reportIds, 5);
        }

        if ($specificMetric === 'providers' || $specificMetric === null) {
            $metrics['top_providers'] = $this->getTopProviders($reportIds, 5);
        }

        if ($specificMetric === 'technologies' || $specificMetric === null) {
            $metrics['technology_distribution'] = $this->getTechnologyDistribution($reportIds);
        }

        return $metrics;
    }

    private function getTopStates(array $reportIds, int $limit): array
    {
        $states = DB::table('report_states')
            ->join('states', 'states.id', '=', 'report_states.state_id')
            ->whereIn('report_states.report_id', $reportIds)
            ->select(
                'states.code',
                'states.name',
                DB::raw('SUM(report_states.request_count) as total_requests')
            )
            ->groupBy('states.id', 'states.code', 'states.name')
            ->orderByDesc('total_requests')
            ->limit($limit)
            ->get();

        return $states->map(fn($s) => [
            'code' => $s->code,
            'name' => $s->name,
            'requests' => (int) $s->total_requests,
        ])->toArray();
    }

    private function getTopProviders(array $reportIds, int $limit): array
    {
        $providers = DB::table('report_providers')
            ->join('providers', 'providers.id', '=', 'report_providers.provider_id')
            ->whereIn('report_providers.report_id', $reportIds)
            ->select(
                'providers.name',
                'report_providers.technology',
                DB::raw('SUM(report_providers.total_count) as total_count')
            )
            ->groupBy('providers.id', 'providers.name', 'report_providers.technology')
            ->orderByDesc('total_count')
            ->limit($limit)
            ->get();

        return $providers->map(fn($p) => [
            'name' => $p->name,
            'technology' => $p->technology,
            'requests' => (int) $p->total_count,
        ])->toArray();
    }

    private function getTechnologyDistribution(array $reportIds): array
    {
        $technologyData = [];
        
        // Buscar dados de technology_metrics.distribution do raw_data
        $reports = \App\Models\Report::whereIn('id', $reportIds)->get();
        
        foreach ($reports as $report) {
            $rawData = $report->raw_data;
            
            // Prioriza technology_metrics.distribution se existir (formato novo)
            if (isset($rawData['technology_metrics']['distribution'])) {
                foreach ($rawData['technology_metrics']['distribution'] as $tech => $count) {
                    if (!isset($technologyData[$tech])) {
                        $technologyData[$tech] = 0;
                    }
                    $technologyData[$tech] += $count;
                }
            }
            // Fallback 1: formato antigo - technologies diretamente (sem .distribution)
            elseif (isset($rawData['technologies']) && is_array($rawData['technologies'])) {
                foreach ($rawData['technologies'] as $tech => $count) {
                    if (!isset($technologyData[$tech])) {
                        $technologyData[$tech] = 0;
                    }
                    $technologyData[$tech] += $count;
                }
            }
            // Fallback 2: formato antigo - data.technologies (formato WordPress antigo)
            elseif (isset($rawData['data']['technologies']) && is_array($rawData['data']['technologies'])) {
                foreach ($rawData['data']['technologies'] as $tech => $count) {
                    if (!isset($technologyData[$tech])) {
                        $technologyData[$tech] = 0;
                    }
                    $technologyData[$tech] += $count;
                }
            }
        }
        
        // Fallback: usar tecnologia dos providers se technology_metrics não existir
        if (empty($technologyData)) {
            $technologies = DB::table('report_providers')
                ->whereIn('report_id', $reportIds)
                ->select(
                    'technology',
                    DB::raw('SUM(total_count) as total_count')
                )
                ->groupBy('technology')
                ->orderByDesc('total_count')
                ->get();

            $total = $technologies->sum('total_count');

            return $technologies->map(function($t) use ($total) {
                return [
                    'technology' => $t->technology ?: 'Unknown',
                    'requests' => (int) $t->total_count,
                    'percentage' => $total > 0 ? round(($t->total_count / $total) * 100, 1) : 0,
                ];
            })->toArray();
        }
        
        // Processar dados de technology_metrics.distribution
        $total = array_sum($technologyData);
        arsort($technologyData);
        
        $result = [];
        foreach ($technologyData as $technology => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            
            $result[] = [
                'technology' => $technology,
                'requests' => (int) $count,
                'percentage' => $percentage,
            ];
        }
        
        return $result;
    }

    private function calculateDifferences(array $current, array $base): array
    {
        $diff = [];

        // Requests difference
        if ($base['total_requests'] > 0) {
            $requestsDiff = (($current['total_requests'] - $base['total_requests']) / $base['total_requests']) * 100;
            $diff['requests_diff'] = round($requestsDiff, 1);
            $diff['requests_diff_label'] = ($requestsDiff >= 0 ? '+' : '') . round($requestsDiff, 1) . '%';
        }

        // Success rate difference
        $successDiff = $current['success_rate'] - $base['success_rate'];
        $diff['success_diff'] = round($successDiff, 2);
        $diff['success_diff_label'] = ($successDiff >= 0 ? '+' : '') . round($successDiff, 2) . '%';

        // Speed difference
        if ($base['avg_speed'] > 0) {
            $speedDiff = (($current['avg_speed'] - $base['avg_speed']) / $base['avg_speed']) * 100;
            $diff['speed_diff'] = round($speedDiff, 1);
            $diff['speed_diff_label'] = ($speedDiff >= 0 ? '+' : '') . round($speedDiff, 1) . '%';
        }

        return $diff;
    }

    private function calculateScore(int $totalRequests, float $successRate, float $avgSpeed): float
    {
        $volumeScore = $totalRequests / 1000;
        $qualityScore = $successRate / 100;
        $speedScore = log($avgSpeed + 1) / 10;
        
        return $volumeScore * $qualityScore * $speedScore;
    }
}

