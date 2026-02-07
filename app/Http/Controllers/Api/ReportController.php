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
use App\Application\UseCases\Report\Global\GetProviderRankingByStateUseCase;
use App\Application\UseCases\Report\GetDomainStateStatsUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitReportRequest;
use App\Http\Requests\SubmitDailyReportRequest;
use App\Jobs\ProcessReportJob;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
        private GetProviderRankingUseCase $getProviderRankingUseCase,
        private GetProviderRankingByStateUseCase $getProviderRankingByStateUseCase,
        private GetDomainStateStatsUseCase $getDomainStateStatsUseCase
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
        // Log no início para garantir que a função está sendo executada
        \Log::debug('🚀 submitDaily chamado', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'content_type' => $request->header('Content-Type'),
            'is_json' => $request->isJson(),
        ]);
        
        try {
            $domain = $this->getAuthenticatedDomain($request);
            
            // Salvar report em JSON ANTES da validação para preservar todos os campos (incluindo providers nos states)
            // IMPORTANTE: Usar getContent() para pegar o JSON raw e parsear manualmente
            // Isso garante que TODOS os campos sejam preservados, mesmo os que não estão nas regras de validação
            $rawJsonContent = $request->getContent();
            
            // Log do conteúdo raw recebido
            \Log::debug('📥 Conteúdo raw recebido', [
                'is_json' => $request->isJson(),
                'content_length' => strlen($rawJsonContent),
                'content_preview' => substr($rawJsonContent, 0, 1000), // Aumentar para ver mais
            ]);
            
            // Se o conteúdo é JSON, parsear diretamente
            if (!empty($rawJsonContent) && $request->isJson()) {
                $rawReportData = json_decode($rawJsonContent, true);
                
                // Verificar se parseou corretamente e tem a estrutura esperada
                if (json_last_error() === JSON_ERROR_NONE && isset($rawReportData['geographic'])) {
                    // Log simples: verificar se providers está presente no primeiro estado
                    if (isset($rawReportData['geographic']['states'][0])) {
                        $firstState = $rawReportData['geographic']['states'][0];
                        $hasProviders = isset($firstState['providers']) && is_array($firstState['providers']);
                        
                        if ($hasProviders) {
                            // Se tem providers, logar o array completo
                            \Log::debug('✅ Providers encontrado no primeiro estado', [
                                'state_code' => $firstState['code'] ?? 'unknown',
                                'providers' => $firstState['providers'],
                            ]);
                        } else {
                            // Se não tem providers, apenas informar
                            \Log::debug('❌ Providers NÃO encontrado no primeiro estado', [
                                'state_code' => $firstState['code'] ?? 'unknown',
                                'keys_disponiveis' => array_keys($firstState),
                            ]);
                        }
                    }
                } else {
                    // Fallback para all() se parse falhou
                    \Log::warning('⚠️ JSON parse falhou, usando request->all()', [
                        'json_error' => json_last_error_msg(),
                        'has_geographic' => isset($rawReportData['geographic']),
                    ]);
                    $rawReportData = $request->all();
                }
            } else {
                // Se não é JSON, usar all()
                \Log::warning('⚠️ Request não é JSON, usando request->all()', [
                    'content_type' => $request->header('Content-Type'),
                    'is_json' => $request->isJson(),
                    'content_empty' => empty($rawJsonContent),
                ]);
                $rawReportData = $request->all();
            }
            
            $reportDateForFile = $rawReportData['data']['date'] ?? $rawReportData['metadata']['report_date'] ?? null;
            $this->saveReportToJson($domain->name, $rawReportData, $reportDateForFile);
            
            // Validar dados para processamento
            $dailyData = $request->validated();
            
            // Criar relatório diário
            $report = $this->createDailyReportUseCase->execute($domain->id, $dailyData);

            // Enfileirar processamento
            ProcessReportJob::dispatch($report->getId(), $dailyData);

            // Handle report date (can be string or DateTime)
            $reportDate = $report->getReportDate();
            $reportDateFormatted = is_string($reportDate) ? $reportDate : $reportDate->format('Y-m-d');
            return response()->json([
                'success' => true,
                'message' => 'Daily report submitted successfully',
                'data' => [
                    'id' => $report->getId(),
                    'domain_id' => $domain->id,
                    'report_date' => $reportDateFormatted,
                    'status' => $report->getStatus(),
                ]
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Error in submitDaily', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error submitting daily report',
                'error' => $e->getMessage() ?? 'Unknown error',
                'file' => $e->getFile() ?? null,
                'line' => $e->getLine() ?? null,
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
        // Log no início para garantir que a função está sendo executada
        \Log::debug('🚀 submit chamado', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'content_type' => $request->header('Content-Type'),
            'is_json' => $request->isJson(),
        ]);
        
        try {
            // Get authenticated domain (via API key middleware)
            $domain = $this->getAuthenticatedDomain($request);
            
            // Salvar report em JSON ANTES da validação para preservar todos os campos (incluindo providers nos states)
            // IMPORTANTE: Usar getContent() para pegar o JSON raw e parsear manualmente
            $rawJsonContent = $request->getContent();
            
            // Log do conteúdo raw recebido
            \Log::debug('📥 Conteúdo raw recebido (submit)', [
                'is_json' => $request->isJson(),
                'content_length' => strlen($rawJsonContent),
                'content_preview' => substr($rawJsonContent, 0, 1000),
            ]);
            
            // Se o conteúdo é JSON, parsear diretamente
            if (!empty($rawJsonContent) && $request->isJson()) {
                $rawReportData = json_decode($rawJsonContent, true);
                
                // Verificar se parseou corretamente e tem a estrutura esperada
                if (json_last_error() === JSON_ERROR_NONE && isset($rawReportData['geographic'])) {
                    // Log simples: verificar se providers está presente no primeiro estado
                    if (isset($rawReportData['geographic']['states'][0])) {
                        $firstState = $rawReportData['geographic']['states'][0];
                        $hasProviders = isset($firstState['providers']) && is_array($firstState['providers']);
                        
                        if ($hasProviders) {
                            // Se tem providers, logar o array completo
                            // Log::debug('✅ Providers encontrado no primeiro estado (submit)', [
                            //     'state_code' => $firstState['code'] ?? 'unknown',
                            //     'providers' => $firstState['providers'],
                            // ]);
                        } else {
                            // Se não tem providers, apenas informar
                            // Log::debug('❌ Providers NÃO encontrado no primeiro estado (submit)', [
                            //     'state_code' => $firstState['code'] ?? 'unknown',
                            //     'keys_disponiveis' => array_keys($firstState),
                            // ]);
                        }
                    }
                } else {
                    // Fallback para all() se parse falhou
                    \Log::warning('⚠️ JSON parse falhou (submit), usando request->all()', [
                        'json_error' => json_last_error_msg(),
                        'has_geographic' => isset($rawReportData['geographic']),
                    ]);
                    $rawReportData = $request->all();
                }
            } else {
                // Se não é JSON, usar all()
                \Log::warning('⚠️ Request não é JSON (submit), usando request->all()', [
                    'content_type' => $request->header('Content-Type'),
                    'is_json' => $request->isJson(),
                    'content_empty' => empty($rawJsonContent),
                ]);
                $rawReportData = $request->all();
            }
            
            // Validate that source domain matches authenticated domain
            $sourceDomain = $request->input('source.domain');

            // Create report entity - usar rawReportData (não validado) para preservar providers
            $report = $this->createReportUseCase->execute(
                $domain->id,
                $rawReportData // Usar rawReportData em vez de validated() para preservar providers
            );
            
            // Salvar report em JSON usando dados raw (não validados)
            $reportDate = $rawReportData['metadata']['report_date'] ?? $rawReportData['metadata']['report_period']['start'] ?? null;
            $this->saveReportToJson($domain->name, $rawReportData, $reportDate);
            
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
    public function dashboard(int $domainId, Request $request): JsonResponse
    {
        try {
            $businessResidentialFilter = $request->query('business_residential_filter', 'all');
            if (!in_array($businessResidentialFilter, ['all', 'R', 'B', 'X'])) {
                $businessResidentialFilter = 'all';
            }
            $dashboardData = $this->getDashboardDataUseCase->execute($domainId, $businessResidentialFilter);

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
     * @queryParam period string optional Period filter: today, yesterday, last_week, last_month, last_year, all_time. When period=all_time, date_from/date_to can be used to limit the range.
     * @queryParam date_from string optional Start date (YYYY-MM-DD). Can be provided alone or with date_to. Used when period is not provided or when period=all_time
     * @queryParam date_to string optional End date (YYYY-MM-DD). Can be provided alone or with date_from. Used when period is not provided or when period=all_time
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
    public function aggregate(int $domainId, Request $request): JsonResponse
    {
        try {
            $period = $request->query('period');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            // Convert period to date range
            if ($period) {
                $dateRange = $this->getPeriodDateRange($period);
                if (!$dateRange) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid period parameter. Must be one of: today, yesterday, last_week, last_month, last_year, all_time',
                    ], 400);
                }
                
                // If period is all_time and custom dates are provided, use custom dates
                if ($period === 'all_time' && ($dateFrom || $dateTo)) {
                    // Validate date format if provided (each can be provided independently)
                    if ($dateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_from format. Use YYYY-MM-DD format (e.g., 2025-12-01)',
                        ], 400);
                    }
                    
                    if ($dateTo && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_to format. Use YYYY-MM-DD format (e.g., 2025-12-01)',
                        ], 400);
                    }
                    
                    // If both dates are provided, validate that date_from is not after date_to
                    if ($dateFrom && $dateTo && strtotime($dateFrom) > strtotime($dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'date_from must be before or equal to date_to',
                        ], 400);
                    }
                } else {
                    // Period overrides manual dates for non-all_time periods
                    $dateFrom = $dateRange['from'];
                    $dateTo = $dateRange['to'];
                }
            } else {
                // If no period, validate custom dates if provided (each can be provided independently)
                if ($dateFrom) {
                    // Validate date format (YYYY-MM-DD)
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_from format. Use YYYY-MM-DD format (e.g., 2025-12-01)',
                        ], 400);
                    }
                }
                
                if ($dateTo) {
                    // Validate date format (YYYY-MM-DD)
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_to format. Use YYYY-MM-DD format (e.g., 2025-12-01)',
                        ], 400);
                    }
                }
                
                // If both dates are provided, validate that date_from is not after date_to
                if ($dateFrom && $dateTo && strtotime($dateFrom) > strtotime($dateTo)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'date_from must be before or equal to date_to',
                    ], 400);
                }
            }

            $businessResidentialFilter = $request->query('business_residential_filter', 'all');
            if (!in_array($businessResidentialFilter, ['all', 'R', 'B', 'X'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid business_residential_filter. Must be: all, R, B, or X',
                ], 400);
            }

            $stats = $this->getAggregatedReportStatsUseCase->execute(
                $domainId,
                $dateFrom,
                $dateTo,
                $businessResidentialFilter
            );

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
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get dashboard and aggregate statistics filtered by state and domain
     * 
     * @group Admin Reports
     * @queryParam state_id integer required State ID to filter by
     * @queryParam period string optional Period filter: today, yesterday, last_week, last_month, last_year, all_time. When period=all_time, date_from/date_to can be used to limit the range.
     * @queryParam date_from string optional Start date (YYYY-MM-DD). Used when period is not provided or when period=all_time
     * @queryParam date_to string optional End date (YYYY-MM-DD). Used when period is not provided or when period=all_time
     * @queryParam sort_by string optional Sort criteria for providers: total_count, success_rate, avg_speed (default: total_count)
     * @queryParam cities_limit integer optional Number of top cities to return (default: 10, max: 100)
     * @urlParam domain_id integer required The domain ID Example: 1
     * @return JsonResponse
     */
    public function domainStateStats(int $domainId, Request $request): JsonResponse
    {
        try {
            $stateId = $request->query('state_id');
            $period = $request->query('period');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $sortBy = $request->query('sort_by', 'total_count');
            $citiesLimit = $request->query('cities_limit') ? (int) $request->query('cities_limit') : 10;

            // Validate state_id
            if (!$stateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'state_id parameter is required',
                ], 400);
            }

            $stateId = (int) $stateId;

            // Validate sort_by parameter
            if (!in_array($sortBy, ['total_count', 'total_requests', 'success_rate', 'avg_speed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid sort_by parameter. Must be one of: total_count, total_requests, success_rate, avg_speed',
                ], 400);
            }

            // Validate cities_limit parameter
            if ($citiesLimit < 1 || $citiesLimit > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid cities_limit parameter. Must be between 1 and 100',
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
                
                // If period is all_time and custom dates are provided, use custom dates
                if ($period === 'all_time' && ($dateFrom || $dateTo)) {
                    // Validate custom dates if provided
                    if (!$dateFrom || !$dateTo) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Both date_from and date_to must be provided when using custom date range',
                        ], 400);
                    }
                    
                    // Validate date format (YYYY-MM-DD)
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date format. Use YYYY-MM-DD format (e.g., 2025-11-01)',
                        ], 400);
                    }
                    
                    // Validate that date_from is not after date_to
                    if (strtotime($dateFrom) > strtotime($dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'date_from must be before or equal to date_to',
                        ], 400);
                    }
                } else {
                    // Period overrides manual dates for non-all_time periods
                    $dateFrom = $dateRange['from'];
                    $dateTo = $dateRange['to'];
                }
            } else {
                // If no period, validate custom date range if provided
                if ($dateFrom || $dateTo) {
                    // If one is provided, both must be provided
                    if (!$dateFrom || !$dateTo) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Both date_from and date_to must be provided when using custom date range',
                        ], 400);
                    }
                    
                    // Validate date format (YYYY-MM-DD)
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date format. Use YYYY-MM-DD format (e.g., 2025-11-01)',
                        ], 400);
                    }
                    
                    // Validate that date_from is not after date_to
                    if (strtotime($dateFrom) > strtotime($dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'date_from must be before or equal to date_to',
                        ], 400);
                    }
                }
            }

            $businessResidentialFilter = $request->query('business_residential_filter', 'all');
            if (!in_array($businessResidentialFilter, ['all', 'R', 'B', 'X'])) {
                $businessResidentialFilter = 'all';
            }

            $stats = $this->getDomainStateStatsUseCase->execute(
                $domainId,
                $stateId,
                $dateFrom,
                $dateTo,
                $sortBy,
                $citiesLimit,
                $businessResidentialFilter
            );

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domain or State not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading domain state statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
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
     * Save report to JSON file organized by domain and date
     * 
     * @param string $domainName
     * @param array $reportData
     * @param string|null $reportDate
     * @return void
     */
    private function saveReportToJson(string $domainName, array $reportData, ?string $reportDate = null): void
    {
        try {
            // Normalize domain name for filesystem (remove invalid chars for folder names)
            // Remove characters that are invalid in folder names: / \ : * ? " < > |
            $safeDomainName = preg_replace('/[\/\\\\:*?"<>|]/', '_', $domainName);
            
            // Replace spaces and other special chars with underscore
            $safeDomainName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $safeDomainName);
            
            // Remove leading/trailing dots and underscores
            $safeDomainName = trim($safeDomainName, '._');
            
            // Replace multiple consecutive underscores with single underscore
            $safeDomainName = preg_replace('/_+/', '_', $safeDomainName);
            
            // Ensure it's not empty (fallback to 'unknown_domain')
            if (empty($safeDomainName)) {
                $safeDomainName = 'unknown_domain';
            }
            
            // Limit length to avoid filesystem issues (max 255 chars for most systems)
            if (strlen($safeDomainName) > 200) {
                $safeDomainName = substr($safeDomainName, 0, 200);
            }
            
            // Extract date from report data if not provided
            if (!$reportDate) {
                // Try different date formats
                $reportDate = $reportData['metadata']['report_date'] 
                    ?? $reportData['data']['date'] 
                    ?? $reportData['metadata']['report_period']['start'] 
                    ?? date('Y-m-d');
            }
            
            // Parse date to ensure Y-m-d format
            if (strlen($reportDate) > 10) {
                $reportDate = substr($reportDate, 0, 10);
            }
            
            // Sanitize date to ensure it's safe for filesystem (only Y-m-d format)
            $reportDate = preg_replace('/[^0-9-]/', '', $reportDate);
            
            // Validate date format (should be YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
                // If invalid, use current date
                $reportDate = date('Y-m-d');
            }
            
            // Create directory path: /docs/submited_reports/{domain_name}/{date}/
            $basePath = base_path('docs/submited_reports');
            $domainPath = $basePath . '/' . $safeDomainName;
            $datePath = $domainPath . '/' . $reportDate;
            
            // Create directories if they don't exist
            if (!File::exists($datePath)) {
                File::makeDirectory($datePath, 0755, true);
            }
            
            // Generate filename with timestamp to avoid conflicts
            $timestamp = now()->format('His') . '_' . substr(microtime(true) * 10000, -4);
            $filename = "report_{$timestamp}.json";
            $filePath = $datePath . '/' . $filename;
            
            // Add metadata to the JSON (preservar estrutura original)
            $jsonData = $reportData;
            $jsonData['_saved_at'] = now()->toIso8601String();
            $jsonData['_domain'] = $domainName;
            $jsonData['_report_date'] = $reportDate;
            
            // Log ANTES de processar - verificar o que chegou na função
            if (isset($jsonData['geographic']['states'][0])) {
                \Log::debug('🔍 ANTES de processar - primeiro estado recebido', [
                    'state_code' => $jsonData['geographic']['states'][0]['code'] ?? 'unknown',
                    'has_providers' => isset($jsonData['geographic']['states'][0]['providers']),
                    'providers_count' => isset($jsonData['geographic']['states'][0]['providers']) 
                        ? count($jsonData['geographic']['states'][0]['providers']) 
                        : 0,
                    'providers_type' => isset($jsonData['geographic']['states'][0]['providers']) 
                        ? gettype($jsonData['geographic']['states'][0]['providers'])
                        : 'not_set',
                    'all_keys' => array_keys($jsonData['geographic']['states'][0]),
                ]);
            }
            
            // Garantir que todos os estados tenham o campo providers
            if (isset($jsonData['geographic']['states']) && is_array($jsonData['geographic']['states'])) {
                foreach ($jsonData['geographic']['states'] as &$state) {
                    // Se o estado não tem providers, criar array vazio
                    if (!isset($state['providers']) || !is_array($state['providers'])) {
                        $state['providers'] = [];
                        // Log::debug('➕ Campo providers criado para estado sem providers', [
                        //     'state_code' => $state['code'] ?? 'unknown',
                        // ]);
                    } else {
                        // Log::debug('✅ Estado já tem providers', [
                        //     'state_code' => $state['code'] ?? 'unknown',
                        //     'providers_count' => count($state['providers']),
                        // ]);
                    }
                }
                unset($state); // Liberar referência
            }
            
            // Log DEPOIS de processar - verificar o que será salvo
            if (isset($jsonData['geographic']['states'][0])) {
                // Log::debug('💾 DEPOIS de processar - primeiro estado antes de salvar', [
                //     'state_code' => $jsonData['geographic']['states'][0]['code'] ?? 'unknown',
                //     'has_providers' => isset($jsonData['geographic']['states'][0]['providers']),
                //     'providers_count' => isset($jsonData['geographic']['states'][0]['providers']) 
                //         ? count($jsonData['geographic']['states'][0]['providers']) 
                //         : 0,
                // ]);
            }
            
            // Save JSON file with pretty print
            File::put($filePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::warning('Failed to save report to JSON: ' . $e->getMessage(), [
                'domain' => $domainName,
                'date' => $reportDate,
                'error' => $e->getMessage()
            ]);
        }
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
            $aggregateByProvider = $request->query('aggregate_by_provider', false);
            $aggregateByProvider = filter_var($aggregateByProvider, FILTER_VALIDATE_BOOLEAN);

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
                    $accessibleDomains,
                    $aggregateByProvider
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
                        'aggregate_by_provider' => $aggregateByProvider,
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
                    $accessibleDomains,
                    $aggregateByProvider
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
                            'aggregate_by_provider' => $aggregateByProvider,
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
     * Get provider ranking by state (precise data)
     * 
     * @group Global Reports
     * @queryParam state_id integer required State ID to filter by
     * @queryParam provider_id integer optional Provider ID to filter by
     * @queryParam period string optional Period filter: today, yesterday, last_week, last_month, last_year, all_time. When period=all_time, date_from/date_to can be used to limit the range.
     * @queryParam date_from string optional Start date (YYYY-MM-DD). Used when period is not provided or when period=all_time
     * @queryParam date_to string optional End date (YYYY-MM-DD). Used when period is not provided or when period=all_time
     * @queryParam sort_by string optional Sort criteria: total_requests, success_rate, avg_speed, total_reports (default: total_requests)
     * @return JsonResponse
     */
    public function providerRankingByState(Request $request): JsonResponse
    {
        try {
            $stateId = $request->query('state_id');
            $providerId = $request->query('provider_id') ? (int) $request->query('provider_id') : null;
            $period = $request->query('period');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $sortBy = $request->query('sort_by', 'total_requests');
            $aggregateByProvider = $request->query('aggregate_by_provider', false);
            
            // Convert string 'true'/'false' to boolean
            if (is_string($aggregateByProvider)) {
                $aggregateByProvider = filter_var($aggregateByProvider, FILTER_VALIDATE_BOOLEAN);
            } else {
                $aggregateByProvider = (bool) $aggregateByProvider;
            }

            // Validate state_id
            if (!$stateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'state_id parameter is required',
                ], 400);
            }

            $stateId = (int) $stateId;

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
                
                // If period is all_time and custom dates are provided, use custom dates (allow override)
                if ($period === 'all_time' && ($dateFrom || $dateTo)) {
                    // Validate date format if provided (each can be provided independently)
                    if ($dateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_from format. Use YYYY-MM-DD format (e.g., 2025-11-01)',
                        ], 400);
                    }
                    
                    if ($dateTo && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_to format. Use YYYY-MM-DD format (e.g., 2025-11-01)',
                        ], 400);
                    }
                    
                    // If both dates are provided, validate that date_from is not after date_to
                    if ($dateFrom && $dateTo && strtotime($dateFrom) > strtotime($dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'date_from must be before or equal to date_to',
                        ], 400);
                    }
                    
                    // Use custom dates (ignore all_time)
                } else {
                    // Period overrides manual dates for non-all_time periods
                    $dateFrom = $dateRange['from'];
                    $dateTo = $dateRange['to'];
                }
            } else {
                // If no period, validate custom dates if provided (each can be provided independently)
                if ($dateFrom) {
                    // Validate date format (YYYY-MM-DD)
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_from format. Use YYYY-MM-DD format (e.g., 2025-11-01)',
                        ], 400);
                    }
                }
                
                if ($dateTo) {
                    // Validate date format (YYYY-MM-DD)
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date_to format. Use YYYY-MM-DD format (e.g., 2025-11-01)',
                        ], 400);
                    }
                }
                
                // If both dates are provided, validate that date_from is not after date_to
                if ($dateFrom && $dateTo && strtotime($dateFrom) > strtotime($dateTo)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'date_from must be before or equal to date_to',
                    ], 400);
                }
            }

            // Get accessible domains for this admin
            $admin = $request->user();
            $accessibleDomains = $admin->getAccessibleDomains();

            // Get ranking
            $ranking = $this->getProviderRankingByStateUseCase->execute(
                $stateId,
                $providerId,
                $dateFrom,
                $dateTo,
                $sortBy,
                $accessibleDomains,
                $aggregateByProvider
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'ranking' => $ranking,
                    'total_entries' => count($ranking),
                    'filters' => [
                        'state_id' => $stateId,
                        'provider_id' => $providerId,
                        'period' => $period,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'sort_by' => $sortBy,
                        'aggregate_by_provider' => $aggregateByProvider,
                    ],
                ],
                'note' => 'Data is precise (from report_state_providers table), not approximated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting provider ranking by state',
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


