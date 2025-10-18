<?php

namespace App\Application\UseCases\Report;

use App\Application\DTOs\Report\AggregatedReportStatsDTO;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use App\Models\ReportProvider;
use App\Models\ReportState;
use App\Models\ReportCity;
use App\Models\ReportZipCode;
use Illuminate\Support\Facades\DB;

class GetDashboardDataUseCase
{
    public function execute(int $domainId): array
    {
        $domain = Domain::findOrFail($domainId);
        
        // Buscar todos os relatórios processados do domínio
        $reports = Report::where('domain_id', $domainId)
            ->where('status', 'processed')
            ->orderBy('report_date')
            ->get();

        if ($reports->isEmpty()) {
            return $this->emptyDashboard($domainId, $domain->name);
        }

        $reportIds = $reports->pluck('id')->toArray();

        return [
            'domain' => [
                'id' => $domainId,
                'name' => $domain->name,
            ],
            'period' => [
                'total_reports' => $reports->count(),
                'first_report' => $reports->first()?->report_date?->format('Y-m-d'),
                'last_report' => $reports->last()?->report_date?->format('Y-m-d'),
                'days_covered' => $reports->count() > 0 ? 
                    (strtotime($reports->last()->report_date ?? 'now') - strtotime($reports->first()->report_date ?? 'now')) / 86400 + 1 : 0,
            ],
            'kpis' => $this->getKPIs($reportIds),
            'provider_distribution' => $this->getProviderDistribution($reportIds),
            'top_states' => $this->getTopStates($reportIds),
            'hourly_distribution' => $this->getHourlyDistribution($reports),
            'speed_by_state' => $this->getSpeedByState($reportIds),
            'technology_distribution' => $this->getTechnologyDistribution($reportIds),
            'exclusion_by_provider' => $this->getExclusionByProvider($reportIds),
        ];
    }

    private function emptyDashboard(int $domainId, string $domainName): array
    {
        return [
            'domain' => ['id' => $domainId, 'name' => $domainName],
            'period' => ['total_reports' => 0, 'first_report' => null, 'last_report' => null, 'days_covered' => 0],
            'kpis' => [
                'total_requests' => 0,
                'success_rate' => 0,
                'daily_average' => 0,
                'unique_providers' => 0,
            ],
            'provider_distribution' => [],
            'top_states' => [],
            'hourly_distribution' => [],
            'speed_by_state' => [],
            'technology_distribution' => [],
            'exclusion_by_provider' => [],
        ];
    }

    private function getKPIs(array $reportIds): array
    {
        $summaries = ReportSummary::whereIn('report_id', $reportIds)->get();

        if ($summaries->isEmpty()) {
            return [
                'total_requests' => 0,
                'success_rate' => 0,
                'daily_average' => 0,
                'unique_providers' => 0,
            ];
        }

        $totalRequests = $summaries->sum('total_requests');
        $avgSuccessRate = $summaries->avg('success_rate');
        $daysCount = count($reportIds);
        $dailyAverage = $daysCount > 0 ? round($totalRequests / $daysCount) : 0;

        return [
            'total_requests' => $totalRequests,
            'success_rate' => round($avgSuccessRate, 1),
            'daily_average' => $dailyAverage,
            'unique_providers' => ReportProvider::whereIn('report_id', $reportIds)
                ->distinct('provider_id')
                ->count('provider_id'),
        ];
    }

    private function getProviderDistribution(array $reportIds): array
    {
        $providers = DB::table('report_providers')
            ->join('providers', 'providers.id', '=', 'report_providers.provider_id')
            ->whereIn('report_providers.report_id', $reportIds)
            ->select(
                'providers.id',
                'providers.name',
                'providers.slug',
                'report_providers.technology',
                DB::raw('SUM(report_providers.total_count) as total_count'),
                DB::raw('AVG(report_providers.success_rate) as avg_success_rate')
            )
            ->groupBy('providers.id', 'providers.name', 'providers.slug', 'report_providers.technology')
            ->orderByDesc('total_count')
            ->get();

        $totalRequests = $providers->sum('total_count');

        return $providers->map(function($p) use ($totalRequests) {
            $percentage = $totalRequests > 0 ? round(($p->total_count / $totalRequests) * 100, 1) : 0;
            
            return [
                'provider_id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'technology' => $p->technology,
                'total_count' => (int) $p->total_count,
                'percentage' => $percentage,
                'avg_success_rate' => round($p->avg_success_rate, 2),
            ];
        })->toArray();
    }

