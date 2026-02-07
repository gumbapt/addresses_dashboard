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
    /** @param string|null $businessResidentialFilter 'all' | 'R' | 'B' | 'X' */
    public function execute(
        int $domainId,
        int $stateId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $sortBy = null,
        int $citiesLimit = 10,
        ?string $businessResidentialFilter = 'all'
    ): array {
        $domain = Domain::findOrFail($domainId);

        // Filter R: count_r > 0 OR count_x > 0 (X = both). Filter B: count_b > 0 OR count_x > 0
        $reportsQuery = Report::where('domain_id', $domainId)
            ->where('status', 'processed')
            ->when($businessResidentialFilter && $businessResidentialFilter !== 'all', function ($q) use ($businessResidentialFilter) {
                $q->whereHas('summary', function ($sq) use ($businessResidentialFilter) {
                    if ($businessResidentialFilter === 'R') {
                        $sq->where(function ($q) { $q->where('count_r', '>', 0)->orWhere('count_x', '>', 0); });
                    } elseif ($businessResidentialFilter === 'B') {
                        $sq->where(function ($q) { $q->where('count_b', '>', 0)->orWhere('count_x', '>', 0); });
                    } else {
                        $sq->where('count_x', '>', 0);
                    }
                });
            });
        
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
            return $this->emptyStats($domainId, $domain->name, $stateId, $citiesLimit);
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
            'filters' => [
                'cities_limit' => $citiesLimit,
                'sort_by' => $sortBy,
                'business_residential_filter' => $businessResidentialFilter,
            ],
            // KPIs (do dashboard)
            'kpis' => $this->getKPIs($reportIds, $stateId, $businessResidentialFilter),
            // Provider distribution (do dashboard) - filtrado por estado
            'provider_distribution' => $this->getProviderDistribution($reportIds, $stateId, $sortBy),
            // Top cities no estado (do aggregate)
            'top_cities' => $this->getTopCities($reportIds, $stateId, $citiesLimit),
            // Cities chart data - formatado para gráfico de barras (ordenado da mais comum para menos comum)
            'cities_chart_data' => $this->getCitiesChartData($reportIds, $stateId, $citiesLimit),
            // Dados de gráficos por cidade (providers e tecnologias por cidade)
            'cities_detailed_charts' => $this->getCitiesDetailedCharts($reportIds, $stateId, $citiesLimit),
            // Top zip codes no estado (do aggregate)
            'top_zip_codes' => $this->getTopZipCodes($reportIds, $stateId),
            // Hourly distribution (do dashboard)
            'hourly_distribution' => $this->getHourlyDistribution($reports),
            // Technology distribution (do dashboard) - filtrado por estado
            'technology_distribution' => $this->getTechnologyDistribution($reportIds, $stateId),
            // State-specific stats
            'state_stats' => $this->getStateStats($reportIds, $stateId),
            // Daily trends (do aggregate)
            'daily_trends' => $this->getDailyTrends($reports, $businessResidentialFilter),
        ];

        return $stats;
    }

    private function emptyStats(int $domainId, string $domainName, int $stateId, int $citiesLimit = 10): array
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
                'percentage_r' => null,
                'percentage_b' => null,
            ],
            'provider_distribution' => [],
            'top_cities' => [],
            'cities_chart_data' => [],
            'cities_detailed_charts' => [],
            'top_zip_codes' => [],
            'filters' => [
                'cities_limit' => $citiesLimit,
                'sort_by' => null,
            ],
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

    private function getKPIs(array $reportIds, int $stateId, ?string $businessResidentialFilter = 'all'): array
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
                'percentage_r' => null,
                'percentage_b' => null,
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

        $summaries = ReportSummary::whereIn('report_id', $reportIds)->get();
        $sumR = (int) $summaries->sum('count_r');
        $sumB = (int) $summaries->sum('count_b');
        $sumX = (int) $summaries->sum('count_x');
        $totalWithCodes = $sumR + $sumB + $sumX;
        $percentageR = $totalWithCodes > 0 ? round((($sumR + $sumX) / $totalWithCodes) * 100, 1) : null;
        $percentageB = $totalWithCodes > 0 ? round((($sumB + $sumX) / $totalWithCodes) * 100, 1) : null;

        return [
            'total_requests' => $totalRequests,
            'success_rate' => round($avgSuccessRate, 1),
            'daily_average' => $dailyAverage,
            'unique_providers' => $uniqueProviders,
            'percentage_r' => $percentageR,
            'percentage_b' => $percentageB,
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

    private function getTopCities(array $reportIds, int $stateId, int $limit = 10): array
    {
        if (empty($reportIds)) {
            return [];
        }

        // Primeiro, tentar buscar cidades que estão associadas ao estado na tabela cities
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
            ->havingRaw('SUM(report_cities.request_count) > 0')
            ->orderByDesc('total_requests')
            ->limit($limit)
            ->get();

        // Se não encontrou cidades com state_id, buscar todas as cidades dos reports
        // (pode ser que as cidades não tenham state_id associado)
        if ($cities->isEmpty()) { 
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
                ->havingRaw('SUM(report_cities.request_count) > 0')
                ->orderByDesc('total_requests')
                ->limit($limit)
                ->get();
        }

        if ($cities->isEmpty()) {
            return [];
        }

        return $cities->map(fn($c) => [
            'city_id' => $c->id,
            'name' => $c->name,
            'total_requests' => (int) $c->total_requests,
            'report_count' => (int) $c->report_count,
        ])->toArray();
    }

    /**
     * Get cities data formatted for bar chart (ordered from most common to least common)
     * 
     * @param array $reportIds
     * @param int $stateId
     * @return array
     */
    private function getCitiesChartData(array $reportIds, int $stateId, int $limit = 10): array
    {
        if (empty($reportIds)) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Requisições por Cidade',
                        'data' => [],
                        'backgroundColor' => [],
                    ]
                ],
                'raw_data' => [],
            ];
        }

        // Primeiro, tentar buscar cidades que estão associadas ao estado
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
            ->havingRaw('SUM(report_cities.request_count) > 0')
            ->orderByDesc('total_requests') // Ordenado da mais comum para menos comum
            ->limit($limit)
            ->get();

        // Se não encontrou, buscar todas as cidades dos reports (fallback)
        if ($cities->isEmpty()) {
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
                ->havingRaw('SUM(report_cities.request_count) > 0')
                ->orderByDesc('total_requests')
                ->limit($limit)
                ->get();
        }

        if ($cities->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Requisições por Cidade',
                        'data' => [],
                        'backgroundColor' => [],
                    ]
                ],
                'raw_data' => [],
            ];
        }

        // Calcular total para porcentagem
        $totalRequests = $cities->sum('total_requests');

        // Preparar dados para gráfico
        $labels = [];
        $data = [];
        $percentages = [];
        $backgroundColor = [];
        $rawData = [];

        // Cores para as barras (gradiente de azul)
        $colors = [
            '#3B82F6', '#2563EB', '#1D4ED8', '#1E40AF', '#1E3A8A',
            '#3B82F6', '#2563EB', '#1D4ED8', '#1E40AF', '#1E3A8A',
        ];

        foreach ($cities as $index => $city) {
            $totalRequestsCity = (int) $city->total_requests;
            $percentage = $totalRequests > 0 ? round(($totalRequestsCity / $totalRequests) * 100, 2) : 0;

            $labels[] = $city->name;
            $data[] = $totalRequestsCity;
            $percentages[] = $percentage;
            $backgroundColor[] = $colors[$index % count($colors)];

            $rawData[] = [
                'city_id' => $city->id,
                'name' => $city->name,
                'total_requests' => $totalRequestsCity,
                'percentage' => $percentage,
                'report_count' => (int) $city->report_count,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Requisições por Cidade',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => array_map(fn($color) => $this->darkenColor($color, 0.1), $backgroundColor),
                    'borderWidth' => 1,
                ]
            ],
            'percentages' => $percentages, // Porcentagens para exibir nas barras se necessário
            'total' => $totalRequests,
            'raw_data' => $rawData, // Dados completos para uso adicional
        ];
    }

    /**
     * Get detailed charts data for cities (providers and technologies by city)
     * 
     * @param array $reportIds
     * @param int $stateId
     * @return array
     */
    private function getCitiesDetailedCharts(array $reportIds, int $stateId, int $limit = 10): array
    {
        if (empty($reportIds)) {
            return [];
        }

        // Buscar cidades do estado
        $cities = DB::table('report_cities')
            ->join('cities', 'cities.id', '=', 'report_cities.city_id')
            ->whereIn('report_cities.report_id', $reportIds)
            ->where('cities.state_id', $stateId)
            ->select(
                'cities.id',
                'cities.name',
                DB::raw('SUM(report_cities.request_count) as total_requests')
            )
            ->groupBy('cities.id', 'cities.name')
            ->havingRaw('SUM(report_cities.request_count) > 0')
            ->orderByDesc('total_requests')
            ->limit($limit) // Limitado pelo parâmetro cities_limit
            ->get();

        // Se não encontrou cidades com state_id, buscar todas as cidades dos reports (fallback)
        if ($cities->isEmpty()) {
            $cities = DB::table('report_cities')
                ->join('cities', 'cities.id', '=', 'report_cities.city_id')
                ->whereIn('report_cities.report_id', $reportIds)
                ->select(
                    'cities.id',
                    'cities.name',
                    DB::raw('SUM(report_cities.request_count) as total_requests')
                )
                ->groupBy('cities.id', 'cities.name')
                ->havingRaw('SUM(report_cities.request_count) > 0')
                ->orderByDesc('total_requests')
                ->limit($limit)
                ->get();
        }

        if ($cities->isEmpty()) {
            return [];
        }

        $result = [];

        foreach ($cities as $city) {
            $cityId = $city->id;
            $cityName = $city->name;

            // Buscar reports que têm essa cidade
            $cityReportIds = DB::table('report_cities')
                ->whereIn('report_id', $reportIds)
                ->where('city_id', $cityId)
                ->pluck('report_id')
                ->unique()
                ->toArray();

            if (empty($cityReportIds)) {
                continue;
            }

            // Providers por cidade (aproximação através de report_id - pode não ser 100% preciso)
            $providersByCity = $this->getProvidersByCity($cityReportIds, $stateId);
            
            // Tecnologias por cidade
            $technologiesByCity = $this->getTechnologiesByCity($cityReportIds, $stateId);

            $result[] = [
                'city_id' => $cityId,
                'city_name' => $cityName,
                'total_requests' => (int) $city->total_requests,
                'providers_chart' => $providersByCity,
                'technologies_chart' => $technologiesByCity,
            ];
        }

        return $result;
    }

    /**
     * Get providers chart data for a specific city
     * 
     * @param array $reportIds
     * @param int $stateId
     * @return array
     */
    private function getProvidersByCity(array $reportIds, int $stateId): array
    {
        // Buscar providers através de report_state_providers que estão nos reports desta cidade
        // Isso é uma aproximação - pode não refletir exatamente os providers desta cidade específica
        $providers = DB::table('report_state_providers')
            ->join('providers', 'providers.id', '=', 'report_state_providers.provider_id')
            ->where('report_state_providers.state_id', $stateId)
            ->whereIn('report_state_providers.report_id', $reportIds)
            ->select(
                'providers.id',
                'providers.name',
                DB::raw('SUM(report_state_providers.request_count) as total_count')
            )
            ->groupBy('providers.id', 'providers.name')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get();

        if ($providers->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
                'raw_data' => [],
            ];
        }

        $totalRequests = $providers->sum('total_count');
        $labels = [];
        $data = [];
        $percentages = [];
        $colors = ['#3B82F6', '#2563EB', '#1D4ED8', '#1E40AF', '#1E3A8A', '#60A5FA', '#3B82F6', '#2563EB', '#1D4ED8', '#1E40AF'];
        $backgroundColor = [];
        $rawData = [];

        foreach ($providers as $index => $provider) {
            $totalCount = (int) $provider->total_count;
            $percentage = $totalRequests > 0 ? round(($totalCount / $totalRequests) * 100, 2) : 0;

            $labels[] = $provider->name;
            $data[] = $totalCount;
            $percentages[] = $percentage;
            $backgroundColor[] = $colors[$index % count($colors)];

            $rawData[] = [
                'provider_id' => $provider->id,
                'name' => $provider->name,
                'total_count' => $totalCount,
                'percentage' => $percentage,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Requisições por Provider',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => array_map(fn($color) => $this->darkenColor($color, 0.1), $backgroundColor),
                    'borderWidth' => 1,
                ]
            ],
            'percentages' => $percentages,
            'total' => $totalRequests,
            'raw_data' => $rawData,
        ];
    }

    /**
     * Get technologies chart data for a specific city
     * 
     * @param array $reportIds
     * @param int $stateId
     * @return array
     */
    private function getTechnologiesByCity(array $reportIds, int $stateId): array
    {
        // Buscar tecnologias através de report_state_providers
        $technologies = DB::table('report_state_providers')
            ->join('report_providers', function($join) use ($reportIds) {
                $join->on('report_providers.provider_id', '=', 'report_state_providers.provider_id')
                     ->on('report_providers.report_id', '=', 'report_state_providers.report_id')
                     ->whereIn('report_providers.report_id', $reportIds);
            })
            ->where('report_state_providers.state_id', $stateId)
            ->select(
                'report_providers.technology',
                DB::raw('SUM(report_state_providers.request_count) as total_count')
            )
            ->groupBy('report_providers.technology')
            ->orderByDesc('total_count')
            ->get();

        if ($technologies->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
                'raw_data' => [],
            ];
        }

        $totalRequests = $technologies->sum('total_count');
        $labels = [];
        $data = [];
        $percentages = [];
        $colors = ['#10B981', '#059669', '#047857', '#065F46', '#064E3B', '#34D399', '#10B981', '#059669', '#047857', '#065F46'];
        $backgroundColor = [];
        $rawData = [];

        foreach ($technologies as $index => $tech) {
            $totalCount = (int) $tech->total_count;
            $percentage = $totalRequests > 0 ? round(($totalCount / $totalRequests) * 100, 2) : 0;
            $technology = $tech->technology ?: 'Unknown';

            $labels[] = $technology;
            $data[] = $totalCount;
            $percentages[] = $percentage;
            $backgroundColor[] = $colors[$index % count($colors)];

            $rawData[] = [
                'technology' => $technology,
                'total_count' => $totalCount,
                'percentage' => $percentage,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Requisições por Tecnologia',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => array_map(fn($color) => $this->darkenColor($color, 0.1), $backgroundColor),
                    'borderWidth' => 1,
                ]
            ],
            'percentages' => $percentages,
            'total' => $totalRequests,
            'raw_data' => $rawData,
        ];
    }

    /**
     * Darken a hex color by a percentage
     * 
     * @param string $hexColor
     * @param float $percent
     * @return string
     */
    private function darkenColor(string $hexColor, float $percent): string
    {
        $hexColor = ltrim($hexColor, '#');
        $rgb = [
            hexdec(substr($hexColor, 0, 2)),
            hexdec(substr($hexColor, 2, 2)),
            hexdec(substr($hexColor, 4, 2)),
        ];

        foreach ($rgb as &$color) {
            $color = max(0, min(255, floor($color * (1 - $percent))));
        }

        return '#' . sprintf('%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    private function getTopZipCodes(array $reportIds, int $stateId): array
    {
        if (empty($reportIds)) {
            return [];
        }

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
            ->havingRaw('SUM(report_zip_codes.request_count) > 0') // Garantir que há requisições
            ->orderByDesc('total_requests')
            ->limit(20)
            ->get();

        if ($zipCodes->isEmpty()) {
            return [];
        }

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

    private function getDailyTrends(mixed $reports, ?string $businessResidentialFilter = 'all'): array
    {
        $trends = [];

        foreach ($reports as $report) {
            $summary = ReportSummary::where('report_id', $report->id)->first();

            if ($summary) {
                $countR = (int) ($summary->count_r ?? 0);
                $countB = (int) ($summary->count_b ?? 0);
                $countX = (int) ($summary->count_x ?? 0);
                $totalWithCodes = $countR + $countB + $countX;
                $percentageR = $totalWithCodes > 0 ? round((($countR + $countX) / $totalWithCodes) * 100, 1) : null;
                $percentageB = $totalWithCodes > 0 ? round((($countB + $countX) / $totalWithCodes) * 100, 1) : null;

                $totalRequests = match ($businessResidentialFilter) {
                    'R' => $countR + $countX,
                    'B' => $countB + $countX,
                    'X' => $countX,
                    default => $summary->total_requests,
                };

                $trends[] = [
                    'date' => $report->report_date->format('Y-m-d'),
                    'report_id' => $report->id,
                    'total_requests' => (int) ($totalRequests ?? 0),
                    'success_rate' => round($summary->success_rate, 2),
                    'failed_requests' => $summary->failed_requests,
                    'avg_requests_per_hour' => round($summary->avg_requests_per_hour, 2),
                    'percentage_r' => $percentageR,
                    'percentage_b' => $percentageB,
                ];
            }
        }

        return $trends;
    }
}

