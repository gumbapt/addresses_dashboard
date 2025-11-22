<?php

namespace App\Application\UseCases\Report\Global;

use App\Application\DTOs\Report\Global\ProviderRankingDTO;
use Illuminate\Support\Facades\DB;

class GetProviderRankingUseCase
{
    /**
     * Get provider ranking across domains
     * 
     * @param int|null $providerId Filter by specific provider
     * @param string|null $technology Filter by technology (Fiber, Cable, DSL, etc)
     * @param string|null $dateFrom Date range start (YYYY-MM-DD)
     * @param string|null $dateTo Date range end (YYYY-MM-DD)
     * @param string $sortBy Sort criteria: total_requests, success_rate, avg_speed, total_reports
     * @param int|null $limit Maximum results to return (deprecated, use pagination)
     * @param array|null $accessibleDomainIds Filter by accessible domain IDs (null = all)
     * @param bool $aggregateByProvider If true, aggregate all technologies for the same provider+domain
     * @return array Array of ProviderRankingDTO
     */
    public function execute(
        ?int $providerId = null,
        ?string $technology = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortBy = 'total_requests',
        ?int $limit = null,
        ?array $accessibleDomainIds = null,
        bool $aggregateByProvider = false
    ): array {
        $query = DB::table('report_providers as rp')
            ->join('providers as p', 'rp.provider_id', '=', 'p.id')
            ->join('reports as r', 'rp.report_id', '=', 'r.id')
            ->join('domains as d', 'r.domain_id', '=', 'd.id')
            ->where('r.status', 'processed')
            ->where('d.is_active', true);
        
        // Filtros
        if ($providerId) {
            $query->where('rp.provider_id', $providerId);
        }
        
        if ($technology) {
            $query->where('rp.technology', $technology);
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
        $selectFields = [
            'd.id as domain_id',
            'd.name as domain_name',
            'd.slug as domain_slug',
            'p.id as provider_id',
            'p.name as provider_name',
            DB::raw('SUM(rp.total_count) as total_requests'),
            DB::raw('AVG(rp.success_rate) as avg_success_rate'),
            DB::raw('AVG(rp.avg_speed) as avg_speed'),
            DB::raw('COUNT(DISTINCT r.id) as total_reports'),
            DB::raw('MIN(r.report_date) as period_start'),
            DB::raw('MAX(r.report_date) as period_end')
        ];
        
        $groupByFields = ['d.id', 'd.name', 'd.slug', 'p.id', 'p.name'];
        
        if ($aggregateByProvider) {
            // Quando agregar por provider, listar todas as tecnologias
            $selectFields[] = DB::raw('GROUP_CONCAT(DISTINCT rp.technology ORDER BY rp.technology SEPARATOR ", ") as technologies');
            // Não incluir technology no groupBy
        } else {
            // Comportamento normal: agrupar por tecnologia também
            $selectFields[] = 'rp.technology';
            $groupByFields[] = 'rp.technology';
        }
        
        $rankings = $query
            ->select($selectFields)
            ->groupBy($groupByFields)
            ->orderByRaw($this->getOrderByClause($sortBy))
            ->when($limit, fn($q) => $q->limit($limit))
            ->get()
            ->toArray();
        
        // Calcular total de requests por domínio (para calcular porcentagem)
        $domainTotals = $this->getDomainTotalRequests($dateFrom, $dateTo, $accessibleDomainIds);
        
        // Adicionar porcentagem a cada ranking
        $rankings = array_map(function($item) use ($domainTotals) {
            $domainTotal = $domainTotals[$item->domain_id] ?? 1; // Evitar divisão por zero
            $item->percentage_of_domain = ($item->total_requests / $domainTotal) * 100;
            $item->domain_total_requests = $domainTotal;
            return $item;
        }, $rankings);
        
        // Converter para DTOs com rank
        return array_map(function($item, $index) use ($aggregateByProvider) {
            $periodStart = new \DateTime($item->period_start);
            $periodEnd = new \DateTime($item->period_end);
            $daysCovered = $periodStart->diff($periodEnd)->days + 1;
            
            // Quando agregado, usar null ou a lista de tecnologias
            $technology = $aggregateByProvider 
                ? ($item->technologies ?? null)
                : ($item->technology ?? null);
            
            return new ProviderRankingDTO(
                rank: $index + 1,
                domainId: $item->domain_id,
                domainName: $item->domain_name,
                domainSlug: $item->domain_slug,
                providerId: $item->provider_id,
                providerName: $item->provider_name,
                technology: $technology,
                totalRequests: (int) $item->total_requests,
                avgSuccessRate: (float) $item->avg_success_rate,
                avgSpeed: (float) $item->avg_speed,
                totalReports: (int) $item->total_reports,
                periodStart: $periodStart->format('Y-m-d'),
                periodEnd: $periodEnd->format('Y-m-d'),
                daysCovered: $daysCovered,
                domainTotalRequests: (int) $item->domain_total_requests,
                percentageOfDomain: (float) $item->percentage_of_domain,
            );
        }, $rankings, array_keys($rankings));
    }

    /**
     * Get provider ranking with pagination
     * 
     * @return array ['data' => ProviderRankingDTO[], 'pagination' => [...]]
     */
    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?int $providerId = null,
        ?string $technology = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortBy = 'total_requests',
        ?array $accessibleDomainIds = null,
        bool $aggregateByProvider = false
    ): array {
        // Get all results (without limit)
        $allResults = $this->execute(
            $providerId,
            $technology,
            $dateFrom,
            $dateTo,
            $sortBy,
            null, // No limit
            $accessibleDomainIds,
            $aggregateByProvider
        );
        
        $total = count($allResults);
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100
        $page = max($page, 1);
        $lastPage = (int) ceil($total / $perPage);
        $page = min($page, max($lastPage, 1));
        
        $offset = ($page - 1) * $perPage;
        $paginatedResults = array_slice($allResults, $offset, $perPage);
        
        return [
            'data' => $paginatedResults,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => $total > 0 ? min($offset + $perPage, $total) : 0,
            ],
        ];
    }
    
    /**
     * Get total requests per domain for percentage calculation
     */
    private function getDomainTotalRequests(?string $dateFrom, ?string $dateTo, ?array $accessibleDomainIds): array
    {
        $query = DB::table('report_providers as rp')
            ->join('reports as r', 'rp.report_id', '=', 'r.id')
            ->join('domains as d', 'r.domain_id', '=', 'd.id')
            ->where('r.status', 'processed')
            ->where('d.is_active', true);
        
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
                DB::raw('SUM(rp.total_count) as total_requests')
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

