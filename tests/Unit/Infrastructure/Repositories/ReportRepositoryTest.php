<?php

namespace Tests\Unit\Infrastructure\Repositories;

use App\Domain\Entities\Report as ReportEntity;
use App\Infrastructure\Repositories\ReportRepository;
use App\Models\Report as ReportModel;
use App\Models\Domain;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReportRepository $repository;
    private Domain $testDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ReportRepository();
        
        // Create test domain
        $this->testDomain = Domain::factory()->create([
            'name' => 'Test Domain',
            'slug' => 'test-domain',
            'domain_url' => 'test.domain.com',
        ]);
    }

    public function test_create_report(): void
    {
        $reportData = [
            'source' => ['domain' => 'test.com'],
            'metadata' => ['report_date' => '2025-10-13'],
            'summary' => ['total_requests' => 1500],
            'providers' => ['top_providers' => []],
        ];

        $report = $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::parse('2025-10-13 18:54:50'),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: $reportData,
            status: 'pending'
        );

        $this->assertInstanceOf(ReportEntity::class, $report);
        $this->assertEquals($this->testDomain->id, $report->getDomainId());
        $this->assertEquals('2025-10-13', $report->getReportDate());
        $this->assertEquals('2.0.0', $report->getDataVersion());
        $this->assertEquals('pending', $report->getStatus());
        $this->assertTrue($report->isPending());
        
        // Verify it was saved to database
        $this->assertDatabaseHas('reports', [
            'domain_id' => $this->testDomain->id,
            'data_version' => '2.0.0',
            'status' => 'pending',
        ]);
        
        // Check the date separately to handle formatting differences
        $this->assertDatabaseCount('reports', 1);
        $dbReport = \App\Models\Report::first();
        $this->assertEquals('2025-10-13', $dbReport->report_date->format('Y-m-d'));
    }

    public function test_find_by_id(): void
    {
        $createdReport = $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'pending'
        );

        $foundReport = $this->repository->findById($createdReport->getId());

        $this->assertNotNull($foundReport);
        $this->assertEquals($createdReport->getId(), $foundReport->getId());
        $this->assertEquals($this->testDomain->id, $foundReport->getDomainId());
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $report = $this->repository->findById(99999);
        $this->assertNull($report);
    }

    public function test_find_by_domain(): void
    {
        // Create reports for test domain
        $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'processed'
        );

        // Create report for different domain
        $otherDomain = Domain::factory()->create();
        $this->repository->create(
            domainId: $otherDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'processed'
        );

        $domainReports = $this->repository->findByDomain($this->testDomain->id);

        $this->assertCount(1, $domainReports);
        $this->assertEquals($this->testDomain->id, $domainReports[0]->getDomainId());
    }

    public function test_find_by_status(): void
    {
        // Create reports with different statuses
        $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'pending'
        );

        $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-12',
            reportPeriodStart: Carbon::parse('2025-10-12 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-12 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'processed'
        );

        $pendingReports = $this->repository->findByStatus('pending');
        $processedReports = $this->repository->findByStatus('processed');

        $this->assertCount(1, $pendingReports);
        $this->assertCount(1, $processedReports);
        $this->assertEquals('pending', $pendingReports[0]->getStatus());
        $this->assertEquals('processed', $processedReports[0]->getStatus());
    }

    public function test_update_status(): void
    {
        $report = $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'pending'
        );

        $this->repository->updateStatus($report->getId(), 'processed');

        $updatedReport = $this->repository->findById($report->getId());
        $this->assertEquals('processed', $updatedReport->getStatus());
        $this->assertTrue($updatedReport->isProcessed());
    }

    public function test_find_all_paginated(): void
    {
        // Create multiple reports
        for ($i = 1; $i <= 25; $i++) {
            $this->repository->create(
                domainId: $this->testDomain->id,
                reportDate: "2025-10-" . str_pad($i, 2, '0', STR_PAD_LEFT),
                reportPeriodStart: Carbon::parse("2025-10-{$i} 00:00:00"),
                reportPeriodEnd: Carbon::parse("2025-10-{$i} 23:59:59"),
                generatedAt: Carbon::now(),
                totalProcessingTime: 0,
                dataVersion: '2.0.0',
                rawData: [],
                status: 'processed'
            );
        }

        $result = $this->repository->findAllPaginated(
            page: 1,
            perPage: 10
        );

        $this->assertEquals(25, $result['total']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(3, $result['last_page']); // ceil(25/10) = 3
        $this->assertCount(10, $result['data']);
    }

    public function test_find_all_paginated_with_domain_filter(): void
    {
        $otherDomain = Domain::factory()->create();
        
        // Create reports for test domain
        $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'processed'
        );
        
        // Create reports for other domain
        $this->repository->create(
            domainId: $otherDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'processed'
        );

        $result = $this->repository->findAllPaginated(
            page: 1,
            perPage: 10,
            domainId: $this->testDomain->id
        );

        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals($this->testDomain->id, $result['data'][0]->getDomainId());
    }

    public function test_delete_report(): void
    {
        $report = $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'processed'
        );

        $reportId = $report->getId();
        $this->repository->delete($reportId);

        $deletedReport = $this->repository->findById($reportId);
        $this->assertNull($deletedReport);
    }

    public function test_count_by_status(): void
    {
        // Create reports with different statuses
        $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-13',
            reportPeriodStart: Carbon::parse('2025-10-13 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-13 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'pending'
        );

        $this->repository->create(
            domainId: $this->testDomain->id,
            reportDate: '2025-10-12',
            reportPeriodStart: Carbon::parse('2025-10-12 00:00:00'),
            reportPeriodEnd: Carbon::parse('2025-10-12 23:59:59'),
            generatedAt: Carbon::now(),
            totalProcessingTime: 0,
            dataVersion: '2.0.0',
            rawData: [],
            status: 'processed'
        );

        $pendingCount = $this->repository->countByStatus('pending');
        $processedCount = $this->repository->countByStatus('processed');

        $this->assertEquals(1, $pendingCount);
        $this->assertEquals(1, $processedCount);
    }
}
