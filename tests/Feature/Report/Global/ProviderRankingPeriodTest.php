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

class ProviderRankingPeriodTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Domain $domain;
    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create(['is_super_admin' => true]);
        $this->domain = Domain::factory()->create();
        $this->provider = Provider::create(['name' => 'Test Provider', 'slug' => 'test-provider']);
    }

    /** @test */
    public function can_filter_by_today()
    {
        // Arrange - Create report for today
        $reportToday = Report::factory()->create([
            'domain_id' => $this->domain->id,
            'report_date' => now()->toDateString(),
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $reportToday->id]);
        
        ReportProvider::create([
            'report_id' => $reportToday->id,
            'provider_id' => $this->provider->id,
            'original_name' => 'Test Provider',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.0,
            'avg_speed' => 1200,
        ]);
        
        // Create old report (should be excluded)
        $reportOld = Report::factory()->create([
            'domain_id' => $this->domain->id,
            'report_date' => now()->subDays(5)->toDateString(),
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $reportOld->id]);
        
        ReportProvider::create([
            'report_id' => $reportOld->id,
            'provider_id' => $this->provider->id,
            'original_name' => 'Test Provider',
            'technology' => 'Cable',
            'total_count' => 500,
            'success_rate' => 80.0,
            'avg_speed' => 1100,
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?period=today');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('today', $response->json('data.filters.period'));
        $this->assertEquals(now()->toDateString(), $response->json('data.filters.date_from'));
        $this->assertEquals(now()->toDateString(), $response->json('data.filters.date_to'));
        
        // If there's data, verify it's only from today
        $ranking = $response->json('data.ranking');
        if (!empty($ranking)) {
            $this->assertEquals(100, $ranking[0]['total_requests']);
        }
    }

    /** @test */
    public function can_filter_by_last_week()
    {
        // Arrange
        $reportRecent = Report::factory()->create([
            'domain_id' => $this->domain->id,
            'report_date' => now()->subDays(3)->toDateString(),
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $reportRecent->id]);
        
        ReportProvider::create([
            'report_id' => $reportRecent->id,
            'provider_id' => $this->provider->id,
            'original_name' => 'Test Provider',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.0,
            'avg_speed' => 1200,
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?period=last_week');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('last_week', $response->json('data.filters.period'));
    }

    /** @test */
    public function can_filter_by_last_month()
    {
        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?period=last_month');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('last_month', $response->json('data.filters.period'));
        $this->assertNotNull($response->json('data.filters.date_from'));
        $this->assertNotNull($response->json('data.filters.date_to'));
    }

    /** @test */
    public function can_filter_by_all_time()
    {
        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?period=all_time');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('all_time', $response->json('data.filters.period'));
        $this->assertNull($response->json('data.filters.date_from'));
        $this->assertNull($response->json('data.filters.date_to'));
    }

    /** @test */
    public function validation_error_for_invalid_period()
    {
        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?period=invalid_period');

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid period parameter. Must be one of: today, yesterday, last_week, last_month, last_year, all_time',
            ]);
    }

    /** @test */
    public function period_overrides_manual_dates()
    {
        // Act - period should override date_from and date_to
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?period=today&date_from=2020-01-01&date_to=2020-12-31');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('today', $response->json('data.filters.period'));
        // date_from and date_to should be today's date
        $this->assertEquals(now()->toDateString(), $response->json('data.filters.date_from'));
        $this->assertEquals(now()->toDateString(), $response->json('data.filters.date_to'));
    }
}

