<?php

namespace Tests\Unit\Authorization;

use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\UseCases\Admin\Authorization\CheckAdminPermissionUseCase;
use App\Domain\Exceptions\AuthorizationException;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizeActionUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private AuthorizeActionUseCase $authorizeActionUseCase;
    private Admin $admin;
    private Admin $superAdmin;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->authorizeActionUseCase = new AuthorizeActionUseCase(
            new CheckAdminPermissionUseCase(
                $this->app->make(\App\Domain\Repositories\PermissionRepositoryInterface::class)
            )
        );

        $this->superAdmin = Admin::factory()->create(['is_super_admin' => true]);
        $this->admin = Admin::factory()->create(['is_super_admin' => false]);
    }

    /**
     * @test
     */
    public function super_admin_can_always_perform_actions(): void
    {
        $this->authorizeActionUseCase->execute($this->superAdmin, 'any-permission');
        $this->assertTrue(true); // Se chegou até aqui, não lançou exceção
    }

    /**
     * @test
     */
    public function admin_without_permission_throws_authorization_exception(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Admin ' . $this->admin->id . ' does not have permission to perform this action. Required permission: test-permission');
        
        $this->authorizeActionUseCase->execute($this->admin, 'test-permission');
    }

    /**
     * @test
     */
    public function admin_with_permission_can_perform_action(): void
    {
        // Create permission and assign to admin
        $permission = Permission::create([
            'slug' => 'test-permission',
            'name' => 'Test Permission',
            'description' => 'Test permission',
            'resource' => 'test',
            'action' => 'test',
            'is_active' => true
        ]);

        $role = Role::create([
            'slug' => 'test-role',
            'name' => 'Test Role',
            'description' => 'Test role',
            'is_active' => true
        ]);

        $role->permissions()->attach($permission->id);
        $this->admin->roles()->attach($role->id);

        $this->authorizeActionUseCase->execute($this->admin, 'test-permission');
        $this->assertTrue(true); // Se chegou até aqui, não lançou exceção
    }
}
