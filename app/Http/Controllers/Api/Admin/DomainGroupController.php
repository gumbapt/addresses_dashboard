<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\UseCases\DomainGroup\CreateDomainGroupUseCase;
use App\Application\UseCases\DomainGroup\UpdateDomainGroupUseCase;
use App\Application\UseCases\DomainGroup\DeleteDomainGroupUseCase;
use App\Application\UseCases\DomainGroup\GetAllDomainGroupsUseCase;
use App\Application\UseCases\DomainGroup\GetDomainGroupByIdUseCase;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Exceptions\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\DomainGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DomainGroupController extends Controller
{
    public function __construct(
        private GetAllDomainGroupsUseCase $getAllDomainGroupsUseCase,
        private GetDomainGroupByIdUseCase $getDomainGroupByIdUseCase,
        private CreateDomainGroupUseCase $createDomainGroupUseCase,
        private UpdateDomainGroupUseCase $updateDomainGroupUseCase,
        private DeleteDomainGroupUseCase $deleteDomainGroupUseCase,
    ) {}

    /**
     * List all domain groups
     */
    public function index(Request $request)
    {
        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 15);
            $search = $request->get('search');
            $isActive = $request->get('is_active');
            
            // Convert string 'true'/'false' to boolean
            if ($isActive !== null && $isActive !== '') {
                $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } else {
                $isActive = null;
            }
            
            // Validate limits
            $perPage = min(max($perPage, 1), 100);
            $page = max($page, 1);

            $result = $this->getAllDomainGroupsUseCase->executePaginated($page, $perPage, $search, $isActive);
            
            // Buscar models com relacionamentos para enriquecer os dados
            $groupIds = array_column($result['data'], 'id');
            $groupsWithRelations = DomainGroup::with(['domains', 'creator', 'updater'])
                ->whereIn('id', $groupIds)
                ->get()
                ->keyBy('id');
            
            $enrichedData = array_map(function($groupEntity) use ($groupsWithRelations) {
                $groupModel = $groupsWithRelations[$groupEntity->id] ?? null;
                $data = $groupEntity->toDto()->toArray();
                
                if ($groupModel) {
                    $data['domains'] = $groupModel->domains->map(fn($d) => [
                        'id' => $d->id,
                        'name' => $d->name,
                        'slug' => $d->slug,
                        'domain_url' => $d->domain_url,
                        'is_active' => $d->is_active,
                    ])->toArray();
                    
                    $data['creator'] = $groupModel->creator ? [
                        'id' => $groupModel->creator->id,
                        'name' => $groupModel->creator->name,
                        'email' => $groupModel->creator->email,
                    ] : null;
                    
                    $data['updater'] = $groupModel->updater ? [
                        'id' => $groupModel->updater->id,
                        'name' => $groupModel->updater->name,
                        'email' => $groupModel->updater->email,
                    ] : null;
                }
                
                return $data;
            }, $result['data']);

            return response()->json([
                'success' => true,
                'data' => $enrichedData,
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve domain groups.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show single domain group
     */
    public function show($id)
    {
        try {
            $group = $this->getDomainGroupByIdUseCase->execute((int) $id);
            
            // Buscar model com relacionamentos
            $groupModel = DomainGroup::with(['domains', 'creator', 'updater'])->find($id);
            
            $data = [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'description' => $group->description,
                'is_active' => $group->isActive,
                'settings' => $group->settings,
                'max_domains' => $group->maxDomains,
                'domains_count' => $groupModel->domains->count(),
                'available_domains' => $groupModel->getAvailableDomainsCount(),
                'has_reached_limit' => $groupModel->hasReachedMaxDomains(),
                'domains' => $groupModel->domains->map(function($domain) {
                    return [
                        'id' => $domain->id,
                        'name' => $domain->name,
                        'slug' => $domain->slug,
                        'domain_url' => $domain->domain_url,
                        'is_active' => $domain->is_active,
                    ];
                }),
                'created_by' => $groupModel->creator ? [
                    'id' => $groupModel->creator->id,
                    'name' => $groupModel->creator->name,
                    'email' => $groupModel->creator->email,
                ] : null,
                'updated_by' => $groupModel->updater ? [
                    'id' => $groupModel->updater->id,
                    'name' => $groupModel->updater->name,
                    'email' => $groupModel->updater->email,
                ] : null,
                'created_at' => $group->createdAt,
                'updated_at' => $group->updatedAt,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (NotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve domain group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new domain group
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:domain_groups,name',
            'slug' => 'nullable|string|max:255|unique:domain_groups,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
            'max_domains' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // Gerar slug se não fornecido
            $slug = $data['slug'] ?? Str::slug($data['name']);

            $group = $this->createDomainGroupUseCase->execute(
                name: $data['name'],
                slug: $slug,
                description: $data['description'] ?? null,
                isActive: $data['is_active'] ?? true,
                settings: $data['settings'] ?? null,
                maxDomains: $data['max_domains'] ?? null,
                createdBy: $request->user()->id
            );
            
            // Buscar model para retornar com relacionamentos
            $groupModel = DomainGroup::with(['domains', 'creator'])->find($group->id);

            return response()->json([
                'success' => true,
                'message' => 'Domain group created successfully.',
                'data' => $groupModel,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create domain group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update domain group
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:domain_groups,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:domain_groups,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
            'max_domains' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // Atualizar slug se o nome mudou
            $slug = null;
            if (isset($data['name']) && !isset($data['slug'])) {
                $slug = Str::slug($data['name']);
            } elseif (isset($data['slug'])) {
                $slug = $data['slug'];
            }

            $group = $this->updateDomainGroupUseCase->execute(
                id: (int) $id,
                name: $data['name'] ?? null,
                slug: $slug,
                description: $data['description'] ?? null,
                isActive: $data['is_active'] ?? null,
                settings: $data['settings'] ?? null,
                maxDomains: $data['max_domains'] ?? null,
                updatedBy: $request->user()->id
            );
            
            // Buscar model para retornar com relacionamentos
            $groupModel = DomainGroup::with(['domains', 'creator', 'updater'])->find($group->id);

            return response()->json([
                'success' => true,
                'message' => 'Domain group updated successfully.',
                'data' => $groupModel,
            ]);
        } catch (NotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update domain group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete domain group
     */
    public function destroy($id)
    {
        try {
            $this->deleteDomainGroupUseCase->execute((int) $id);

            return response()->json([
                'success' => true,
                'message' => 'Domain group deleted successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete domain group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get domains of a specific group
     */
    public function domains($id)
    {
        try {
            $group = $this->getDomainGroupByIdUseCase->execute((int) $id);
            
            // Buscar model com domains
            $groupModel = DomainGroup::with('domains')->find($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'group_name' => $group->name,
                    'domains' => $groupModel->domains,
                    'total' => $groupModel->domains->count(),
                    'max_domains' => $group->maxDomains,
                    'available' => $groupModel->getAvailableDomainsCount(),
                ],
            ]);
        } catch (NotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve domains.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
