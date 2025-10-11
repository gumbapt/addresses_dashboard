<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\Services\AdminFactory;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\UseCases\Geographic\GetAllStatesUseCase;
use App\Application\UseCases\Geographic\GetStateByCodeUseCase;
use App\Domain\Exceptions\AuthorizationException;
use App\Domain\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function __construct(
        private GetAllStatesUseCase $getAllStatesUseCase,
        private GetStateByCodeUseCase $getStateByCodeUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            // States são dados de referência públicos, qualquer admin autenticado pode ver
            // Não requer permissão específica
            
            // Get pagination parameters
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 50); // Default maior para estados
            $search = $request->query('search');
            $isActive = $request->query('is_active');
            
            // Convert string 'true'/'false' to boolean
            if ($isActive !== null && $isActive !== '') {
                $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } else {
                $isActive = null;
            }
            
            // Validate limits
            $perPage = min(max($perPage, 1), 100);
            $page = max($page, 1);
            
            // Execute use case
            $result = $this->getAllStatesUseCase->executePaginated(
                $page,
                $perPage,
                $search,
                $isActive
            );
            
            // Convert entities to DTOs
            $result['data'] = array_map(function ($state) {
                return $state->toDto()->toArray();
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

    public function all(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            // Get all active states (without pagination - for dropdowns, etc)
            $states = $this->getAllStatesUseCase->executeActive();
            
            // Convert entities to DTOs
            $statesArray = array_map(function ($state) {
                return $state->toDto()->toArray();
            }, $states);
            
            return response()->json([
                'success' => true,
                'data' => $statesArray
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

    public function showByCode(Request $request, string $code): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            
            $state = $this->getStateByCodeUseCase->execute($code);
            
            return response()->json([
                'success' => true,
                'data' => $state->toDto()->toArray()
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 403);
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
}

