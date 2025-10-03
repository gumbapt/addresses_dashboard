<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\UseCases\Admin\Authorization\CreateRoleUseCase;
use App\Application\UseCases\Admin\Authorization\GetRolesUseCase;
use App\Application\UseCases\Admin\Authorization\AttachPermissionsToRoleUseCase;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Domain\Exceptions\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    
    public function __construct(
        private GetRolesUseCase $getRolesUseCase,
        private CreateRoleUseCase $createRoleUseCase,
        private AttachPermissionsToRoleUseCase $attachPermissionsToRoleUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();
            $this->authorizeActionUseCase->execute($admin, 'role-read');
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
            $admin = $request->user();
            $this->authorizeActionUseCase->execute($admin, 'role-create');
            $name = $request->input('name');
            $description = $request->input('description');
            $permissionsIds = $request->input('permissions') ?? [];
            $role = $this->createRoleUseCase->execute($name, $description);
            if(count($permissionsIds) > 0){
                $this->authorizeActionUseCase->execute($admin, 'role-manage');
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
            $admin = $request->user();
            $this->authorizeActionUseCase->execute($admin, 'role-update');
            $id = $request->input('id');

        }catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
