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

class ProviderRankingPaginationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create(['is_super_admin' => true]);
    }

    /** @test */
    public function can_paginate_provider_ranking()
    {
        // Arrange - Create 25 different domain+provider combinations
        for ($i = 1; $i <= 25; $i++) {
            $domain = Domain::factory()->create(['name' => "domain{$i}.com"]);
            $provider = Provider::create(['name' => "Provider {$i}", 'slug' => "provider-{$i}"]);
            
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            
            ReportSummary::factory()->create(['report_id' => $report->id]);
            
            ReportProvider::create([
                'report_id' => $report->id,
                'provider_id' => $provider->id,
                'original_name' => "Provider {$i}",
                'technology' => 'Cable',
                'total_count' => 100 - $i,
                'success_rate' => 85.0,
                'avg_speed' => 1200,
            ]);
        }

        // Act - Get page 1 (15 per page)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?page=1&per_page=15');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);

        $this->assertCount(15, $response->json('data'));
        $this->assertEquals(25, $response->json('pagination.total'));
        $this->assertEquals(1, $response->json('pagination.current_page'));
        $this->assertEquals(2, $response->json('pagination.last_page'));
        $this->assertEquals(15, $response->json('pagination.per_page'));
    }

    /** @test */
    public function can_get_second_page()
    {
        // Arrange
        for ($i = 1; $i <= 25; $i++) {
            $domain = Domain::factory()->create(['name' => "domain{$i}.com"]);
            $provider = Provider::create(['name' => "Provider {$i}", 'slug' => "provider-{$i}"]);
            
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            
            ReportSummary::factory()->create(['report_id' => $report->id]);
            
            ReportProvider::create([
                'report_id' => $report->id,
                'provider_id' => $provider->id,
                'original_name' => "Provider {$i}",
                'technology' => 'Cable',
                'total_count' => 100 - $i,
                'success_rate' => 85.0,
                'avg_speed' => 1200,
            ]);
        }

        // Act - Get page 2
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?page=2&per_page=15');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data')); // 25 total - 15 on page 1 = 10 on page 2
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(16, $response->json('pagination.from'));
        $this->assertEquals(25, $response->json('pagination.to'));
    }

    /** @test */
    public function can_change_per_page()
    {
        // Arrange
        for ($i = 1; $i <= 30; $i++) {
            $domain = Domain::factory()->create(['name' => "domain{$i}.com"]);
            $provider = Provider::create(['name' => "Provider {$i}", 'slug' => "provider-{$i}"]);
            
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            
            ReportSummary::factory()->create(['report_id' => $report->id]);
            
            ReportProvider::create([
                'report_id' => $report->id,
                'provider_id' => $provider->id,
                'original_name' => "Provider {$i}",
                'technology' => 'Cable',
                'total_count' => 100,
                'success_rate' => 85.0,
                'avg_speed' => 1200,
            ]);
        }

        // Act - 20 per page
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?page=1&per_page=20');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data'));
        $this->assertEquals(20, $response->json('pagination.per_page'));
        $this->assertEquals(2, $response->json('pagination.last_page')); // 30 total / 20 per page = 2 pages
    }

    /** @test */
    public function backward_compatible_with_limit()
    {
        // Arrange
        for ($i = 1; $i <= 20; $i++) {
            $domain = Domain::factory()->create(['name' => "domain{$i}.com"]);
            $provider = Provider::create(['name' => "Provider {$i}", 'slug' => "provider-{$i}"]);
            
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            
            ReportSummary::factory()->create(['report_id' => $report->id]);
            
            ReportProvider::create([
                'report_id' => $report->id,
                'provider_id' => $provider->id,
                'original_name' => "Provider {$i}",
                'technology' => 'Cable',
                'total_count' => 100,
                'success_rate' => 85.0,
                'avg_speed' => 1200,
            ]);
        }

        // Act - Use old limit parameter (no pagination)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/global/provider-ranking?limit=10');

        // Assert
        $response->assertStatus(200);
        $this->assertArrayHasKey('ranking', $response->json('data'));
        $this->assertArrayNotHasKey('pagination', $response->json());
        $this->assertEquals(10, $response->json('data.total_entries'));
    }

    /** @test */
    public function pagination_works_with_filters()
    {
        // Arrange
        $provider = Provider::create(['name' => 'Target Provider', 'slug' => 'target']);
        
        for ($i = 1; $i <= 30; $i++) {
            $domain = Domain::factory()->create(['name' => "domain{$i}.com"]);
            
            $report = Report::factory()->create([
                'domain_id' => $domain->id,
                'status' => 'processed',
            ]);
            
            ReportSummary::factory()->create(['report_id' => $report->id]);
            
            ReportProvider::create([
                'report_id' => $report->id,
                'provider_id' => $provider->id,
                'original_name' => 'Target Provider',
                'technology' => 'Fiber',
                'total_count' => 100,
                'success_rate' => 85.0,
                'avg_speed' => 1200,
            ]);
        }

        // Act - Filter by provider + pagination
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/reports/global/provider-ranking?provider_id={$provider->id}&page=2&per_page=10");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(30, $response->json('pagination.total'));
    }
}

