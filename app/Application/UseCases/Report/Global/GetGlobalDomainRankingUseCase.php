<?php

namespace App\Application\UseCases\Report\Global;

use App\Application\DTOs\Report\Global\DomainRankingDTO;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use App\Models\ReportState;
use Illuminate\Support\Facades\DB;

class GetGlobalDomainRankingUseCase
{
    /**
     * Get global domain ranking
     * 
     * @param string $sortBy Options: 'score', 'volume', 'success', 'speed'
     * @param string|null $dateFrom Filter by date range start
     * @param string|null $dateTo Filter by date range end
     * @param int|null $minReports Minimum number of reports required
     * @param array|null $accessibleDomainIds Filter by accessible domain IDs (null = all)
     * @return array Array of DomainRankingDTO
     */
    public function execute(
        string $sortBy = 'score',
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $minReports = null,
        ?array $accessibleDomainIds = null
    ): array {
        // Get domains (filtered by accessible if provided)
        $query = Domain::where('is_active', true);
        
        if ($accessibleDomainIds !== null && !empty($accessibleDomainIds)) {
            $query->whereIn('id', $accessibleDomainIds);
        }
        
        $domains = $query->get();

        if ($domains->isEmpty()) {
            return [];
        }

        $rankings = [];

        foreach ($domains as $domain) {
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

            $reports = $reportsQuery->orderBy('report_date')->get();

            // Skip if below minimum reports threshold
            if ($minReports && $reports->count() < $minReports) {
                continue;
            }

            // Skip if no reports
            if ($reports->isEmpty()) {
                continue;
            }

            $reportIds = $reports->pluck('id')->toArray();

            // Aggregate metrics
            $metrics = $this->aggregateMetrics($reportIds);

            // Calculate score (weighted combination)
            $score = $this->calculateScore(
                $metrics['total_requests'],
                $metrics['avg_success_rate'],
                $metrics['avg_speed']
            );

            $rankings[] = [
                'domain' => $domain,
                'metrics' => $metrics,
                'score' => $score,
                'period' => [
                    'start' => $reports->first()->report_date->format('Y-m-d'),
                    'end' => $reports->last()->report_date->format('Y-m-d'),
                    'days' => $reports->first()->report_date->diffInDays($reports->last()->report_date) + 1,
                ],
            ];
        }

        // Sort by requested criteria
        $rankings = $this->sortRankings($rankings, $sortBy);

        // Convert to DTOs with rank
        return array_map(function ($item, $index) {
            return new DomainRankingDTO(
                rank: $index + 1,
                domainId: $item['domain']->id,
                domainName: $item['domain']->name,
                domainSlug: $item['domain']->slug,
                totalRequests: $item['metrics']['total_requests'],
                successRate: $item['metrics']['avg_success_rate'],
                avgSpeed: $item['metrics']['avg_speed'],
                score: $item['score'],
                totalReports: $item['metrics']['total_reports'],
                uniqueProviders: $item['metrics']['unique_providers'],
                uniqueStates: $item['metrics']['unique_states'],
                periodStart: $item['period']['start'],
                periodEnd: $item['period']['end'],
                daysCovered: $item['period']['days'],
            );
        }, $rankings, array_keys($rankings));
    }

    private function aggregateMetrics(array $reportIds): array
    {
        // Aggregate from report_summaries
        $summary = DB::table('report_summaries')
            ->whereIn('report_id', $reportIds)
            ->select(
                DB::raw('SUM(total_requests) as total_requests'),
                DB::raw('AVG(success_rate) as avg_success_rate'),
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('SUM(unique_providers) as total_unique_providers'),
                DB::raw('SUM(unique_states) as total_unique_states')
            )
            ->first();

        // Get average speed from raw_data
        $reports = Report::whereIn('id', $reportIds)->get();
        $speeds = [];
        foreach ($reports as $report) {
            if (isset($report->raw_data['speed_metrics']['overall']['avg'])) {
                $speeds[] = $report->raw_data['speed_metrics']['overall']['avg'];
            }
        }
        $avgSpeed = !empty($speeds) ? array_sum($speeds) / count($speeds) : 0;

        // Get unique counts
        $uniqueProviders = DB::table('report_providers')
            ->whereIn('report_id', $reportIds)
            ->distinct('provider_id')
            ->count('provider_id');

        $uniqueStates = DB::table('report_states')
            ->whereIn('report_id', $reportIds)
            ->distinct('state_id')
            ->count('state_id');

        return [
            'total_requests' => (int) ($summary->total_requests ?? 0),
            'avg_success_rate' => (float) ($summary->avg_success_rate ?? 0),
            'avg_speed' => (float) $avgSpeed,
            'total_reports' => (int) ($summary->total_reports ?? 0),
            'unique_providers' => (int) $uniqueProviders,
            'unique_states' => (int) $uniqueStates,
        ];
    }

    private function calculateScore(int $totalRequests, float $successRate, float $avgSpeed): float
    {
        // Weighted score combining volume, quality, and speed
        // Formula: (requests/1000) * (success_rate/100) * log(speed+1)/10
        
        $volumeScore = $totalRequests / 1000; // Normalize requests
        $qualityScore = $successRate / 100;   // Normalize success rate to 0-1
        $speedScore = log($avgSpeed + 1) / 10; // Log scale for speed
        
        $score = $volumeScore * $qualityScore * $speedScore;
        
        return $score;
    }

    private function sortRankings(array $rankings, string $sortBy): array
    {
        usort($rankings, function ($a, $b) use ($sortBy) {
            return match($sortBy) {
                'volume' => $b['metrics']['total_requests'] <=> $a['metrics']['total_requests'],
                'success' => $b['metrics']['avg_success_rate'] <=> $a['metrics']['avg_success_rate'],
                'speed' => $b['metrics']['avg_speed'] <=> $a['metrics']['avg_speed'],
                'score' => $b['score'] <=> $a['score'],
                default => $b['score'] <=> $a['score'],
            };
        });

        return $rankings;
    }
}

