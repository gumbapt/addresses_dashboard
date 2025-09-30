<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\UseCases\Admin\Authorization\CreateRoleUseCase;
use App\Application\UseCases\Admin\Authorization\GetRolesUseCase;
use App\Application\UseCases\Admin\Authorization\AttachPermissionsToRoleUseCase;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    
    public function __construct(
        private GetRolesUseCase $getRolesUseCase,
        private CreateRoleUseCase $createRoleUseCase,
        private AttachPermissionsToRoleUseCase $attachPermissionsToRoleUseCase
    ) {}

    public function index(): JsonResponse
    {
        try {
            $roles = $this->getRolesUseCase->execute();
            $roles = array_map(function ($role) {
                return $role->toDto();
            }, $roles);
            return response()->json($roles, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
        $name = $request->input('name');
        $description = $request->input('description');
        $permissionsIds = $request->input('permissions') ?? [];
        $role = $this->createRoleUseCase->execute($name, $description);
        if(count($permissionsIds) > 0){
            $role = $this->attachPermissionsToRoleUseCase->execute($role->getId(), $permissionsIds);
            dd($role);
        }
            return response()->json(
                [
                    'success' => true,
                    'data' => [
                        'role' => $role->toDto()
                    ]
                ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
