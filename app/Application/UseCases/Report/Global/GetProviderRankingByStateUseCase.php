<?php

namespace App\Application\UseCases\Report\Global;

use Illuminate\Support\Facades\DB;

class GetProviderRankingByStateUseCase
{
    /**
     * Get provider ranking by state (precise data from report_state_providers)
     * 
     * @param int $stateId State ID to filter by
     * @param int|null $providerId Optional provider filter
     * @param string|null $dateFrom Date range start (YYYY-MM-DD)
     * @param string|null $dateTo Date range end (YYYY-MM-DD)
     * @param string $sortBy Sort criteria: total_requests, success_rate, avg_speed, total_reports
     * @param array|null $accessibleDomainIds Filter by accessible domain IDs (null = all)
     * @return array Array of ranking results
     */
    public function execute(
        int $stateId,
        ?int $providerId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortBy = 'total_requests',
        ?array $accessibleDomainIds = null
    ): array {
        $query = DB::table('report_state_providers as rsp')
            ->join('providers as p', 'rsp.provider_id', '=', 'p.id')
            ->join('reports as r', 'rsp.report_id', '=', 'r.id')
            ->join('domains as d', 'r.domain_id', '=', 'd.id')
            ->join('states as s', 'rsp.state_id', '=', 's.id')
            ->where('r.status', 'processed')
            ->where('d.is_active', true)
            ->where('s.id', $stateId);
        
        // Filtros
        if ($providerId) {
            $query->where('rsp.provider_id', $providerId);
        }
        
        if ($dateFrom) {
            $query->where('r.report_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('r.report_date', '<=', $dateTo);
        }
        
        if ($accessibleDomainIds && !empty($accessibleDomainIds)) {
            $query->whereIn('d.id', $accessibleDomainIds);
        }
        
        // Agregar por domínio e provider
        $rankings = $query
            ->select(
                'd.id as domain_id',
                'd.name as domain_name',
                'd.slug as domain_slug',
                'p.id as provider_id',
                'p.name as provider_name',
                's.id as state_id',
                's.code as state_code',
                's.name as state_name',
                DB::raw('SUM(rsp.request_count) as total_requests'),
                DB::raw('AVG(rsp.success_rate) as avg_success_rate'),
                DB::raw('AVG(rsp.avg_speed) as avg_speed'),
                DB::raw('COUNT(DISTINCT r.id) as total_reports'),
                DB::raw('MIN(r.report_date) as period_start'),
                DB::raw('MAX(r.report_date) as period_end')
            )
            ->groupBy('d.id', 'd.name', 'd.slug', 'p.id', 'p.name', 's.id', 's.code', 's.name')
            ->orderByRaw($this->getOrderByClause($sortBy))
            ->get()
            ->toArray();
        
        // Calcular total de requests por domínio no estado (para calcular porcentagem)
        $domainTotals = $this->getDomainTotalRequestsByState($stateId, $dateFrom, $dateTo, $accessibleDomainIds);
        
        // Adicionar porcentagem a cada ranking
        $rankings = array_map(function($item) use ($domainTotals) {
            $domainTotal = $domainTotals[$item->domain_id] ?? 1; // Evitar divisão por zero
            $item->percentage_of_domain = ($item->total_requests / $domainTotal) * 100;
            $item->domain_total_requests = $domainTotal;
            return $item;
        }, $rankings);
        
        // Converter para array com rank
        return array_map(function($item, $index) {
            $periodStart = new \DateTime($item->period_start);
            $periodEnd = new \DateTime($item->period_end);
            $daysCovered = $periodStart->diff($periodEnd)->days + 1;
            
            return [
                'rank' => $index + 1,
                'domain_id' => $item->domain_id,
                'domain_name' => $item->domain_name,
                'domain_slug' => $item->domain_slug,
                'provider_id' => $item->provider_id,
                'provider_name' => $item->provider_name,
                'state_id' => $item->state_id,
                'state_code' => $item->state_code,
                'state_name' => $item->state_name,
                'total_requests' => (int) $item->total_requests,
                'avg_success_rate' => round((float) $item->avg_success_rate, 2),
                'avg_speed' => round((float) $item->avg_speed, 2),
                'total_reports' => (int) $item->total_reports,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'days_covered' => $daysCovered,
                'domain_total_requests' => (int) $item->domain_total_requests,
                'percentage_of_domain' => round((float) $item->percentage_of_domain, 2),
            ];
        }, $rankings, array_keys($rankings));
    }
    
    /**
     * Get total requests per domain in a specific state for percentage calculation
     */
    private function getDomainTotalRequestsByState(int $stateId, ?string $dateFrom, ?string $dateTo, ?array $accessibleDomainIds): array
    {
        $query = DB::table('report_state_providers as rsp')
            ->join('reports as r', 'rsp.report_id', '=', 'r.id')
            ->join('domains as d', 'r.domain_id', '=', 'd.id')
            ->where('r.status', 'processed')
            ->where('d.is_active', true)
            ->where('rsp.state_id', $stateId);
        
        if ($dateFrom) {
            $query->where('r.report_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('r.report_date', '<=', $dateTo);
        }
        
        if ($accessibleDomainIds && !empty($accessibleDomainIds)) {
            $query->whereIn('d.id', $accessibleDomainIds);
        }
        
        $totals = $query
            ->select(
                'd.id as domain_id',
                DB::raw('SUM(rsp.request_count) as total_requests')
            )
            ->groupBy('d.id')
            ->get();
        
        // Converter para array [domain_id => total_requests]
        $result = [];
        foreach ($totals as $total) {
            $result[$total->domain_id] = (int) $total->total_requests;
        }
        
        return $result;
    }
    
    /**
     * Get ORDER BY clause based on sort criteria
     */
    private function getOrderByClause(string $sortBy): string
    {
        return match($sortBy) {
            'success_rate' => 'avg_success_rate DESC',
            'avg_speed' => 'avg_speed DESC',
            'total_reports' => 'total_reports DESC',
            default => 'total_requests DESC',
        };
    }
}

