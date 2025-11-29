<?php

namespace App\Application\UseCases\Report;

use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use App\Models\ReportProvider;
use App\Models\ReportState;
use App\Models\ReportCity;
use App\Models\ReportZipCode;
use Illuminate\Support\Facades\DB;

class GetDomainStateStatsUseCase
{
    public function execute(
        int $domainId,
        int $stateId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $sortBy = null
    ): array {
        $domain = Domain::findOrFail($domainId);
        
        // Buscar relatórios do domínio processados e filtrados por data se fornecido
        $reportsQuery = Report::where('domain_id', $domainId)
            ->where('status', 'processed');
        
        if ($dateFrom) {
            $reportsQuery->where('report_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $reportsQuery->where('report_date', '<=', $dateTo);
        }
        
        $reports = $reportsQuery->orderBy('report_date')->get();

        // Filtrar apenas reports que têm dados para o estado específico
        $reportIdsWithState = ReportState::where('state_id', $stateId)
            ->whereIn('report_id', $reports->pluck('id'))
            ->pluck('report_id')
            ->unique()
            ->toArray();
        
        $reports = $reports->whereIn('id', $reportIdsWithState);

        if ($reports->isEmpty()) {
            return $this->emptyStats($domainId, $domain->name, $stateId);
        }

        $reportIds = $reports->pluck('id')->toArray();

        // Buscar dados do estado específico
        $state = DB::table('states')->where('id', $stateId)->first();
        if (!$state) {
            throw new \Exception("State not found");
        }

        // Combina estatísticas de dashboard e aggregate
        $stats = [
            'domain' => [
                'id' => $domainId,
                'name' => $domain->name,
            ],
            'state' => [
                'id' => $state->id,
                'code' => $state->code,
                'name' => $state->name,
            ],
            'period' => [
                'total_reports' => $reports->count(),
                'first_report' => $reports->first()?->report_date?->format('Y-m-d'),
                'last_report' => $reports->last()?->report_date?->format('Y-m-d'),
                'days_covered' => $reports->count() > 0 ? 
                    (strtotime($reports->last()->report_date ?? 'now') - strtotime($reports->first()->report_date ?? 'now')) / 86400 + 1 : 0,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            // KPIs (do dashboard)
            'kpis' => $this->getKPIs($reportIds, $stateId),
            // Provider distribution (do dashboard) - filtrado por estado
            'provider_distribution' => $this->getProviderDistribution($reportIds, $stateId, $sortBy),
            // Top cities no estado (do aggregate)
            'top_cities' => $this->getTopCities($reportIds, $stateId),
            // Top zip codes no estado (do aggregate)
            'top_zip_codes' => $this->getTopZipCodes($reportIds, $stateId),
            // Hourly distribution (do dashboard)
            'hourly_distribution' => $this->getHourlyDistribution($reports),
            // Technology distribution (do dashboard) - filtrado por estado
            'technology_distribution' => $this->getTechnologyDistribution($reportIds, $stateId),
            // State-specific stats
            'state_stats' => $this->getStateStats($reportIds, $stateId),
            // Daily trends (do aggregate)
            'daily_trends' => $this->getDailyTrends($reports),
        ];

        return $stats;
    }

    private function emptyStats(int $domainId, string $domainName, int $stateId): array
    {
        $state = DB::table('states')->where('id', $stateId)->first();
        
        return [
            'domain' => ['id' => $domainId, 'name' => $domainName],
            'state' => $state ? [
                'id' => $state->id,
                'code' => $state->code,
                'name' => $state->name,
            ] : ['id' => $stateId, 'code' => null, 'name' => null],
            'period' => [
                'total_reports' => 0,
                'first_report' => null,
                'last_report' => null,
                'days_covered' => 0,
            ],
            'kpis' => [
                'total_requests' => 0,
                'success_rate' => 0,
                'daily_average' => 0,
                'unique_providers' => 0,
            ],
            'provider_distribution' => [],
            'top_cities' => [],
            'top_zip_codes' => [],
            'hourly_distribution' => [],
            'technology_distribution' => [],
            'state_stats' => [
                'total_requests' => 0,
                'avg_success_rate' => 0,
                'avg_speed' => 0,
            ],
            'daily_trends' => [],
        ];
    }

    private function getKPIs(array $reportIds, int $stateId): array
    {
        // KPIs baseados nos dados do estado específico
        $stateReports = ReportState::where('state_id', $stateId)
            ->whereIn('report_id', $reportIds)
            ->get();

        if ($stateReports->isEmpty()) {
            return [
                'total_requests' => 0,
                'success_rate' => 0,
                'daily_average' => 0,
                'unique_providers' => 0,
            ];
        }

        $totalRequests = $stateReports->sum('request_count');
        $avgSuccessRate = $stateReports->avg('success_rate');
        $daysCount = count(array_unique($stateReports->pluck('report_id')->toArray()));
        $dailyAverage = $daysCount > 0 ? round($totalRequests / $daysCount) : 0;

        // Providers únicos que aparecem neste estado nestes reports
        $uniqueProviders = DB::table('report_state_providers')
            ->where('state_id', $stateId)
            ->whereIn('report_id', $reportIds)
            ->distinct('provider_id')
            ->count('provider_id');

        return [
            'total_requests' => $totalRequests,
            'success_rate' => round($avgSuccessRate, 1),
            'daily_average' => $dailyAverage,
            'unique_providers' => $uniqueProviders,
        ];
    }

    private function getProviderDistribution(array $reportIds, int $stateId, ?string $sortBy = null): array
    {
        // Usar report_state_providers para obter providers no estado específico
        $providers = DB::table('report_state_providers')
            ->join('providers', 'providers.id', '=', 'report_state_providers.provider_id')
            ->where('report_state_providers.state_id', $stateId)
            ->whereIn('report_state_providers.report_id', $reportIds)
            ->select(
                'providers.id',
                'providers.name',
                'providers.slug',
                DB::raw('SUM(report_state_providers.request_count) as total_count'),
                DB::raw('AVG(report_state_providers.success_rate) as avg_success_rate'),
                DB::raw('AVG(report_state_providers.avg_speed) as avg_speed')
            )
            ->groupBy('providers.id', 'providers.name', 'providers.slug');

        // Aplicar ordenação
        $orderBy = match($sortBy) {
            'success_rate' => 'avg_success_rate',
            'avg_speed' => 'avg_speed',
            'total_count', 'total_requests' => 'total_count',
            default => 'total_count',
        };

        $providers = $providers->orderByDesc($orderBy)->get();

        $totalRequests = $providers->sum('total_count');

        return $providers->map(function($p) use ($totalRequests) {
            $percentage = $totalRequests > 0 ? round(($p->total_count / $totalRequests) * 100, 1) : 0;
            
            return [
                'provider_id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'total_count' => (int) $p->total_count,
                'percentage' => $percentage,
                'avg_success_rate' => round($p->avg_success_rate ?? 0, 2),
                'avg_speed' => round($p->avg_speed ?? 0, 2),
            ];
        })->toArray();
    }

    private function getTopCities(array $reportIds, int $stateId): array
    {
        $cities = DB::table('report_cities')
            ->join('cities', 'cities.id', '=', 'report_cities.city_id')
            ->whereIn('report_cities.report_id', $reportIds)
            ->where('cities.state_id', $stateId)
            ->select(
                'cities.id',
                'cities.name',
                DB::raw('SUM(report_cities.request_count) as total_requests'),
                DB::raw('COUNT(DISTINCT report_cities.report_id) as report_count')
            )
            ->groupBy('cities.id', 'cities.name')
            ->orderByDesc('total_requests')
            ->limit(20)
            ->get();

        return $cities->map(fn($c) => [
            'city_id' => $c->id,
            'name' => $c->name,
            'total_requests' => (int) $c->total_requests,
            'report_count' => (int) $c->report_count,
        ])->toArray();
    }

    private function getTopZipCodes(array $reportIds, int $stateId): array
    {
        $zipCodes = DB::table('report_zip_codes')
            ->join('zip_codes', 'zip_codes.id', '=', 'report_zip_codes.zip_code_id')
            ->join('cities', 'cities.id', '=', 'zip_codes.city_id')
            ->whereIn('report_zip_codes.report_id', $reportIds)
            ->where('cities.state_id', $stateId)
            ->select(
                'zip_codes.id',
                'zip_codes.code',
                DB::raw('SUM(report_zip_codes.request_count) as total_requests'),
                DB::raw('COUNT(DISTINCT report_zip_codes.report_id) as report_count')
            )
            ->groupBy('zip_codes.id', 'zip_codes.code')
            ->orderByDesc('total_requests')
            ->limit(20)
            ->get();

        return $zipCodes->map(fn($z) => [
            'zip_code_id' => $z->id,
            'code' => $z->code,
            'total_requests' => (int) $z->total_requests,
            'report_count' => (int) $z->report_count,
        ])->toArray();
    }

    private function getHourlyDistribution(mixed $reports): array
    {
        $hourlyData = [];
        
        foreach ($reports as $report) {
            $rawData = $report->raw_data;
            
            if (isset($rawData['performance']['hourly_distribution'])) {
                $hourlyDist = $rawData['performance']['hourly_distribution'];
                
                if (is_array($hourlyDist) && !empty($hourlyDist) && !isset($hourlyDist[0])) {
                    foreach ($hourlyDist as $hour => $count) {
                        $hour = (int) $hour;
                        if (!isset($hourlyData[$hour])) {
                            $hourlyData[$hour] = 0;
                        }
                        $hourlyData[$hour] += (int) $count;
                    }
                } elseif (is_array($hourlyDist) && !empty($hourlyDist) && isset($hourlyDist[0])) {
                    foreach ($hourlyDist as $hourData) {
                        if (is_array($hourData) && isset($hourData['hour'])) {
                            $hour = (int) $hourData['hour'];
                            if (!isset($hourlyData[$hour])) {
                                $hourlyData[$hour] = 0;
                            }
                            $hourlyData[$hour] += (int) ($hourData['count'] ?? 0);
                        }
                    }
                }
            }
        }

        $maxCount = max($hourlyData) ?: 1;
        
        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $count = $hourlyData[$hour] ?? 0;
            $normalized = $maxCount > 0 ? round($count / $maxCount, 2) : 0;
            
            $result[] = [
                'hour' => sprintf('%02d:00', $hour),
                'count' => $count,
                'normalized' => $normalized,
            ];
        }

        return $result;
    }

    private function getTechnologyDistribution(array $reportIds, int $stateId): array
    {
        // Buscar technology dos providers no estado específico
        $technologies = DB::table('report_state_providers')
            ->join('report_providers', function($join) use ($reportIds) {
                $join->on('report_providers.provider_id', '=', 'report_state_providers.provider_id')
                     ->on('report_providers.report_id', '=', 'report_state_providers.report_id');
            })
            ->where('report_state_providers.state_id', $stateId)
            ->whereIn('report_state_providers.report_id', $reportIds)
            ->select(
                'report_providers.technology',
                DB::raw('SUM(report_state_providers.request_count) as total_count')
            )
            ->groupBy('report_providers.technology')
            ->orderByDesc('total_count')
            ->get();

        $totalRequests = $technologies->sum('total_count');

        return $technologies->map(function($t) use ($totalRequests) {
            $percentage = $totalRequests > 0 ? round(($t->total_count / $totalRequests) * 100, 1) : 0;
            
            return [
                'technology' => $t->technology ?: 'Unknown',
                'total_count' => (int) $t->total_count,
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    private function getStateStats(array $reportIds, int $stateId): array
    {
        $stateData = ReportState::where('state_id', $stateId)
            ->whereIn('report_id', $reportIds)
            ->get();

        if ($stateData->isEmpty()) {
            return [
                'total_requests' => 0,
                'avg_success_rate' => 0,
                'avg_speed' => 0,
            ];
        }

        return [
            'total_requests' => $stateData->sum('request_count'),
            'avg_success_rate' => round($stateData->avg('success_rate'), 2),
            'avg_speed' => round($stateData->avg('avg_speed'), 2),
        ];
    }

    private function getDailyTrends(mixed $reports): array
    {
        $trends = [];

        foreach ($reports as $report) {
            $summary = ReportSummary::where('report_id', $report->id)->first();
            
            if ($summary) {
                $trends[] = [
                    'date' => $report->report_date->format('Y-m-d'),
                    'report_id' => $report->id,
                    'total_requests' => $summary->total_requests,
                    'success_rate' => round($summary->success_rate, 2),
                    'failed_requests' => $summary->failed_requests,
                    'avg_requests_per_hour' => round($summary->avg_requests_per_hour, 2),
                ];
            }
        }

        return $trends;
    }
}

