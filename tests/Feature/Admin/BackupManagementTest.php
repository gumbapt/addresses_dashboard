<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Backup;
use App\Models\BackupAuditLog;
use App\Models\Domain;
use App\Models\Report;
use Database\Seeders\AdminRolePermissionSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(AdminSeeder::class);
        $this->seed(AdminRolePermissionSeeder::class);

        $this->superAdmin = Admin::where('is_super_admin', true)->first();
    }

    /** @test */
    public function super_admin_can_create_global_backup(): void
    {
        $domain = Domain::factory()->create();
        Report::factory()->count(3)->create(['domain_id' => $domain->id]);

        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/admin/backups', [
            'scope' => 'global',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Backup::STATUS_COMPLETED)
            ->assertJsonPath('data.reports_count_at_backup', 3);

        $this->assertDatabaseHas('backups', [
            'scope' => 'global',
            'status' => Backup::STATUS_COMPLETED,
            'reports_count_at_backup' => 3,
        ]);

        $this->assertGreaterThan(0, BackupAuditLog::count());
    }

    /** @test */
    public function super_admin_can_create_domain_scoped_backup(): void
    {
        $d1 = Domain::factory()->create();
        $d2 = Domain::factory()->create();
        Report::factory()->count(2)->create(['domain_id' => $d1->id]);
        Report::factory()->count(5)->create(['domain_id' => $d2->id]);

        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/admin/backups', [
            'scope' => 'domain',
            'domain_id' => $d1->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.reports_count_at_backup', 2)
            ->assertJsonPath('data.domain_id', $d1->id);
    }

    /** @test */
    public function non_super_admin_cannot_create_backup(): void
    {
        $admin = Admin::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/admin/backups', ['scope' => 'global']);

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_list_backups(): void
    {
        Backup::create([
            'scope' => Backup::SCOPE_GLOBAL,
            'trigger_type' => Backup::TRIGGER_MANUAL,
            'requested_by_admin_id' => $this->superAdmin->id,
            'reports_count_at_backup' => 0,
            'status' => Backup::STATUS_COMPLETED,
        ]);

        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/admin/backups')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function super_admin_can_update_backup_config(): void
    {
        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->putJson('/api/admin/backup-config', [
                'periodic_enabled' => true,
                'interval_hours' => 12,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.periodic_enabled', true)
            ->assertJsonPath('data.interval_hours', 12);

        $this->assertDatabaseHas('backup_configs', [
            'periodic_enabled' => true,
            'interval_hours' => 12,
        ]);
    }

    /** @test */
    public function restore_endpoint_returns_not_implemented(): void
    {
        $backup = Backup::create([
            'scope' => Backup::SCOPE_GLOBAL,
            'trigger_type' => Backup::TRIGGER_MANUAL,
            'requested_by_admin_id' => $this->superAdmin->id,
            'reports_count_at_backup' => 0,
            'status' => Backup::STATUS_COMPLETED,
        ]);

        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson("/api/admin/backups/{$backup->id}/restore")
            ->assertStatus(501);
    }
}
