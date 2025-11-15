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

class GetAggregatedReportStatsUseCase
{
    public function execute(int $domainId): AggregatedReportStatsDTO
    {
        $domain = Domain::findOrFail($domainId);
        
        // Buscar todos os relatórios processados do domínio
        $reports = Report::where('domain_id', $domainId)
            ->where('status', 'processed')
            ->orderBy('report_date')
            ->get();

        if ($reports->isEmpty()) {
            return $this->emptyStats($domainId, $domain->name);
        }

        $reportIds = $reports->pluck('id')->toArray();

        // Agregar Summary
        $summary = $this->aggregateSummary($reportIds);

        // Agregar Providers
        $providers = $this->aggregateProviders($reportIds);

        // Agregar States
        $states = $this->aggregateStates($reportIds);

        // Agregar Cities
        $cities = $this->aggregateCities($reportIds);

        // Agregar ZipCodes
        $zipCodes = $this->aggregateZipCodes($reportIds);

        // Trends diários
        $dailyTrends = $this->getDailyTrends($reports);

        return new AggregatedReportStatsDTO(
            domainId: $domainId,
            domainName: $domain->name,
            totalReports: $reports->count(),
            firstReportDate: $reports->first()?->report_date?->format('Y-m-d'),
            lastReportDate: $reports->last()?->report_date?->format('Y-m-d'),
            summary: $summary,
            providers: $providers,
            states: $states,
            cities: $cities,
            zipCodes: $zipCodes,
            dailyTrends: $dailyTrends,
        );
    }

    private function emptyStats(int $domainId, string $domainName): AggregatedReportStatsDTO
    {
        return new AggregatedReportStatsDTO(
            domainId: $domainId,
            domainName: $domainName,
            totalReports: 0,
            firstReportDate: null,
            lastReportDate: null,
            summary: [
                'total_requests' => 0,
                'total_failed' => 0,
                'avg_success_rate' => 0,
                'total_unique_providers' => 0,
                'total_unique_states' => 0,
                'total_unique_zip_codes' => 0,
            ],
            providers: [],
            states: [],
            cities: [],
            zipCodes: [],
            dailyTrends: [],
        );
    }

    private function aggregateSummary(array $reportIds): array
    {
        $summaries = ReportSummary::whereIn('report_id', $reportIds)->get();

        if ($summaries->isEmpty()) {
            return [
                'total_requests' => 0,
                'total_failed' => 0,
                'avg_success_rate' => 0,
                'total_unique_providers' => 0,
                'total_unique_states' => 0,
                'total_unique_zip_codes' => 0,
            ];
        }

        $totalRequests = $summaries->sum('total_requests');
        $totalFailed = $summaries->sum('failed_requests');

        return [
            'total_requests' => $totalRequests,
            'total_failed' => $totalFailed,
            'avg_success_rate' => $summaries->avg('success_rate'),
            'avg_requests_per_hour' => $summaries->avg('avg_requests_per_hour'),
            'total_unique_providers' => ReportProvider::whereIn('report_id', $reportIds)
                ->distinct('provider_id')
                ->count('provider_id'),
            'total_unique_states' => ReportState::whereIn('report_id', $reportIds)
                ->distinct('state_id')
                ->count('state_id'),
            'total_unique_zip_codes' => ReportZipCode::whereIn('report_id', $reportIds)
                ->distinct('zip_code_id')
                ->count('zip_code_id'),
        ];
    }

