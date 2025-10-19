<?php

namespace Tests\Unit\Application\UseCases\Report\Global;

use Tests\TestCase;
use App\Application\UseCases\Report\Global\CompareDomainsUseCase;
use App\Models\Domain;
use App\Models\Report;
use App\Models\ReportSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompareDomainsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private CompareDomainsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(CompareDomainsUseCase::class);
    }

    public function test_execute_compares_two_domains(): void
    {
        $domain1 = Domain::factory()->create();
        $report1 = Report::factory()->create(['domain_id' => $domain1->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report1->id,
            'total_requests' => 1000,
            'success_rate' => 90.0,
        ]);

        $domain2 = Domain::factory()->create();
        $report2 = Report::factory()->create(['domain_id' => $domain2->id, 'status' => 'processed']);
        ReportSummary::factory()->create([
            'report_id' => $report2->id,
            'total_requests' => 1500,
            'success_rate' => 95.0,
        ]);

        $result = $this->useCase->execute([$domain1->id, $domain2->id]);

        $this->assertCount(2, $result);
        $this->assertEquals($domain1->id, $result[0]->domainId);
        $this->assertEquals($domain2->id, $result[1]->domainId);
        
        // Second domain should have comparison
        $this->assertNotNull($result[1]->vsBaseDomain);
        $this->assertEquals(50.0, $result[1]->vsBaseDomain['requests_diff']); // +50%
        $this->assertEquals(5.0, $result[1]->vsBaseDomain['success_diff']); // +5%
    }

    public function test_execute_first_domain_has_no_comparison(): void
    {
        $domain1 = Domain::factory()->create();
        $report1 = Report::factory()->create(['domain_id' => $domain1->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report1->id]);

        $result = $this->useCase->execute([$domain1->id]);

        $this->assertCount(1, $result);
        $this->assertNull($result[0]->vsBaseDomain);
    }

    public function test_execute_handles_empty_domain_ids(): void
    {
        $result = $this->useCase->execute([]);

        $this->assertEmpty($result);
    }

    public function test_execute_skips_inactive_domains(): void
    {
        $activeDomain = Domain::factory()->create(['is_active' => true]);
        $report1 = Report::factory()->create(['domain_id' => $activeDomain->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report1->id]);

        $inactiveDomain = Domain::factory()->create(['is_active' => false]);
        $report2 = Report::factory()->create(['domain_id' => $inactiveDomain->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report2->id]);

        $result = $this->useCase->execute([$activeDomain->id, $inactiveDomain->id]);

        $this->assertCount(1, $result);
        $this->assertEquals($activeDomain->id, $result[0]->domainId);
    }

    public function test_execute_skips_domains_without_reports(): void
    {
        $domainWithReports = Domain::factory()->create();
        $report = Report::factory()->create(['domain_id' => $domainWithReports->id, 'status' => 'processed']);
        ReportSummary::factory()->create(['report_id' => $report->id]);

        $domainWithoutReports = Domain::factory()->create();

        $result = $this->useCase->execute([$domainWithReports->id, $domainWithoutReports->id]);

        $this->assertCount(1, $result);
        $this->assertEquals($domainWithReports->id, $result[0]->domainId);
    }
}

