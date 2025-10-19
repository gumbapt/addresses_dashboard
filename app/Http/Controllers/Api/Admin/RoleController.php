<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\UseCases\Admin\Authorization\CreateRoleUseCase;
use App\Application\UseCases\Admin\Authorization\GetRolesUseCase;
use App\Application\UseCases\Admin\Authorization\AttachPermissionsToRoleUseCase;
use App\Application\UseCases\Admin\Authorization\UpdatePermissionsToRoleUseCase;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\UseCases\Admin\Authorization\UpdateRoleUseCase;
use App\Application\UseCases\Admin\Authorization\DeleteRoleUseCase;
use App\Application\Services\AdminFactory;
use App\Domain\Exceptions\AuthorizationException;
use App\Domain\Services\DomainPermissionService;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    
    public function __construct(
        private GetRolesUseCase $getRolesUseCase,
        private CreateRoleUseCase $createRoleUseCase,
        private UpdateRoleUseCase $updateRoleUseCase,
        private DeleteRoleUseCase $deleteRoleUseCase,
        private AttachPermissionsToRoleUseCase $attachPermissionsToRoleUseCase,
        private UpdatePermissionsToRoleUseCase $updatePermissionsToRoleUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $user = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($user, 'role-read');
            $roles = $this->getRolesUseCase->execute();
            $roles = array_map(function ($role) {
                return $role->toDto()->toArray();
            }, $roles);
            return response()->json($roles, 200);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $user = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($user, 'role-create');
            $name = $request->input('name');
            $description = $request->input('description');
            $permissionsIds = $request->input('permissions') ?? [];
            $role = $this->createRoleUseCase->execute($name, $description);
            if(count($permissionsIds) > 0){
                $this->authorizeActionUseCase->execute($user, 'role-manage');
                $role = $this->attachPermissionsToRoleUseCase->execute($role->getId(), $permissionsIds);
            }
            return response()->json(
                [
                    'success' => true,
                    'data' => [
                        'role' => $role->toDto()->toArray()
                    ]
                ], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $user = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($user, 'role-update');
            $id = $request->input('id');
            $name = $request->input('name');
            $description = $request->input('description');
            $role = $this->updateRoleUseCase->execute($id, $name, $description);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role->toDto()->toArray()
                ]
            ], 200);
            
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $user = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($user, 'role-delete');
            
            $id = $request->input('id');
            $this->deleteRoleUseCase->execute($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ], 200);
            
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updatePermissions(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $user = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($user, 'role-manage');
            $id = $request->input('id');
            $permissionsIds = $request->input('permissions') ?? [];
            $role = $this->updatePermissionsToRoleUseCase->execute($id, $permissionsIds);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role->toDto()->toArray()
                ]
            ], 200);
            
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign domains to a role
     */
    public function assignDomains(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|exists:roles,id',
                'domain_ids' => 'required|array',
                'domain_ids.*' => 'exists:domains,id',
                'permissions' => 'sometimes|array',
                'permissions.can_view' => 'sometimes|boolean',
                'permissions.can_edit' => 'sometimes|boolean',
                'permissions.can_delete' => 'sometimes|boolean',
                'permissions.can_submit_reports' => 'sometimes|boolean',
            ]);

            $role = Role::findOrFail($request->role_id);
            $admin = $request->user();

            $domainPermissionService = app(DomainPermissionService::class);
            $domainPermissionService->assignDomainsToRole(
                $role,
                $request->domain_ids,
                $admin,
                $request->permissions ?? []
            );

            $assignedDomains = Domain::whereIn('id', $request->domain_ids)->get();

            return response()->json([
                'success' => true,
                'message' => 'Domains assigned to role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'assigned_domains' => count($request->domain_ids),
                    'domains' => $assignedDomains->map(fn($d) => [
                        'id' => $d->id,
                        'name' => $d->name,
                        'slug' => $d->slug,
                    ]),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning domains to role',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Revoke domains from a role
     */
    public function revokeDomains(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|exists:roles,id',
                'domain_ids' => 'required|array',
                'domain_ids.*' => 'exists:domains,id',
            ]);

            $role = Role::findOrFail($request->role_id);

            $domainPermissionService = app(DomainPermissionService::class);
            $domainPermissionService->revokeDomainsFromRole($role, $request->domain_ids);

            return response()->json([
                'success' => true,
                'message' => 'Domains revoked from role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'revoked_domains' => count($request->domain_ids),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error revoking domains from role',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get domains assigned to a role
     */
    public function getDomains(int $roleId): JsonResponse
    {
        try {
            $role = Role::findOrFail($roleId);

            $domainPermissionService = app(DomainPermissionService::class);
            $domains = $domainPermissionService->getRoleDomains($role);

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                    ],
                    'domains' => $domains,
                    'total' => count($domains),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting role domains',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