    private function getTopStates(array $reportIds): array
    {
        $states = DB::table('report_states')
            ->join('states', 'states.id', '=', 'report_states.state_id')
            ->whereIn('report_states.report_id', $reportIds)
            ->select(
                'states.id',
                'states.code',
                'states.name',
                DB::raw('SUM(report_states.request_count) as total_requests'),
                DB::raw('AVG(report_states.success_rate) as avg_success_rate')
            )
            ->groupBy('states.id', 'states.code', 'states.name')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get();

        return $states->map(fn($s) => [
            'state_id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'total_requests' => (int) $s->total_requests,
            'avg_success_rate' => round($s->avg_success_rate, 2),
        ])->toArray();
    }

    private function getHourlyDistribution(mixed $reports): array
    {
        $hourlyData = [];
        
        foreach ($reports as $report) {
            $rawData = $report->raw_data;
            
            if (isset($rawData['performance']['hourly_distribution'])) {
                foreach ($rawData['performance']['hourly_distribution'] as $hourData) {
                    $hour = $hourData['hour'];
                    if (!isset($hourlyData[$hour])) {
                        $hourlyData[$hour] = 0;
                    }
                    $hourlyData[$hour] += $hourData['count'];
                }
            }
        }

        // Normalizar para 0-1 (para o gráfico)
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

    private function getSpeedByState(array $reportIds): array
    {
        $states = DB::table('report_states')
            ->join('states', 'states.id', '=', 'report_states.state_id')
            ->whereIn('report_states.report_id', $reportIds)
            ->select(
                'states.id',
                'states.code',
                'states.name',
                DB::raw('AVG(report_states.avg_speed) as avg_speed'),
                DB::raw('SUM(report_states.request_count) as sample_count')
            )
            ->groupBy('states.id', 'states.code', 'states.name')
            ->having('avg_speed', '>', 0)
            ->orderByDesc('avg_speed')
            ->limit(10)
            ->get();

        return $states->map(fn($s) => [
            'state_id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'avg_speed' => round($s->avg_speed, 0),
            'sample_count' => (int) $s->sample_count,
        ])->toArray();
    }

    private function getTechnologyDistribution(array $reportIds): array
    {
        $technologies = DB::table('report_providers')
            ->whereIn('report_providers.report_id', $reportIds)
            ->select(
                'technology',
                DB::raw('SUM(total_count) as total_count'),
                DB::raw('COUNT(DISTINCT provider_id) as unique_providers')
            )
            ->groupBy('technology')
            ->orderByDesc('total_count')
            ->get();

        $totalRequests = $technologies->sum('total_count');

        return $technologies->map(function($t) use ($totalRequests) {
            $percentage = $totalRequests > 0 ? round(($t->total_count / $totalRequests) * 100, 1) : 0;
            
            return [
                'technology' => $t->technology ?: 'Unknown',
                'total_count' => (int) $t->total_count,
                'percentage' => $percentage,
                'unique_providers' => (int) $t->unique_providers,
            ];
        })->toArray();
    }

    private function getExclusionByProvider(array $reportIds): array
    {
        $exclusions = [];
        
        // Buscar dados de exclusão do raw_data
        foreach ($reportIds as $reportId) {
            $report = Report::find($reportId);
            if ($report && isset($report->raw_data['exclusion_metrics']['by_provider'])) {
                foreach ($report->raw_data['exclusion_metrics']['by_provider'] as $providerName => $exclusionData) {
                    if (!isset($exclusions[$providerName])) {
                        $exclusions[$providerName] = 0;
                    }
                    $exclusions[$providerName] += is_array($exclusionData) ? array_sum($exclusionData) : (int) $exclusionData;
                }
            }
        }

        // Converter para array ordenado
        arsort($exclusions);
        
        $result = [];
        foreach (array_slice($exclusions, 0, 10, true) as $providerName => $exclusionCount) {
            $result[] = [
                'provider_name' => $providerName,
                'exclusion_count' => $exclusionCount,
            ];
        }

        return $result;
    }
}