    private function aggregateProviders(array $reportIds): array
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
                DB::raw('AVG(report_providers.success_rate) as avg_success_rate'),
                DB::raw('AVG(report_providers.avg_speed) as avg_speed'),
                DB::raw('COUNT(DISTINCT report_providers.report_id) as report_count')
            )
            ->groupBy('providers.id', 'providers.name', 'providers.slug', 'report_providers.technology')
            ->orderByDesc('total_count')
            ->limit(20)
            ->get();

        return $providers->map(fn($p) => [
            'provider_id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'technology' => $p->technology,
            'total_count' => (int) $p->total_count,
            'avg_success_rate' => round($p->avg_success_rate, 2),
            'avg_speed' => round($p->avg_speed, 2),
            'report_count' => (int) $p->report_count,
        ])->toArray();
    }

    private function aggregateStates(array $reportIds): array
    {
        $states = DB::table('report_states')
            ->join('states', 'states.id', '=', 'report_states.state_id')
            ->whereIn('report_states.report_id', $reportIds)
            ->select(
                'states.id',
                'states.code',
                'states.name',
                DB::raw('SUM(report_states.request_count) as total_requests'),
                DB::raw('AVG(report_states.success_rate) as avg_success_rate'),
                DB::raw('AVG(report_states.avg_speed) as avg_speed'),
                DB::raw('COUNT(DISTINCT report_states.report_id) as report_count')
            )
            ->groupBy('states.id', 'states.code', 'states.name')
            ->orderByDesc('total_requests')
            ->limit(20)
            ->get();

        $result = $states->map(fn($s) => [
            'state_id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'total_requests' => (int) $s->total_requests,
            'avg_success_rate' => round($s->avg_success_rate, 2),
            'avg_speed' => round($s->avg_speed, 2),
            'report_count' => (int) $s->report_count,
        ])->toArray();

        // Se avg_speed for 0 para todos os estados, tentar buscar de speed_metrics.by_state do raw_data
        $hasSpeedData = array_sum(array_column($result, 'avg_speed')) > 0;
        
        if (!$hasSpeedData) {
            $speedDataByState = [];
            $reports = Report::whereIn('id', $reportIds)->get();
            
            foreach ($reports as $report) {
                $rawData = $report->raw_data;
                
                // Tentar buscar de speed_metrics.by_state
                if (isset($rawData['speed_metrics']['by_state']) && is_array($rawData['speed_metrics']['by_state'])) {
                    foreach ($rawData['speed_metrics']['by_state'] as $stateCode => $speedData) {
                        if (!isset($speedDataByState[$stateCode])) {
                            $speedDataByState[$stateCode] = [];
                        }
                        if (isset($speedData['avg']) && $speedData['avg'] > 0) {
                            $speedDataByState[$stateCode][] = $speedData['avg'];
                        }
                    }
                }
                
                // Tentar buscar de geographic.states[].avg_speed (caso não tenha sido processado)
                if (isset($rawData['geographic']['states']) && is_array($rawData['geographic']['states'])) {
                    foreach ($rawData['geographic']['states'] as $stateData) {
                        $stateCode = $stateData['code'] ?? null;
                        $avgSpeed = $stateData['avg_speed'] ?? 0;
                        if ($stateCode && $avgSpeed > 0) {
                            if (!isset($speedDataByState[$stateCode])) {
                                $speedDataByState[$stateCode] = [];
                            }
                            $speedDataByState[$stateCode][] = $avgSpeed;
                        }
                    }
                }
            }
            
            // Atualizar avg_speed nos resultados se encontramos dados
            foreach ($result as &$state) {
                $stateCode = $state['code'];
                if (isset($speedDataByState[$stateCode]) && !empty($speedDataByState[$stateCode])) {
                    $state['avg_speed'] = round(array_sum($speedDataByState[$stateCode]) / count($speedDataByState[$stateCode]), 2);
                }
            }
            unset($state);
        }

        return $result;
    }

    private function aggregateCities(array $reportIds): array
    {
        $cities = DB::table('report_cities')
            ->join('cities', 'cities.id', '=', 'report_cities.city_id')
            ->whereIn('report_cities.report_id', $reportIds)
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

    private function aggregateZipCodes(array $reportIds): array
    {
        $zipCodes = DB::table('report_zip_codes')
            ->join('zip_codes', 'zip_codes.id', '=', 'report_zip_codes.zip_code_id')
            ->whereIn('report_zip_codes.report_id', $reportIds)
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

