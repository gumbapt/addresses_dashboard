<?php

namespace Tests\Feature\Report;

use App\Models\Admin;
use App\Models\Domain;
use App\Models\Report;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Domain $testDomain;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test domain
        $this->testDomain = Domain::factory()->create([
            'name' => 'test.domain.com',
        ]);

        // Create admin with proper role
        $this->admin = Admin::factory()->create([
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);

        // Create role with report permissions
        $role = Role::factory()->create([
            'name' => 'report-manager',
            'slug' => 'report-manager',
            'description' => 'Manager role for handling report operations'
        ]);
        $this->admin->roles()->attach($role, [
            'assigned_at' => now(),
            'assigned_by' => $this->admin->id
        ]);
    }

    public function test_admin_can_list_reports(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        // Create test reports
        Report::factory()->count(5)->create([
            'domain_id' => $this->testDomain->id,
        ]);

        $response = $this->getJson('/api/admin/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'domain_id',
                        'report_date',
                        'status',
                        'data_version'
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page'
                ]
            ]);
    }

    public function test_admin_can_filter_reports_by_domain(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        $otherDomain = Domain::factory()->create();

        // Create reports for different domains
        Report::factory()->count(3)->create(['domain_id' => $this->testDomain->id]);
        Report::factory()->count(2)->create(['domain_id' => $otherDomain->id]);

        $response = $this->getJson("/api/admin/reports?domain_id={$this->testDomain->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        foreach ($data as $report) {
            $this->assertEquals($this->testDomain->id, $report['domain_id']);
        }
    }

    public function test_admin_can_filter_reports_by_status(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        // Create reports with different statuses
        Report::factory()->count(2)->create([
            'domain_id' => $this->testDomain->id,
            'status' => 'pending'
        ]);
        Report::factory()->count(3)->create([
            'domain_id' => $this->testDomain->id,
            'status' => 'processed'
        ]);

        $response = $this->getJson('/api/admin/reports?status=processed');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        foreach ($data as $report) {
            $this->assertEquals('processed', $report['status']);
        }
    }

    public function test_admin_can_filter_reports_by_date_range(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        // Create reports with different dates
        Report::factory()->create([
            'domain_id' => $this->testDomain->id,
            'report_date' => '2025-10-01'
        ]);
        Report::factory()->create([
            'domain_id' => $this->testDomain->id,
            'report_date' => '2025-10-15'
        ]);
        Report::factory()->create([
            'domain_id' => $this->testDomain->id,
            'report_date' => '2025-10-30'
        ]);

        $response = $this->getJson('/api/admin/reports?start_date=2025-10-10&end_date=2025-10-20');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('2025-10-15', $data[0]['report_date']);
    }

    public function test_admin_can_paginate_reports(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        // Create more reports than default per_page
        Report::factory()->count(25)->create([
            'domain_id' => $this->testDomain->id,
        ]);

        $response = $this->getJson('/api/admin/reports?per_page=10&page=2');

        $response->assertStatus(200);
        
        $meta = $response->json('meta');
        $this->assertEquals(25, $meta['total']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(2, $meta['current_page']);
        $this->assertEquals(3, $meta['last_page']);
    }

    public function test_admin_can_get_specific_report(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        $report = Report::factory()->create([
            'domain_id' => $this->testDomain->id,
            'raw_data' => [
                'source' => ['domain' => 'test.com'],
                'metadata' => ['report_date' => '2025-10-13'],
                'summary' => ['total_requests' => 100]
            ]
        ]);

        $response = $this->getJson("/api/admin/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'domain_id',
                    'report_date',
                    'status',
                    'raw_data'
                ]
            ]);

        $this->assertEquals($report->id, $response->json('data.id'));
    }

    public function test_admin_cannot_get_nonexistent_report(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        $response = $this->getJson('/api/admin/reports/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Report not found'
            ]);
    }

    public function test_admin_can_get_recent_reports(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        // Create reports with different creation times
        Report::factory()->count(15)->create([
            'domain_id' => $this->testDomain->id,
        ]);

        $response = $this->getJson('/api/admin/reports/recent');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'domain_id',
                        'report_date',
                        'status'
                    ]
                ]
            ]);

        // Should return max 10 recent reports
        $data = $response->json('data');
        $this->assertLessThanOrEqual(10, count($data));
    }

    public function test_unauthenticated_user_cannot_access_admin_reports(): void
    {
        $response = $this->getJson('/api/admin/reports');

        $response->assertStatus(401);
    }

    public function test_non_admin_user_cannot_access_admin_reports(): void
    {
        // Create regular user (not admin)
        $user = \App\Models\User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/reports');

        $response->assertStatus(401);
    }

    public function test_inactive_admin_cannot_access_reports(): void
    {
        $this->admin->update(['is_active' => false]);
        Sanctum::actingAs($this->admin, [], 'admin');

        $response = $this->getJson('/api/admin/reports');

        $response->assertStatus(401);
    }

    public function test_pagination_limits_per_page(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        Report::factory()->count(150)->create([
            'domain_id' => $this->testDomain->id,
        ]);

        // Test max limit
        $response = $this->getJson('/api/admin/reports?per_page=200');
        $meta = $response->json('meta');
        $this->assertEquals(100, $meta['per_page']); // Should be capped at 100

        // Test min limit
        $response = $this->getJson('/api/admin/reports?per_page=0');
        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['per_page']); // Should be minimum 1
    }

    public function test_combines_multiple_filters(): void
    {
        Sanctum::actingAs($this->admin, [], 'admin');

        $otherDomain = Domain::factory()->create();

        // Create reports with various combinations
        Report::factory()->create([
            'domain_id' => $this->testDomain->id,
            'status' => 'processed',
            'report_date' => '2025-10-15'
        ]);
        Report::factory()->create([
            'domain_id' => $this->testDomain->id,
            'status' => 'pending',
            'report_date' => '2025-10-15'
        ]);
        Report::factory()->create([
            'domain_id' => $otherDomain->id,
            'status' => 'processed',
            'report_date' => '2025-10-15'
        ]);

        $response = $this->getJson(
            "/api/admin/reports?domain_id={$this->testDomain->id}&status=processed&start_date=2025-10-10&end_date=2025-10-20"
        );

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->testDomain->id, $data[0]['domain_id']);
        $this->assertEquals('processed', $data[0]['status']);
    }
}
