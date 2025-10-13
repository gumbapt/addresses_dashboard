<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\Services\AdminFactory;
use App\Application\UseCases\Provider\GetAllProvidersUseCase;
use App\Application\UseCases\Provider\GetProviderBySlugUseCase;
use App\Domain\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function __construct(
        private GetAllProvidersUseCase $getAllProvidersUseCase,
        private GetProviderBySlugUseCase $getProviderBySlugUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            // Get pagination parameters
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 15);
            $search = $request->query('search');
            $technology = $request->query('technology');
            $isActive = $request->query('is_active');
            
            // Convert filters
            if ($isActive !== null && $isActive !== '') {
                $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } else {
                $isActive = null;
            }
            
            // Validate limits
            $perPage = min(max($perPage, 1), 100);
            $page = max($page, 1);
            
            // Execute use case
            $result = $this->getAllProvidersUseCase->executePaginated(
                $page,
                $perPage,
                $search,
                $technology,
                $isActive
            );
            
            // Convert entities to DTOs
            $result['data'] = array_map(function ($provider) {
                return $provider->toDto()->toArray();
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
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            $provider = $this->getProviderBySlugUseCase->execute($slug);
            
            return response()->json([
                'success' => true,
                'data' => $provider->toDto()->toArray()
            ], 200);
        } catch (NotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function byTechnology(Request $request, string $technology): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            $providers = $this->getAllProvidersUseCase->executeByTechnology($technology);
            
            // Convert entities to DTOs
            $providersArray = array_map(function ($provider) {
                return $provider->toDto()->toArray();
            }, $providers);
            
            return response()->json([
                'success' => true,
                'data' => $providersArray
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function technologies(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            // Get all unique technologies from providers
            $technologies = [
                ['name' => 'Fiber', 'display_name' => 'Fiber Optic'],
                ['name' => 'Cable', 'display_name' => 'Cable Internet'],
                ['name' => 'Mobile', 'display_name' => 'Mobile/Cellular'],
                ['name' => 'DSL', 'display_name' => 'Digital Subscriber Line'],
                ['name' => 'Satellite', 'display_name' => 'Satellite Internet'],
                ['name' => 'Wireless', 'display_name' => 'Fixed Wireless'],
            ];
            
            return response()->json([
                'success' => true,
                'data' => $technologies
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
