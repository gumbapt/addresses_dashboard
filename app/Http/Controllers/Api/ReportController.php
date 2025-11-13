<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\Report\CreateReportUseCase;
use App\Application\UseCases\Report\GetAllReportsUseCase;
use App\Application\UseCases\Report\GetReportByIdUseCase;
use App\Application\UseCases\Report\GetAggregatedReportStatsUseCase;
use App\Application\UseCases\Report\GetReportWithStatsUseCase;
use App\Application\UseCases\Report\GetDashboardDataUseCase;
use App\Application\UseCases\Report\CreateDailyReportUseCase;
use App\Application\UseCases\Report\Global\GetGlobalDomainRankingUseCase;
use App\Application\UseCases\Report\Global\CompareDomainsUseCase;
use App\Application\UseCases\Report\Global\GetProviderRankingUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitReportRequest;
use App\Http\Requests\SubmitDailyReportRequest;
use App\Jobs\ProcessReportJob;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(
        private CreateReportUseCase $createReportUseCase,
        private GetAllReportsUseCase $getAllReportsUseCase,
        private GetReportByIdUseCase $getReportByIdUseCase,
        private GetAggregatedReportStatsUseCase $getAggregatedReportStatsUseCase,
        private GetReportWithStatsUseCase $getReportWithStatsUseCase,
        private GetDashboardDataUseCase $getDashboardDataUseCase,
        private CreateDailyReportUseCase $createDailyReportUseCase,
        private GetGlobalDomainRankingUseCase $getGlobalDomainRankingUseCase,
        private CompareDomainsUseCase $compareDomainsUseCase,
        private GetProviderRankingUseCase $getProviderRankingUseCase
    ) {}

    /**
     * Submit a daily report from a domain (WordPress format)
     * 
     * @group Reports
     * @bodyParam api_version string required API version Example: 1.0
     * @bodyParam report_type string required Report type Example: daily
     * @bodyParam timestamp string required Timestamp Example: 2025-10-16T21:24:25Z
     * @bodyParam source object required Source information
     * @bodyParam source.site_id string required Site ID Example: wp-zip-daily-test
     * @bodyParam source.site_name string required Site name Example: SmarterHome.ai
     * @bodyParam source.site_url string required Site URL Example: http://zip.50g.io
     * @bodyParam source.wordpress_version string required WordPress version Example: 6.8.3
     * @bodyParam source.plugin_version string required Plugin version Example: 1.0.0
     * @bodyParam data object required Daily report data
     * @bodyParam data.date string required Report date Example: 2025-06-27
     * @bodyParam data.summary object required Summary statistics
     * @bodyParam data.geographic object required Geographic data
     * @bodyParam data.providers object required Provider data
     * @response 201 {
     *   "success": true,
     *   "message": "Daily report submitted successfully",
     *   "data": {
     *     "id": 10,
     *     "domain_id": 1,
     *     "report_date": "2025-06-27",
     *     "status": "pending"
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "data.date": ["The data.date field is required."]
     *   }
     * }
     */
    public function submitDaily(SubmitDailyReportRequest $request): JsonResponse
    {
        try {
            $domain = $this->getAuthenticatedDomain($request);
            $dailyData = $request->validated();

            // Criar relatório diário
            $report = $this->createDailyReportUseCase->execute($domain->id, $dailyData);

            // Enfileirar processamento
            ProcessReportJob::dispatch($report->getId(), $dailyData);

            return response()->json([
                'success' => true,
                'message' => 'Daily report submitted successfully',
                'data' => [
                    'id' => $report->getId(),
                    'domain_id' => $domain->id,
                    'report_date' => $report->getReportDate()->format('Y-m-d'),
                    'status' => $report->getStatus(),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting daily report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a new report from a domain
     * 
     * @group Reports
     * @bodyParam source.domain string required The domain submitting the report Example: zip.50g.io
     * @bodyParam source.site_id string required The site ID Example: wp-prod-zip50gio-001
     * @bodyParam source.site_name string required The site name Example: SmarterHome.ai
     * @bodyParam metadata.report_date string required Report date Example: 2025-10-13
     * @bodyParam metadata.report_period.start string required Period start Example: 2025-10-13 00:00:00
     * @bodyParam metadata.report_period.end string required Period end Example: 2025-10-13 23:59:59
     * @bodyParam metadata.generated_at string required Generation timestamp Example: 2025-10-13 18:54:50
     * @bodyParam metadata.data_version string required Data version Example: 2.0.0
     * @bodyParam summary object required Report summary data
     * @bodyParam providers object optional Provider metrics
     * @bodyParam geographic object optional Geographic metrics
     * @response 201 {
     *   "success": true,
     *   "message": "Report received and queued for processing",
     *   "data": {
     *     "id": 1,
     *     "domain_id": 1,
     *     "report_date": "2025-10-13",
     *     "status": "pending"
     *   }
     * }
     * @response 401 {"success": false, "message": "Unauthorized - Invalid API key"}
     * @response 400 {"success": false, "message": "Invalid report structure", "errors": {...}}
     */
    public function submit(SubmitReportRequest $request): JsonResponse
    {
        try {
            // Get authenticated domain (via API key middleware)
            $domain = $this->getAuthenticatedDomain($request);
            
            // Validate that source domain matches authenticated domain
            $sourceDomain = $request->input('source.domain');
            if ($domain->name !== $sourceDomain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain mismatch - authenticated domain does not match source domain'
                ], 403);
            }

            // Create report entity
            $report = $this->createReportUseCase->execute(
                $domain->id,
                $request->validated()
            );
            
            // Queue for async processing
            ProcessReportJob::dispatch($report->getId(), $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Report received and queued for processing',
                'data' => $report->toDto()->toArray()
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process report submission',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all reports with pagination and filters
     * 
     * @group Reports
     * @queryParam page integer Page number Example: 1
     * @queryParam per_page integer Items per page (1-100) Example: 15
     * @queryParam domain_id integer Filter by domain ID Example: 1
     * @queryParam status string Filter by status (pending,processing,processed,failed) Example: processed
     * @queryParam start_date string Filter by start date Example: 2025-10-01
     * @queryParam end_date string Filter by end date Example: 2025-10-31
     * @response {
     *   "success": true,
     *   "data": [...],
     *   "meta": {
     *     "total": 150,
     *     "per_page": 15,
     *     "current_page": 1,
     *     "last_page": 10
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $domainId = $request->get('domain_id') ? (int) $request->get('domain_id') : null;
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $result = $this->getAllReportsUseCase->executePaginated(
            $page,
            $perPage,
            $domainId,
            $status,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => array_map(fn($report) => $report->toDto()->toArray(), $result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'from' => $result['from'],
                'to' => $result['to'],
            ]
        ]);
    }

    /**
     * Get a specific report by ID with processed statistics
     * 
     * @group Reports
     * @urlParam id integer required Report ID Example: 1
     * @response {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "domain": {"id": 1, "name": "zip.50g.io"},
     *     "report_date": "2025-10-13",
     *     "status": "processed",
     *     "summary": {...},
     *     "providers": [...],
     *     "geographic": {...},
     *     "raw_data": {...}
     *   }
     * }
     * @response 404 {"success": false, "message": "Report not found"}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $reportData = $this->getReportWithStatsUseCase->execute($id);
            
            return response()->json([
                'success' => true,
                'data' => $reportData
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
        }
    }

    /**
     * Get recent reports (last 10)
     * 
     * @group Reports
     * @response {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function recent(): JsonResponse
    {
        $reports = $this->getAllReportsUseCase->executeRecent(10);
        
        return response()->json([
            'success' => true,
            'data' => array_map(fn($report) => $report->toDto()->toArray(), $reports)
        ]);
    }

    /**
     * Get dashboard data for a specific domain
     * 
     * @group Admin Reports
     * @urlParam domain_id integer required The domain ID Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "domain": {"id": 1, "name": "zip.50g.io"},
     *     "kpis": {...},
     *     "provider_distribution": [...],
     *     "top_states": [...],
     *     "hourly_distribution": [...],
     *     "speed_by_state": [...],
     *     "technology_distribution": [...],
     *     "exclusion_by_provider": [...]
     *   }
     * }
     */
    public function dashboard(int $domainId): JsonResponse
    {
        try {
            $dashboardData = $this->getDashboardDataUseCase->execute($domainId);

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domain not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get aggregated statistics for a specific domain
     * 
     * @group Admin Reports
     * @urlParam domain_id integer required The domain ID Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "domain": {"id": 1, "name": "zip.50g.io"},
     *     "period": {
     *       "total_reports": 5,
     *       "first_report": "2025-10-01",
     *       "last_report": "2025-10-05",
     *       "days_covered": 5
     *     },
     *     "summary": {...},
     *     "providers": [...],
     *     "geographic": {...},
     *     "trends": [...]
     *   }
     * }
     */
    public function aggregate(int $domainId): JsonResponse
    {
        try {
            $stats = $this->getAggregatedReportStatsUseCase->execute($domainId);

            return response()->json([
                'success' => true,
                'data' => $stats->toArray(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domain not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error aggregating report statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated domain from API key
     */
    private function getAuthenticatedDomain(Request $request): Domain
    {
        // Try to get from Authorization header first
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $apiKey = substr($authHeader, 7);
            $domain = Domain::where('api_key', $apiKey)->where('is_active', true)->first();
            if ($domain) {
                return $domain;
            }
        }

        // Fallback to X-API-Key header
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            $domain = Domain::where('api_key', $apiKey)->where('is_active', true)->first();
            if ($domain) {
                return $domain;
            }
        }

        abort(401, 'Invalid or missing API key');
    }

    /**
     * Get global domain ranking
     * 
     * @group Global Reports
     * @authenticated
     */
    public function globalRanking(Request $request): JsonResponse
    {
        try {
            $sortBy = $request->query('sort_by', 'score');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $minReports = $request->query('min_reports') ? (int) $request->query('min_reports') : null;

            // Validate sort_by parameter
            if (!in_array($sortBy, ['score', 'volume', 'success', 'speed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid sort_by parameter. Must be one of: score, volume, success, speed',
                ], 400);
            }

            // Get accessible domains for this admin
            $admin = $request->user();
            $accessibleDomains = $admin->getAccessibleDomains();

            $ranking = $this->getGlobalDomainRankingUseCase->execute(
                $sortBy,
                $dateFrom,
                $dateTo,
                $minReports,
                $accessibleDomains // Filter by accessible domains
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'ranking' => array_map(fn($dto) => $dto->toArray(), $ranking),
                    'sort_by' => $sortBy,
                    'total_domains' => count($ranking),
                    'filters' => [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'min_reports' => $minReports,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting global domain ranking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Compare domains
     * 
     * @group Global Reports
     * @authenticated
     */
    public function compareDomains(Request $request): JsonResponse
    {
        try {
            $domainIdsParam = $request->query('domains');
            
            if (!$domainIdsParam) {
                return response()->json([
                    'success' => false,
                    'message' => 'domains parameter is required. Example: ?domains=1,2,3',
                ], 400);
            }

            // Parse domain IDs
            $domainIds = array_map('intval', explode(',', $domainIdsParam));

            if (empty($domainIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one domain ID is required',
                ], 400);
            }

            // Filter by accessible domains
            $admin = $request->user();
            $accessibleDomains = $admin->getAccessibleDomains();
            
            // Verify all requested domains are accessible
            foreach ($domainIds as $domainId) {
                if (!in_array($domainId, $accessibleDomains)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Access denied to domain ID {$domainId}",
                    ], 403);
                }
            }

            $metric = $request->query('metric');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $comparison = $this->compareDomainsUseCase->execute(
                $domainIds,
                $metric,
                $dateFrom,
                $dateTo
            );

            if (empty($comparison)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found for the specified domains',
                ], 404);
            }

            // Get aggregated provider data
            $providerData = $this->compareDomainsUseCase->getAggregatedProviderData(
                $domainIds,
                $dateFrom,
                $dateTo
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'domains' => array_map(fn($dto) => $dto->toArray(), $comparison),
                    'total_compared' => count($comparison),
                    'provider_data' => $providerData,
                    'filters' => [
                        'metric' => $metric,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error comparing domains',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get provider ranking across domains
     * 
     * @group Global Reports
     * @authenticated
     */
    public function providerRanking(Request $request): JsonResponse
    {
        try {
            $providerId = $request->query('provider_id') ? (int) $request->query('provider_id') : null;
            $technology = $request->query('technology');
            $period = $request->query('period'); // today, yesterday, last_week, last_month, last_year, all_time
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $sortBy = $request->query('sort_by', 'total_requests');
            $limit = $request->query('limit') ? (int) $request->query('limit') : null;
            $page = $request->query('page') ? (int) $request->query('page') : 1;
            $perPage = $request->query('per_page') ? (int) $request->query('per_page') : 15;

            // Validate sort_by parameter
            if (!in_array($sortBy, ['total_requests', 'success_rate', 'avg_speed', 'total_reports'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid sort_by parameter. Must be one of: total_requests, success_rate, avg_speed, total_reports',
                ], 400);
            }

            // Convert period to date range
            if ($period) {
                $dateRange = $this->getPeriodDateRange($period);
                if (!$dateRange) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid period parameter. Must be one of: today, yesterday, last_week, last_month, last_year, all_time',
                    ], 400);
                }
                
                // Period overrides manual dates
                $dateFrom = $dateRange['from'];
                $dateTo = $dateRange['to'];
            }

            // Get accessible domains for this admin
            $admin = $request->user();
            $accessibleDomains = $admin->getAccessibleDomains();

            // Get available providers for filtering
            $availableProviders = $this->getAvailableProviders($accessibleDomains, $dateFrom, $dateTo);
            
            // Use pagination if page is specified, otherwise use limit (backward compatible)
            if ($request->has('page') || $request->has('per_page')) {
                $result = $this->getProviderRankingUseCase->executePaginated(
                    $page,
                    $perPage,
                    $providerId,
                    $technology,
                    $dateFrom,
                    $dateTo,
                    $sortBy,
                    $accessibleDomains
                );
                
                // Calculate aggregated stats
                $aggregatedStats = $this->calculateAggregatedStats($result['data']);
                
                // Calculate global stats if provider is filtered
                $globalStats = null;
                if ($providerId) {
                    $globalStats = $this->calculateGlobalStats($providerId, $dateFrom, $dateTo, $accessibleDomains);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => array_map(fn($dto) => $dto->toArray(), $result['data']),
                    'pagination' => $result['pagination'],
                    'available_providers' => $availableProviders,
                    'aggregated_stats' => $aggregatedStats,
                    'global_stats' => $globalStats,
                    'filters' => [
                        'provider_id' => $providerId,
                        'technology' => $technology,
                        'period' => $period,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'sort_by' => $sortBy,
                    ],
                ]);
            } else {
                // Backward compatible: use limit
                $ranking = $this->getProviderRankingUseCase->execute(
                    $providerId,
                    $technology,
                    $dateFrom,
                    $dateTo,
                    $sortBy,
                    $limit,
                    $accessibleDomains
                );
                
                // Calculate aggregated stats
                $aggregatedStats = $this->calculateAggregatedStats($ranking);
                
                // Calculate global stats if provider is filtered
                $globalStats = null;
                if ($providerId) {
                    $globalStats = $this->calculateGlobalStats($providerId, $dateFrom, $dateTo, $accessibleDomains);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'ranking' => array_map(fn($dto) => $dto->toArray(), $ranking),
                        'total_entries' => count($ranking),
                        'filters' => [
                            'provider_id' => $providerId,
                            'technology' => $technology,
                            'period' => $period,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'sort_by' => $sortBy,
                            'limit' => $limit,
                        ],
                    ],
                    'available_providers' => $availableProviders,
                    'aggregated_stats' => $aggregatedStats,
                    'global_stats' => $globalStats,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting provider ranking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Convert period string to date range
     */
    private function getPeriodDateRange(string $period): ?array
    {
        $now = now();
        
        return match($period) {
            'today' => [
                'from' => $now->toDateString(),
                'to' => $now->toDateString(),
            ],
            'yesterday' => [
                'from' => $now->copy()->subDay()->toDateString(),
                'to' => $now->copy()->subDay()->toDateString(),
            ],
            'last_week' => [
                'from' => $now->copy()->subWeek()->toDateString(),
                'to' => $now->toDateString(),
            ],
            'last_month' => [
                'from' => $now->copy()->subMonth()->toDateString(),
                'to' => $now->toDateString(),
            ],
            'last_year' => [
                'from' => $now->copy()->subYear()->toDateString(),
                'to' => $now->toDateString(),
            ],
            'all_time' => [
                'from' => null,
                'to' => null,
            ],
            default => null,
        };
    }

    /**
     * Get available providers with their IDs
     */
    private function getAvailableProviders(?array $accessibleDomainIds, ?string $dateFrom, ?string $dateTo): array
    {
        $query = \Illuminate\Support\Facades\DB::table('report_providers as rp')
            ->join('providers as p', 'rp.provider_id', '=', 'p.id')
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
        
        return $query
            ->select(
                'p.id',
                'p.name',
                'p.slug',
                \Illuminate\Support\Facades\DB::raw('SUM(rp.total_count) as total_requests')
            )
            ->groupBy('p.id', 'p.name', 'p.slug')
            ->orderBy('total_requests', 'desc')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'total_requests' => (int) $item->total_requests,
            ])
            ->toArray();
    }

    /**
     * Calculate aggregated stats from ranking data
     */
    private function calculateAggregatedStats(array $rankings): array
    {
        if (empty($rankings)) {
            return [
                'total_requests' => 0,
                'avg_success_rate' => 0,
                'avg_speed' => 0,
                'unique_domains' => 0,
                'unique_providers' => 0,
            ];
        }
        
        $totalRequests = 0;
        $successRates = [];
        $speeds = [];
        $uniqueDomains = [];
        $uniqueProviders = [];
        
        foreach ($rankings as $dto) {
            $totalRequests += $dto->totalRequests;
            $successRates[] = $dto->avgSuccessRate;
            $speeds[] = $dto->avgSpeed;
            $uniqueDomains[$dto->domainId] = true;
            $uniqueProviders[$dto->providerId] = true;
        }
        
        return [
            'total_requests' => $totalRequests,
            'avg_success_rate' => !empty($successRates) ? array_sum($successRates) / count($successRates) : 0,
            'avg_speed' => !empty($speeds) ? array_sum($speeds) / count($speeds) : 0,
            'unique_domains' => count($uniqueDomains),
            'unique_providers' => count($uniqueProviders),
        ];
    }

    /**
     * Calculate global stats when filtering by specific provider
     */
    private function calculateGlobalStats(int $providerId, ?string $dateFrom, ?string $dateTo, ?array $accessibleDomainIds): array
    {
        $query = \Illuminate\Support\Facades\DB::table('report_providers as rp')
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
        
        // Total geral (todos providers)
        $globalTotal = $query->sum('rp.total_count');
        
        // Total do provider específico
        $providerTotal = (clone $query)
            ->where('rp.provider_id', $providerId)
            ->sum('rp.total_count');
        
        $percentageOfGlobal = $globalTotal > 0 ? ($providerTotal / $globalTotal) * 100 : 0;
        
        return [
            'provider_total_requests' => (int) $providerTotal,
            'global_total_requests' => (int) $globalTotal,
            'percentage_of_global' => round($percentageOfGlobal, 2),
        ];
    }
}


