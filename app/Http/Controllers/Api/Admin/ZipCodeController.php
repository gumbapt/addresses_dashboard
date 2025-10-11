<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\Services\AdminFactory;
use App\Application\UseCases\Geographic\GetAllZipCodesUseCase;
use App\Application\UseCases\Geographic\GetZipCodeByCodeUseCase;
use App\Domain\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZipCodeController extends Controller
{
    public function __construct(
        private GetAllZipCodesUseCase $getAllZipCodesUseCase,
        private GetZipCodeByCodeUseCase $getZipCodeByCodeUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            // Get pagination parameters
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 50);
            $search = $request->query('search');
            $stateId = $request->query('state_id');
            $cityId = $request->query('city_id');
            $isActive = $request->query('is_active');
            
            // Convert filters
            if ($stateId !== null && $stateId !== '') {
                $stateId = (int) $stateId;
            } else {
                $stateId = null;
            }
            
            if ($cityId !== null && $cityId !== '') {
                $cityId = (int) $cityId;
            } else {
                $cityId = null;
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
            $result = $this->getAllZipCodesUseCase->executePaginated(
                $page,
                $perPage,
                $search,
                $stateId,
                $cityId,
                $isActive
            );
            
            // Convert entities to DTOs
            $result['data'] = array_map(function ($zipCode) {
                return $zipCode->toDto()->toArray();
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

    public function show(Request $request, string $code): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            $zipCode = $this->getZipCodeByCodeUseCase->execute($code);
            
            return response()->json([
                'success' => true,
                'data' => $zipCode->toDto()->toArray()
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

    public function byState(Request $request, int $stateId): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            $zipCodes = $this->getAllZipCodesUseCase->executeByState($stateId);
            
            // Convert entities to DTOs
            $zipCodesArray = array_map(function ($zipCode) {
                return $zipCode->toDto()->toArray();
            }, $zipCodes);
            
            return response()->json([
                'success' => true,
                'data' => $zipCodesArray
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function byCity(Request $request, int $cityId): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            $zipCodes = $this->getAllZipCodesUseCase->executeByCity($cityId);
            
            // Convert entities to DTOs
            $zipCodesArray = array_map(function ($zipCode) {
                return $zipCode->toDto()->toArray();
            }, $zipCodes);
            
            return response()->json([
                'success' => true,
                'data' => $zipCodesArray
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}

