<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use App\Models\RoleDomainPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DomainPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $domainManager;
    private Role $superAdminRole;
    private Role $domainManagerRole;
    private array $domains;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $globalPermission = Permission::factory()->create([
            'slug' => 'domain.access.all',
            'name' => 'Access All Domains',
            'resource' => 'domain',
            'action' => 'access-all',
        ]);

        $assignedPermission = Permission::factory()->create([
            'slug' => 'domain.access.assigned',
            'name' => 'Access Assigned Domains',
            'resource' => 'domain',
            'action' => 'access-assigned',
        ]);

        // Create roles
        $this->superAdminRole = Role::factory()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Super administrator',
        ]);
        $this->superAdminRole->permissions()->attach($globalPermission);

        $this->domainManagerRole = Role::factory()->create([
            'name' => 'Domain Manager',
            'slug' => 'domain-manager',
            'description' => 'Domain manager',
        ]);
        $this->domainManagerRole->permissions()->attach($assignedPermission);

        // Create admins
        $this->superAdmin = Admin::factory()->create([
            'email' => 'super@test.com',
            'is_active' => true,
        ]);
        $this->superAdmin->roles()->attach($this->superAdminRole, [
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        $this->domainManager = Admin::factory()->create([
            'email' => 'manager@test.com',
            'is_active' => true,
        ]);
        $this->domainManager->roles()->attach($this->domainManagerRole, [
            'assigned_at' => now(),
            'assigned_by' => 1,
        ]);

        // Create domains
        $this->domains = [
            Domain::factory()->create(['name' => 'domain1.com']),
            Domain::factory()->create(['name' => 'domain2.com']),
            Domain::factory()->create(['name' => 'domain3.com']),
        ];

        // Assign domains 1 and 2 to domain manager role
        RoleDomainPermission::create([
            'role_id' => $this->domainManagerRole->id,
            'domain_id' => $this->domains[0]->id,
            'can_view' => true,
            'assigned_at' => now(),
            'assigned_by' => $this->superAdmin->id,
        ]);
        RoleDomainPermission::create([
            'role_id' => $this->domainManagerRole->id,
            'domain_id' => $this->domains[1]->id,
            'can_view' => true,
            'assigned_at' => now(),
            'assigned_by' => $this->superAdmin->id,
        ]);
    }

    public function test_super_admin_can_access_all_domains(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/admin/my-domains');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'access_type' => 'all',
                    'total' => 3,
                ],
            ]);
    }

    public function test_domain_manager_can_access_only_assigned_domains(): void
    {
        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson('/api/admin/my-domains');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'access_type' => 'assigned',
                    'total' => 2,
                ],
            ]);

        $domainIds = collect($response->json('data.domains'))->pluck('id')->toArray();
        $this->assertContains($this->domains[0]->id, $domainIds);
        $this->assertContains($this->domains[1]->id, $domainIds);
        $this->assertNotContains($this->domains[2]->id, $domainIds);
    }

    public function test_domain_manager_can_access_allowed_domain_dashboard(): void
    {
        // For a domain with no reports, dashboard should return empty but successful
        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson("/api/admin/reports/domain/{$this->domains[0]->id}/dashboard");

        // Should not be blocked (200), even if no data
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_domain_manager_cannot_access_unassigned_domain_dashboard(): void
    {
        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson("/api/admin/reports/domain/{$this->domains[2]->id}/dashboard");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied. You do not have permission to access this domain.',
            ]);
    }

    public function test_domain_ranking_respects_permissions_for_super_admin(): void
    {
        // Create reports for all domains
        foreach ($this->domains as $domain) {
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            ReportSummary::factory()->create(['report_id' => $report->id]);
        }

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.total_domains'));
    }

    public function test_domain_ranking_respects_permissions_for_domain_manager(): void
    {
        // Create reports for all domains
        foreach ($this->domains as $domain) {
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            ReportSummary::factory()->create(['report_id' => $report->id]);
        }

        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.total_domains'));
        
        $domainIds = collect($response->json('data.ranking'))->pluck('domain.id')->toArray();
        $this->assertContains($this->domains[0]->id, $domainIds);
        $this->assertContains($this->domains[1]->id, $domainIds);
        $this->assertNotContains($this->domains[2]->id, $domainIds);
    }

    public function test_domain_manager_can_compare_allowed_domains(): void
    {
        // Create reports
        foreach ([$this->domains[0], $this->domains[1]] as $domain) {
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            ReportSummary::factory()->create(['report_id' => $report->id]);
        }

        $domainIds = "{$this->domains[0]->id},{$this->domains[1]->id}";
        
        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson("/api/admin/reports/global/comparison?domains={$domainIds}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertEquals(2, $response->json('data.total_compared'));
    }

    public function test_domain_manager_cannot_compare_unassigned_domain(): void
    {
        $domainIds = "{$this->domains[0]->id},{$this->domains[2]->id}";
        
        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson("/api/admin/reports/global/comparison?domains={$domainIds}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => "Access denied to domain ID {$this->domains[2]->id}",
            ]);
    }

    public function test_assign_domains_to_role(): void
    {
        $newRole = Role::factory()->create();
        $assignedPermission = Permission::where('slug', 'domain.access.assigned')->first();
        $newRole->permissions()->attach($assignedPermission);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/admin/role/assign-domains', [
                'role_id' => $newRole->id,
                'domain_ids' => [$this->domains[0]->id],
                'permissions' => [
                    'can_view' => true,
                    'can_edit' => false,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Domains assigned to role successfully',
                'data' => [
                    'assigned_domains' => 1,
                ],
            ]);

        $this->assertDatabaseHas('role_domain_permissions', [
            'role_id' => $newRole->id,
            'domain_id' => $this->domains[0]->id,
            'can_view' => true,
            'can_edit' => false,
        ]);
    }

    public function test_revoke_domains_from_role(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson('/api/admin/role/revoke-domains', [
                'role_id' => $this->domainManagerRole->id,
                'domain_ids' => [$this->domains[0]->id],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Domains revoked from role successfully',
            ]);

        $this->assertDatabaseMissing('role_domain_permissions', [
            'role_id' => $this->domainManagerRole->id,
            'domain_id' => $this->domains[0]->id,
        ]);
    }

    public function test_get_role_domains(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/admin/role/{$this->domainManagerRole->id}/domains");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => [
                        'id' => $this->domainManagerRole->id,
                        'name' => $this->domainManagerRole->name,
                    ],
                    'total' => 2,
                ],
            ]);
    }

    public function test_assign_domains_validates_role_exists(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/admin/role/assign-domains', [
                'role_id' => 9999,
                'domain_ids' => [1],
            ]);

        $response->assertStatus(422);
    }

    public function test_assign_domains_validates_domain_exists(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/admin/role/assign-domains', [
                'role_id' => $this->domainManagerRole->id,
                'domain_ids' => [9999],
            ]);

        $response->assertStatus(422);
    }

    public function test_domain_manager_cannot_access_report_from_unassigned_domain(): void
    {
        $report = Report::factory()->create([
            'domain_id' => $this->domains[2]->id,
            'status' => 'processed',
        ]);

        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson("/api/admin/reports/{$report->id}");

        $response->assertStatus(403);
    }

    public function test_domain_manager_can_access_report_from_assigned_domain(): void
    {
        $report = Report::factory()->create([
            'domain_id' => $this->domains[0]->id,
            'status' => 'processed',
        ]);
        ReportSummary::factory()->create(['report_id' => $report->id]);

        $response = $this->actingAs($this->domainManager, 'sanctum')
            ->getJson("/api/admin/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}

