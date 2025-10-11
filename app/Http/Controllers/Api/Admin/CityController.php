<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\Services\AdminFactory;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\UseCases\Geographic\GetAllCitiesUseCase;
use App\Application\UseCases\Geographic\FindOrCreateCityUseCase;
use App\Domain\Exceptions\AuthorizationException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function __construct(
        private GetAllCitiesUseCase $getAllCitiesUseCase,
        private FindOrCreateCityUseCase $findOrCreateCityUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            // Cities são dados de referência, qualquer admin pode ver
            
            // Get pagination parameters
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 50);
            $search = $request->query('search');
            $stateId = $request->query('state_id');
            $isActive = $request->query('is_active');
            
            // Convert filters
            if ($stateId !== null && $stateId !== '') {
                $stateId = (int) $stateId;
            } else {
                $stateId = null;
            }
            
            if ($isActive !== null && $isActive !== '') {
                $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } else {
                $isActive = null;
            }
            
            // Validate limits
            $perPage = min(max($perPage, 1), 100);
            $page = max($page, 1);
            
            // Execute use case
            $result = $this->getAllCitiesUseCase->executePaginated(
                $page,
                $perPage,
                $search,
                $stateId,
                $isActive
            );
            
            // Convert entities to DTOs
            $result['data'] = array_map(function ($city) {
                return $city->toDto()->toArray();
            }, $result['data']);
            
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to']
                ]
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function byState(Request $request, int $stateId): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            $cities = $this->getAllCitiesUseCase->executeByState($stateId);
            
            // Convert entities to DTOs
            $citiesArray = array_map(function ($city) {
                return $city->toDto()->toArray();
            }, $cities);
            
            return response()->json([
                'success' => true,
                'data' => $citiesArray
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}

