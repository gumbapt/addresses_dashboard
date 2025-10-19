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
        private CompareDomainsUseCase $compareDomainsUseCase
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

            return response()->json([
                'success' => true,
                'data' => [
                    'domains' => array_map(fn($dto) => $dto->toArray(), $comparison),
                    'total_compared' => count($comparison),
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
}
