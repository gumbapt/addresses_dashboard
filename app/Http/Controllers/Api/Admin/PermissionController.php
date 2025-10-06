<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\UseCases\Admin\Authorization\GetAllPermissionsUseCase;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\Services\UserFactory;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(
        private GetAllPermissionsUseCase $getAllPermissionsUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        $adminModel = $request->user();
        
        // Verificar se o usuário está autenticado ANTES de qualquer outra lógica
        if (!$adminModel) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        try {
            $admin = UserFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'role-manage');
            $permissions = $this->getAllPermissionsUseCase->execute();
            return response()->json([
                'success' => true,
                'data' => $permissions
            ], 200);
        } catch (\App\Domain\Exceptions\AuthorizationException $e) {
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
