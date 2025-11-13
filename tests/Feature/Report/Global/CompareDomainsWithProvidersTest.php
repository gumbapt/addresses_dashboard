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

class CompareDomainsWithProvidersTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Domain $domain1;
    private Domain $domain2;
    private Provider $provider1;
    private Provider $provider2;
    private Provider $commonProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create(['is_super_admin' => true]);
        $this->domain1 = Domain::factory()->create(['name' => 'domain1.com']);
        $this->domain2 = Domain::factory()->create(['name' => 'domain2.com']);
        
        $this->provider1 = Provider::create(['name' => 'Provider 1', 'slug' => 'provider-1']);
        $this->provider2 = Provider::create(['name' => 'Provider 2', 'slug' => 'provider-2']);
        $this->commonProvider = Provider::create(['name' => 'Common Provider', 'slug' => 'common-provider']);
    }

    /** @test */
    public function compare_includes_aggregated_provider_data()
    {
        // Arrange - Domain 1 with providers
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Provider 1',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.0,
            'avg_speed' => 1200,
        ]);
        
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->commonProvider->id,
            'original_name' => 'Common Provider',
            'technology' => 'Fiber',
            'total_count' => 50,
            'success_rate' => 90.0,
            'avg_speed' => 1000,
        ]);
        
        // Domain 2 with providers
        $report2 = Report::factory()->create([
            'domain_id' => $this->domain2->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report2->id]);
        
        ReportProvider::create([
            'report_id' => $report2->id,
            'provider_id' => $this->provider2->id,
            'original_name' => 'Provider 2',
            'technology' => 'DSL',
            'total_count' => 80,
            'success_rate' => 75.0,
            'avg_speed' => 1500,
        ]);
        
        ReportProvider::create([
            'report_id' => $report2->id,
            'provider_id' => $this->commonProvider->id,
            'original_name' => 'Common Provider',
            'technology' => 'Fiber',
            'total_count' => 60,
            'success_rate' => 92.0,
            'avg_speed' => 950,
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/reports/global/comparison?domains={$this->domain1->id},{$this->domain2->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'domains',
                    'total_compared',
                    'provider_data' => [
                        'all_providers',
                        'common_providers',
                        'unique_providers_count',
                    ],
                ],
            ]);

        $providerData = $response->json('data.provider_data');
        
        // Should have all 3 providers aggregated
        $this->assertGreaterThanOrEqual(3, count($providerData['all_providers']));
        
        // Should have 1 common provider
        $this->assertCount(1, $providerData['common_providers']);
        $this->assertEquals('Common Provider', $providerData['common_providers'][0]['provider_name']);
        
        // Unique providers count
        $this->assertEquals(3, $providerData['unique_providers_count']);
    }

    /** @test */
    public function aggregated_provider_data_sums_correctly()
    {
        // Arrange
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        // Same provider in both domains
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->commonProvider->id,
            'original_name' => 'Common Provider',
            'technology' => 'Fiber',
            'total_count' => 100,
            'success_rate' => 80.0,
            'avg_speed' => 1000,
        ]);
        
        $report2 = Report::factory()->create([
            'domain_id' => $this->domain2->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report2->id]);
        
        ReportProvider::create([
            'report_id' => $report2->id,
            'provider_id' => $this->commonProvider->id,
            'original_name' => 'Common Provider',
            'technology' => 'Fiber',
            'total_count' => 150,
            'success_rate' => 90.0,
            'avg_speed' => 1200,
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/reports/global/comparison?domains={$this->domain1->id},{$this->domain2->id}");

        // Assert
        $response->assertStatus(200);
        
        $providerData = $response->json('data.provider_data');
        $commonProviderData = $providerData['common_providers'][0];
        
        // Should sum the requests (100 + 150 = 250)
        $this->assertEquals(250, $commonProviderData['total_requests']);
        
        // Should average the success rate ((80 + 90) / 2 = 85)
        $this->assertEquals(85.0, $commonProviderData['avg_success_rate']);
        
        // Should average the speed ((1000 + 1200) / 2 = 1100)
        $this->assertEquals(1100.0, $commonProviderData['avg_speed']);
    }

    /** @test */
    public function shows_providers_unique_to_each_domain()
    {
        // Arrange
        // Domain 1: Provider 1 only
        $report1 = Report::factory()->create([
            'domain_id' => $this->domain1->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report1->id]);
        
        ReportProvider::create([
            'report_id' => $report1->id,
            'provider_id' => $this->provider1->id,
            'original_name' => 'Provider 1',
            'technology' => 'Cable',
            'total_count' => 100,
            'success_rate' => 85.0,
            'avg_speed' => 1200,
        ]);
        
        // Domain 2: Provider 2 only
        $report2 = Report::factory()->create([
            'domain_id' => $this->domain2->id,
            'status' => 'processed',
        ]);
        
        ReportSummary::factory()->create(['report_id' => $report2->id]);
        
        ReportProvider::create([
            'report_id' => $report2->id,
            'provider_id' => $this->provider2->id,
            'original_name' => 'Provider 2',
            'technology' => 'DSL',
            'total_count' => 80,
            'success_rate' => 75.0,
            'avg_speed' => 1500,
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/reports/global/comparison?domains={$this->domain1->id},{$this->domain2->id}");

        // Assert
        $response->assertStatus(200);
        
        $providerData = $response->json('data.provider_data');
        
        // Should have 2 providers in all_providers
        $this->assertCount(2, $providerData['all_providers']);
        
        // Should have NO common providers
        $this->assertCount(0, $providerData['common_providers']);
        
        // Should have 2 unique providers
        $this->assertEquals(2, $providerData['unique_providers_count']);
    }
}

