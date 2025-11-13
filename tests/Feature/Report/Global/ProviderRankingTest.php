<?php

namespace Tests\Feature\Report\Global;

use App\Models\Admin;
use App\Models\Domain;
use App\Models\Provider;
use App\Models\Report;
use App\Models\ReportProvider;
use App\Models\ReportSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRankingTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Domain $domain1;
    private Domain $domain2;
    private Provider $provider1;
    private Provider $provider2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin
        $this->admin = Admin::factory()->create(['is_super_admin' => true]);

        // Create domains
        $this->domain1 = Domain::factory()->create(['name' => 'domain1.com']);
        $this->domain2 = Domain::factory()->create(['name' => 'domain2.com']);

        // Create providers
        $this->provider1 = Provider::create(['name' => 'Spectrum', 'slug' => 'spectrum']);
        $this->provider2 = Provider::create(['name' => 'AT&T', 'slug' => 'att']);
    }

    /** @test */
    public function can_get_provider_ranking()
    {
        // Arrange - Create reports
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'report_date' => '2025-11-10',
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.5,
            'avg_speed' => 1200,
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_entries' => 1,
                ],
            ]);

        $ranking = $response->json('data.ranking');
        $this->assertNotEmpty($ranking);
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals('domain1.com', $ranking[0]['domain_name']);
        $this->assertEquals('Spectrum', $ranking[0]['provider_name']);
        $this->assertEquals(100, $ranking[0]['total_requests']);
        
        // Verificar novos campos de porcentagem
        $this->assertArrayHasKey('domain_total_requests', $ranking[0]);
        $this->assertArrayHasKey('percentage_of_domain', $ranking[0]);
        $this->assertGreaterThan(0, $ranking[0]['percentage_of_domain']);
        $this->assertLessThanOrEqual(100, $ranking[0]['percentage_of_domain']);
    }

    /** @test */
    public function can_filter_by_specific_provider()
    {
        // Arrange
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        // Provider 1 (Spectrum)
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.0,
            'avg_speed' => 1200,
        ]);
        
        // Provider 2 (AT&T)
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider2->id,
            'original_name' => 'AT&T',
            'technology' => 'Fiber',
            'total_count' => 50,
            'success_rate' => 90.0,
            'avg_speed' => 1500,
        ]);

        // Act - Filter by Spectrum only
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/reports/global/provider-ranking?provider_id={$this->provider1->id}");

        // Assert
        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertCount(1, $ranking);
        $this->assertEquals('Spectrum', $ranking[0]['provider_name']);
        $this->assertEquals(100, $ranking[0]['total_requests']);
    }

    /** @test */
    public function can_filter_by_technology()
    {
        // Arrange
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        // Cable
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.0,
            'avg_speed' => 1200,
        ]);
        
        // Fiber
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider2->id,
            'original_name' => 'AT&T',
            'technology' => 'Fiber',
            'total_count' => 50,
            'success_rate' => 90.0,
            'avg_speed' => 1500,
        ]);

        // Act - Filter by Fiber only
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?technology=Fiber');

        // Assert
        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertCount(1, $ranking);
        $this->assertEquals('Fiber', $ranking[0]['technology']);
        $this->assertEquals('AT&T', $ranking[0]['provider_name']);
    }

    /** @test */
    public function can_sort_by_different_criteria()
    {
        // Arrange
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        // Provider with high volume, low success
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 1000,
            'success_rate' => 70.0,
            'avg_speed' => 1000,
        ]);
        
        // Provider with low volume, high success
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider2->id,
            'original_name' => 'AT&T',
            'technology' => 'Fiber',
            'total_count' => 100,
            'success_rate' => 95.0,
            'avg_speed' => 1500,
        ]);

        // Act - Sort by success_rate
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?sort_by=success_rate');

        // Assert
        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertEquals('AT&T', $ranking[0]['provider_name']); // AT&T first (higher success rate)
        $this->assertEquals('Spectrum', $ranking[1]['provider_name']);
    }

    /** @test */
    public function can_limit_results()
    {
        // Arrange - Create 5 different provider entries
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        for ($i = 1; $i <= 5; $i++) {
            $provider = Provider::create(['name' => "Provider $i", 'slug' => "provider-$i"]);
            ReportProvider::create([
                'report_id' => $report1->id,
                'provider_id' => $provider->id,
                'original_name' => "Provider $i",
                'technology' => 'Cable',
                'total_count' => 100 - $i,
                'success_rate' => 85.0,
                'avg_speed' => 1200,
            ]);
        }

        // Act - Limit to 3
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?limit=3');

        // Assert
        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertCount(3, $ranking);
        $this->assertEquals(3, $response->json('data.total_entries'));
    }

    /** @test */
    public function can_filter_by_date_range()
    {
        // Arrange
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'report_date' => '2025-11-01',
            'status' => 'processed',
        ]);
        
        $report2 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'report_date' => '2025-11-15',
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        ReportSummary::factory()->create(['report_id' => $report2->id]);
        
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.0,
            'avg_speed' => 1200,
        ]);
        
        ReportProvider::create([
            'report_id' => $report2->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 200,
            'success_rate' => 90.0,
            'avg_speed' => 1300,
        ]);

        // Act - Filter November 1-10 (should get only report1)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?date_from=2025-11-01&date_to=2025-11-10');

        // Assert
        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertEquals(100, $ranking[0]['total_requests']); // Only first report
        $this->assertEquals('2025-11-01', $ranking[0]['period_start']);
        $this->assertEquals('2025-11-01', $ranking[0]['period_end']);
    }

    /** @test */
    public function validation_error_for_invalid_sort_by()
    {
        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?sort_by=invalid');

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid sort_by parameter. Must be one of: total_requests, success_rate, avg_speed, total_reports',
            ]);
    }

    /** @test */
    public function aggregates_multiple_reports_for_same_domain_provider_combination()
    {
        // Arrange - 2 reports from same domain, same provider
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'report_date' => '2025-11-01',
            'status' => 'processed',
        ]);
        
        $report2 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'report_date' => '2025-11-02',
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        ReportSummary::factory()->create(['report_id' => $report2->id]);
        
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 80.0,
            'avg_speed' => 1200,
        ]);
        
        ReportProvider::create([
            'report_id' => $report2->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Spectrum',
            'technology' => 'Cable',
            'total_count' => 150,
            'success_rate' => 90.0,
            'avg_speed' => 1300,
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking');

        // Assert
        $response->assertStatus(200);
        
        $ranking = $response->json('data.ranking');
        $this->assertCount(1, $ranking); // Should be aggregated into 1 entry
        $this->assertEquals(250, $ranking[0]['total_requests']); // 100 + 150
        $this->assertEquals(85.0, $ranking[0]['avg_success_rate']); // (80 + 90) / 2
        $this->assertEquals(2, $ranking[0]['total_reports']);
    }
}

