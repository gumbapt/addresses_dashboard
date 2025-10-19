<?php

namespace Tests\Feature\Report\Global;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GlobalDomainRankingTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private array $domains;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin with role
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

        // Create test domains
        $this->domains = [
            Domain::factory()->create([
                'name' => 'domain1.com',
                'slug' => 'domain1-com',
            ]),
            Domain::factory()->create([
                'name' => 'domain2.com',
                'slug' => 'domain2-com',
            ]),
            Domain::factory()->create([
                'name' => 'domain3.com',
                'slug' => 'domain3-com',
            ]),
        ];
    }

    public function test_admin_can_get_global_domain_ranking(): void
    {
        // Create reports for each domain with different volumes
        foreach ($this->domains as $index => $domain) {
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);

            ReportSummary::factory()->create([
                'report_id' => $report->id,
                'total_requests' => 1000 * ($index + 1), // 1000, 2000, 3000
                'success_rate' => 90 + $index, // 90, 91, 92
            ]);
        }

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'ranking' => [
                        '*' => [
                            'rank',
                            'domain' => ['id', 'name', 'slug'],
                            'metrics' => [
                                'total_requests',
                                'success_rate',
                                'avg_speed',
                                'score',
                                'unique_providers',
                                'unique_states',
                            ],
                            'coverage' => [
                                'total_reports',
                                'period_start',
                                'period_end',
                                'days_covered',
                            ],
                        ],
                    ],
                    'sort_by',
                    'total_domains',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(3, $response->json('data.total_domains'));
    }

    public function test_ranking_can_be_sorted_by_volume(): void
    {
        // Create reports with different volumes
        $report1 = Report::factory()->create(['domain_id' => $this->domains[0]->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report1->id, 'total_requests' => 1000]);

        $report2 = Report::factory()->create(['domain_id' => $this->domains[1]->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report2->id, 'total_requests' => 3000]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking?sort_by=volume');

        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(3000, $ranking[0]['metrics']['total_requests']);
        $this->assertEquals(2, $ranking[1]['rank']);
        $this->assertEquals(1000, $ranking[1]['metrics']['total_requests']);
    }

    public function test_ranking_can_be_sorted_by_success_rate(): void
    {
        $report1 = Report::factory()->create(['domain_id' => $this->domains[0]->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report1->id, 'success_rate' => 85.5]);

        $report2 = Report::factory()->create(['domain_id' => $this->domains[1]->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report2->id, 'success_rate' => 95.5]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking?sort_by=success');

        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertEquals(95.5, $ranking[0]['metrics']['success_rate']);
        $this->assertEquals(85.5, $ranking[1]['metrics']['success_rate']);
    }

    public function test_ranking_excludes_inactive_domains(): void
    {
        $inactiveDomain = Domain::factory()->create(['is_active' => false]);
        $report = Report::factory()->create(['domain_id' => $inactiveDomain->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking');

        $response->assertStatus(200);
        
        // Should not include the inactive domain
        $domains = collect($response->json('data.ranking'))->pluck('domain.id');
        $this->assertNotContains($inactiveDomain->id, $domains);
    }

    public function test_ranking_returns_empty_array_when_no_domains(): void
    {
        Domain::query()->delete();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ranking' => [],
                    'total_domains' => 0,
                ],
            ]);
    }

    public function test_unauthenticated_users_cannot_access_ranking(): void
    {
        $response = $this->getJson('/api/admin/reports/global/domain-ranking');

        $response->assertStatus(401);
    }

    public function test_invalid_sort_by_parameter_returns_error(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/domain-ranking?sort_by=invalid');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid sort_by parameter. Must be one of: score, volume, success, speed',
            ]);
    }
}

