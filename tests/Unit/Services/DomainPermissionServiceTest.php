<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domain\Services\DomainPermissionService;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Domain;
use App\Models\RoleDomainPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DomainPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainPermissionService $service;
    private Admin $admin;
    private Role $role;
    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(DomainPermissionService::class);
        
        $this->admin = Admin::factory()->create();
        $this->role = Role::factory()->create();
        $this->domain = Domain::factory()->create();
        
        $this->admin->roles()->attach($this->role, [
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);
    }

    public function test_hasGlobalDomainAccess_returns_true_for_global_permission(): void
    {
        $permission = Permission::factory()->create(['slug' => 'domain.access.all']);
        $this->role->permissions()->attach($permission);

        $result = $this->service->hasGlobalDomainAccess($this->admin);

        $this->assertTrue($result);
    }

    public function test_hasGlobalDomainAccess_returns_false_without_permission(): void
    {
        $result = $this->service->hasGlobalDomainAccess($this->admin);

        $this->assertFalse($result);
    }

    public function test_hasAssignedDomainAccess_returns_true_for_assigned_domain(): void
    {
        RoleDomainPermission::create([
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
            'can_view' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $result = $this->service->hasAssignedDomainAccess($this->admin, $this->domain->id);

        $this->assertTrue($result);
    }

    public function test_hasAssignedDomainAccess_returns_false_for_unassigned_domain(): void
    {
        $result = $this->service->hasAssignedDomainAccess($this->admin, $this->domain->id);

        $this->assertFalse($result);
    }

    public function test_canAccessDomain_returns_true_for_global_access(): void
    {
        $permission = Permission::factory()->create(['slug' => 'domain.access.all']);
        $this->role->permissions()->attach($permission);

        $result = $this->service->canAccessDomain($this->admin, $this->domain->id);

        $this->assertTrue($result);
    }

    public function test_canAccessDomain_returns_true_for_assigned_domain(): void
    {
        RoleDomainPermission::create([
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
            'can_view' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $result = $this->service->canAccessDomain($this->admin, $this->domain->id);

        $this->assertTrue($result);
    }

    public function test_canAccessDomain_returns_false_for_unassigned_domain(): void
    {
        $result = $this->service->canAccessDomain($this->admin, $this->domain->id);

        $this->assertFalse($result);
    }

    public function test_getAccessibleDomains_returns_all_for_global_access(): void
    {
        $permission = Permission::factory()->create(['slug' => 'domain.access.all']);
        $this->role->permissions()->attach($permission);

        $domain2 = Domain::factory()->create();
        $domain3 = Domain::factory()->create();

        $result = $this->service->getAccessibleDomains($this->admin);

        $this->assertCount(3, $result);
        $this->assertContains($this->domain->id, $result);
        $this->assertContains($domain2->id, $result);
        $this->assertContains($domain3->id, $result);
    }

    public function test_getAccessibleDomains_returns_assigned_only(): void
    {
        $domain2 = Domain::factory()->create();
        $domain3 = Domain::factory()->create();

        RoleDomainPermission::create([
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
            'can_view' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $result = $this->service->getAccessibleDomains($this->admin);

        $this->assertCount(1, $result);
        $this->assertContains($this->domain->id, $result);
        $this->assertNotContains($domain2->id, $result);
        $this->assertNotContains($domain3->id, $result);
    }

    public function test_assignDomainsToRole_creates_permissions(): void
    {
        $domain2 = Domain::factory()->create();
        
        $this->service->assignDomainsToRole(
            $this->role,
            [$this->domain->id, $domain2->id],
            $this->admin,
            ['can_view' => true, 'can_edit' => true]
        );

        $this->assertDatabaseHas('role_domain_permissions', [
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
            'can_view' => true,
            'can_edit' => true,
        ]);

        $this->assertDatabaseHas('role_domain_permissions', [
            'role_id' => $this->role->id,
            'domain_id' => $domain2->id,
            'can_view' => true,
            'can_edit' => true,
        ]);
    }

    public function test_revokeDomainsFromRole_removes_permissions(): void
    {
        RoleDomainPermission::create([
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
            'can_view' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $this->service->revokeDomainsFromRole($this->role, [$this->domain->id]);

        $this->assertDatabaseMissing('role_domain_permissions', [
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
        ]);
    }

    public function test_getRoleDomains_returns_assigned_domains(): void
    {
        $domain2 = Domain::factory()->create(['name' => 'test-domain.com']);
        
        RoleDomainPermission::create([
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
            'can_view' => true,
            'can_edit' => false,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        RoleDomainPermission::create([
            'role_id' => $this->role->id,
            'domain_id' => $domain2->id,
            'can_view' => true,
            'can_edit' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $result = $this->service->getRoleDomains($this->role);

        $this->assertCount(2, $result);
        $this->assertEquals($this->domain->id, $result[0]['domain_id']);
        $this->assertTrue($result[0]['can_view']);
        $this->assertFalse($result[0]['can_edit']);
    }

    public function test_getDomainPermissions_returns_all_for_global_access(): void
    {
        $permission = Permission::factory()->create(['slug' => 'domain.access.all']);
        $this->role->permissions()->attach($permission);

        $result = $this->service->getDomainPermissions($this->admin, $this->domain->id);

        $this->assertTrue($result['can_view']);
        $this->assertTrue($result['can_edit']);
        $this->assertTrue($result['can_delete']);
        $this->assertTrue($result['can_submit_reports']);
    }

    public function test_getDomainPermissions_returns_specific_for_assigned_domain(): void
    {
        RoleDomainPermission::create([
            'role_id' => $this->role->id,
            'domain_id' => $this->domain->id,
            'can_view' => true,
            'can_edit' => false,
            'can_delete' => false,
            'can_submit_reports' => true,
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $result = $this->service->getDomainPermissions($this->admin, $this->domain->id);

        $this->assertTrue($result['can_view']);
        $this->assertFalse($result['can_edit']);
        $this->assertFalse($result['can_delete']);
        $this->assertTrue($result['can_submit_reports']);
    }
}

