<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\Services\UserFactory;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\UseCases\Admin\CreateAdminUseCase;
use App\Application\UseCases\Admin\DeleteAdminUseCase;
use App\Application\UseCases\Admin\GetAllAdminsUseCase;
use App\Application\UseCases\Admin\UpdateAdminUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private GetAllAdminsUseCase $getAllAdminsUseCase,
        private CreateAdminUseCase $createAdminUseCase,
        private UpdateAdminUseCase $updateAdminUseCase,
        private DeleteAdminUseCase $deleteAdminUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = UserFactory::createFromModel($adminModel);
            
            $this->authorizeActionUseCase->execute($admin, 'admin-read');
            
            $admins = $this->getAllAdminsUseCase->execute();
            
            return response()->json([
                'success' => true,
                'data' => $admins
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

    public function create(CreateAdminRequest $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = UserFactory::createFromModel($adminModel);
            
            $this->authorizeActionUseCase->execute($admin, 'admin-create');
            
            $newAdmin = $this->createAdminUseCase->execute(
                $request->input('name'),
                $request->input('email'),
                $request->input('password'),
                $request->input('is_active', true)
            );
            
            return response()->json([
                'success' => true,
                'data' => $newAdmin
            ], 201);
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

    public function update(UpdateAdminRequest $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = UserFactory::createFromModel($adminModel);
            
            $this->authorizeActionUseCase->execute($admin, 'admin-update');
            
            $updatedAdmin = $this->updateAdminUseCase->execute(
                $request->input('id'),
                $request->input('name'),
                $request->input('email'),
                $request->input('password'),
                $request->input('is_active')
            );
            
            return response()->json([
                'success' => true,
                'data' => $updatedAdmin
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

    public function delete(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = UserFactory::createFromModel($adminModel);
            
            $this->authorizeActionUseCase->execute($admin, 'admin-delete');
            
            $request->validate(['id' => 'required|integer|exists:admins,id']);
            
            $this->deleteAdminUseCase->execute($request->input('id'));
            
            return response()->json([
                'success' => true
            ], 200);
        } catch (\App\Domain\Exceptions\AuthorizationException $e) {
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
}

