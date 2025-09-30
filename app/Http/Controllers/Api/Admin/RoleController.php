<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\UseCases\Admin\Authorization\GetRolesUseCase;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    
    public function __construct(
        private GetRolesUseCase $getRolesUseCase
    ) {}

    public function index(): JsonResponse
    {
        try {
            $roles = $this->getRolesUseCase->execute();
/*             dd($roles);
 */            $roles = array_map(function ($role) {
                return $role->toDto();
            }, $roles);

            return response()->json($roles, 200);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
