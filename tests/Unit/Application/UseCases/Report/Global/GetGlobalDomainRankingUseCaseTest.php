<?php

namespace Tests\Unit\Application\UseCases\Report\Global;

use Tests\TestCase;
use App\Application\UseCases\Report\Global\GetGlobalDomainRankingUseCase;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GetGlobalDomainRankingUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private GetGlobalDomainRankingUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(GetGlobalDomainRankingUseCase::class);
    }

    public function test_execute_returns_domains_sorted_by_score(): void
    {
        // Create 2 domains with different metrics
        $domain1 = Domain::factory()->create();
        $report1 = Report::factory()->create(['domain_id' => $domain1->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report1->id,
            'total_requests' => 1000,
            'success_rate' => 90,
        ]);

        $domain2 = Domain::factory()->create();
        $report2 = Report::factory()->create(['domain_id' => $domain2->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report2->id,
            'total_requests' => 2000,
            'success_rate' => 95,
        ]);

        $result = $this->useCase->execute('score');

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->rank);
        $this->assertEquals(2, $result[1]->rank);
        // Domain with higher score should be first
        $this->assertGreaterThan($result[1]->score, $result[0]->score);
    }

    public function test_execute_can_sort_by_volume(): void
    {
        $domain1 = Domain::factory()->create();
        $report1 = Report::factory()->create(['domain_id' => $domain1->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report1->id, 'total_requests' => 500]);

        $domain2 = Domain::factory()->create();
        $report2 = Report::factory()->create(['domain_id' => $domain2->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report2->id, 'total_requests' => 1500]);

        $result = $this->useCase->execute('volume');

        $this->assertEquals(1500, $result[0]->totalRequests);
        $this->assertEquals(500, $result[1]->totalRequests);
    }

    public function test_execute_can_sort_by_success_rate(): void
    {
        $domain1 = Domain::factory()->create();
        $report1 = Report::factory()->create(['domain_id' => $domain1->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report1->id, 'success_rate' => 85.5]);

        $domain2 = Domain::factory()->create();
        $report2 = Report::factory()->create(['domain_id' => $domain2->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report2->id, 'success_rate' => 95.5]);

        $result = $this->useCase->execute('success');

        $this->assertEquals(95.5, $result[0]->successRate);
        $this->assertEquals(85.5, $result[1]->successRate);
    }

    public function test_execute_handles_no_domains(): void
    {
        $result = $this->useCase->execute('score');

        $this->assertEmpty($result);
    }

    public function test_execute_excludes_inactive_domains(): void
    {
        $activeDomain = Domain::factory()->create(['is_active' => true]);
        $report1 = Report::factory()->create(['domain_id' => $activeDomain->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report1->id]);

        $inactiveDomain = Domain::factory()->create(['is_active' => false]);
        $report2 = Report::factory()->create(['domain_id' => $inactiveDomain->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report2->id]);

        $result = $this->useCase->execute('score');

        $this->assertCount(1, $result);
        $this->assertEquals($activeDomain->id, $result[0]->domainId);
    }

    public function test_execute_filters_by_min_reports(): void
    {
        $domain1 = Domain::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $report = Report::factory()->create(['domain_id' => $domain1->id, 'status' => 'processed']);
            ReportSummary::factory()->create(['report_id' => $report->id]);
        }

        $domain2 = Domain::factory()->create();
        for ($i = 0; $i < 2; $i++) {
            $report = Report::factory()->create(['domain_id' => $domain2->id, 'status' => 'processed']);
            ReportSummary::factory()->create(['report_id' => $report->id]);
        }

        // Require at least 3 reports
        $result = $this->useCase->execute('score', null, null, 3);

        $this->assertCount(1, $result);
        $this->assertEquals($domain1->id, $result[0]->domainId);
    }
}

