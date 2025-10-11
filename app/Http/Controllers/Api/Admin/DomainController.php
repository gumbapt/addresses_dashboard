<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\Services\AdminFactory;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\UseCases\ISP\CreateDomainUseCase;
use App\Application\UseCases\ISP\DeleteDomainUseCase;
use App\Application\UseCases\ISP\GetAllDomainsUseCase;
use App\Application\UseCases\ISP\GetDomainByIdUseCase;
use App\Application\UseCases\ISP\RegenerateApiKeyUseCase;
use App\Application\UseCases\ISP\UpdateDomainUseCase;
use App\Domain\Exceptions\AuthorizationException;
use App\Domain\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(
        private GetAllDomainsUseCase $getAllDomainsUseCase,
        private GetDomainByIdUseCase $getDomainByIdUseCase,
        private CreateDomainUseCase $createDomainUseCase,
        private UpdateDomainUseCase $updateDomainUseCase,
        private DeleteDomainUseCase $deleteDomainUseCase,
        private RegenerateApiKeyUseCase $regenerateApiKeyUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'domain-read');
            
            // Get pagination parameters
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 15);
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
            
            // Execute use case with pagination
            $result = $this->getAllDomainsUseCase->executePaginated(
                $page,
                $perPage,
                $search,
                $isActive
            );
            
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

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'domain-read');
            
            $domain = $this->getDomainByIdUseCase->execute($id);
            
            return response()->json([
                'success' => true,
                'data' => $domain
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

    public function create(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'domain-create');
            
            // Validate request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'domain_url' => 'required|string|max:255',
                'site_id' => 'nullable|string|max:255',
                'timezone' => 'nullable|string|max:50',
                'wordpress_version' => 'nullable|string|max:20',
                'plugin_version' => 'nullable|string|max:20',
                'settings' => 'nullable|array'
            ]);
            
            $domain = $this->createDomainUseCase->execute(
                $validated['name'],
                $validated['domain_url'],
                $validated['site_id'] ?? null,
                $validated['timezone'] ?? 'UTC',
                $validated['wordpress_version'] ?? null,
                $validated['plugin_version'] ?? null,
                $validated['settings'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Domain created successfully',
                'data' => $domain
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'domain-update');
            
            // Validate request
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'domain_url' => 'nullable|string|max:255',
                'site_id' => 'nullable|string|max:255',
                'is_active' => 'nullable|boolean',
                'timezone' => 'nullable|string|max:50',
                'wordpress_version' => 'nullable|string|max:20',
                'plugin_version' => 'nullable|string|max:20',
                'settings' => 'nullable|array'
            ]);
            
            $domain = $this->updateDomainUseCase->execute(
                $id,
                $validated['name'] ?? null,
                $validated['domain_url'] ?? null,
                $validated['site_id'] ?? null,
                $validated['is_active'] ?? null,
                $validated['timezone'] ?? null,
                $validated['wordpress_version'] ?? null,
                $validated['plugin_version'] ?? null,
                $validated['settings'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Domain updated successfully',
                'data' => $domain
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'domain-delete');
            
            $this->deleteDomainUseCase->execute($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Domain deleted successfully'
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

    public function regenerateApiKey(Request $request, int $id): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'domain-manage');
            
            $domain = $this->regenerateApiKeyUseCase->execute($id);
            
            return response()->json([
                'success' => true,
                'message' => 'API key regenerated successfully. Please update your integration immediately.',
                'data' => $domain
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

