<?php

namespace Tests\Feature\Report\Global;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompareDomainsFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private array $domains;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);

        $role = Role::factory()->create([
            'name' => 'report-manager',
            'slug' => 'report-manager',
            'description' => 'Manager role for handling report operations'
        ]);

        $this->admin->roles()->attach($role, [
            'assigned_at' => now(),
            'assigned_by' => $this->admin->id
        ]);

        $this->domains = [
            Domain::factory()->create(['name' => 'domain1.com']),
            Domain::factory()->create(['name' => 'domain2.com']),
        ];
    }

    public function test_admin_can_compare_two_domains(): void
    {
        // Create reports
        $report1 = Report::factory()->create(['domain_id' => $this->domains[0]->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report1->id,
            'total_requests' => 1000,
            'success_rate' => 90.0,
        ]);

        $report2 = Report::factory()->create(['domain_id' => $this->domains[1]->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report2->id,
            'total_requests' => 2000,
            'success_rate' => 95.0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/comparison?domains=1,2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'domains' => [
                        '*' => [
                            'domain' => ['id', 'name'],
                            'metrics',
                        ],
                    ],
                    'total_compared',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(2, $response->json('data.total_compared'));
    }

    public function test_comparison_shows_percentage_differences(): void
    {
        $report1 = Report::factory()->create(['domain_id' => $this->domains[0]->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report1->id,
            'total_requests' => 1000,
            'success_rate' => 90.0,
        ]);

        $report2 = Report::factory()->create(['domain_id' => $this->domains[1]->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report2->id,
            'total_requests' => 1500,
            'success_rate' => 95.0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/comparison?domains=1,2');

        $response->assertStatus(200);

        $domains = $response->json('data.domains');
        $this->assertNotNull($domains[1]['comparison']);
        $this->assertEquals(50.0, $domains[1]['comparison']['requests_diff']); // +50%
        $this->assertEquals(5.0, $domains[1]['comparison']['success_diff']); // +5%
    }

    public function test_comparison_requires_domains_parameter(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/comparison');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'domains parameter is required. Example: ?domains=1,2,3',
            ]);
    }

    public function test_comparison_returns_404_when_no_data_found(): void
    {
        // Domains exist but no reports
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/comparison?domains=1,2');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No data found for the specified domains',
            ]);
    }

    public function test_unauthenticated_users_cannot_access_comparison(): void
    {
        $response = $this->getJson('/api/admin/reports/global/comparison?domains=1,2');

        $response->assertStatus(401);
    }
}

