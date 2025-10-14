<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\Report\CreateReportUseCase;
use App\Application\UseCases\Report\GetAllReportsUseCase;
use App\Application\UseCases\Report\GetReportByIdUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitReportRequest;
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
        private GetReportByIdUseCase $getReportByIdUseCase
    ) {}

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
     * Get a specific report by ID
     * 
     * @group Reports
     * @urlParam id integer required Report ID Example: 1
     * @response {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "domain_id": 1,
     *     "report_date": "2025-10-13",
     *     "status": "processed",
     *     "raw_data": {...}
     *   }
     * }
     * @response 404 {"success": false, "message": "Report not found"}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $report = $this->getReportByIdUseCase->execute($id);
            
            return response()->json([
                'success' => true,
                'data' => $report->toDto()->toArray()
            ]);
            
        } catch (\App\Domain\Exceptions\NotFoundException $e) {
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
}
