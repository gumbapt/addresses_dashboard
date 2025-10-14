<?php

namespace Tests\Unit\Application\UseCases\Report;

use App\Application\UseCases\Report\SubmitReportUseCase;
use App\Application\UseCases\Report\ValidateReportStructureUseCase;
use App\Application\UseCases\Report\CreateReportUseCase;
use App\Domain\Entities\Report;
use App\Domain\Exceptions\InvalidArgumentException;
use Tests\TestCase;
use Mockery;

class SubmitReportUseCaseTest extends TestCase
{
    private SubmitReportUseCase $useCase;
    private ValidateReportStructureUseCase $mockValidateUseCase;
    private CreateReportUseCase $mockCreateUseCase;
    private array $validReportData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockValidateUseCase = Mockery::mock(ValidateReportStructureUseCase::class);
        $this->mockCreateUseCase = Mockery::mock(CreateReportUseCase::class);

        $this->useCase = new SubmitReportUseCase(
            $this->mockValidateUseCase,
            $this->mockCreateUseCase
        );

        $this->validReportData = [
            'source' => [
                'domain' => 'test.domain.com',
                'site_id' => 'wp-test-001',
                'site_name' => 'Test Site'
            ],
            'metadata' => [
                'report_date' => '2025-10-13',
                'report_period' => [
                    'start' => '2025-10-13 00:00:00',
                    'end' => '2025-10-13 23:59:59'
                ],
                'generated_at' => '2025-10-13 18:54:50',
                'data_version' => '2.0.0'
            ],
            'summary' => [
                'total_requests' => 1500,
                'success_rate' => 85.15
            ]
        ];
    }

    public function test_successfully_submits_valid_report(): void
    {
        $domainId = 1;
        $mockReport = Mockery::mock(Report::class);

        // Mock validation success
        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($this->validReportData)
            ->andReturn([
                'valid' => true,
                'errors' => [],
                'warnings' => []
            ]);

        // Mock report creation
        $this->mockCreateUseCase->shouldReceive('execute')
            ->once()
            ->with($domainId, $this->validReportData)
            ->andReturn($mockReport);

        $result = $this->useCase->execute($domainId, $this->validReportData);

        $this->assertSame($mockReport, $result);
    }

    public function test_throws_exception_for_invalid_report_structure(): void
    {
        $domainId = 1;
        $invalidReportData = ['invalid' => 'data'];

        // Mock validation failure
        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($invalidReportData)
            ->andReturn([
                'valid' => false,
                'errors' => [
                    "Missing or invalid 'source' section",
                    "Missing or invalid 'metadata' section"
                ],
                'warnings' => []
            ]);

        // Should not attempt to create report
        $this->mockCreateUseCase->shouldReceive('execute')->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid report structure: Missing or invalid 'source' section, Missing or invalid 'metadata' section");

        $this->useCase->execute($domainId, $invalidReportData);
    }

    public function test_throws_exception_with_single_validation_error(): void
    {
        $domainId = 1;
        $invalidReportData = ['partial' => 'data'];

        // Mock validation failure with single error
        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($invalidReportData)
            ->andReturn([
                'valid' => false,
                'errors' => ["Missing 'metadata.report_date' field"],
                'warnings' => []
            ]);

        $this->mockCreateUseCase->shouldReceive('execute')->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid report structure: Missing 'metadata.report_date' field");

        $this->useCase->execute($domainId, $invalidReportData);
    }

    public function test_passes_domain_id_to_create_use_case(): void
    {
        $domainId = 42;
        $mockReport = Mockery::mock(Report::class);

        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->andReturn(['valid' => true, 'errors' => [], 'warnings' => []]);

        $this->mockCreateUseCase->shouldReceive('execute')
            ->once()
            ->with($domainId, $this->validReportData)
            ->andReturn($mockReport);

        $result = $this->useCase->execute($domainId, $this->validReportData);

        $this->assertSame($mockReport, $result);
    }

    public function test_validate_only_returns_validation_result(): void
    {
        $expectedValidation = [
            'valid' => true,
            'errors' => [],
            'warnings' => ['Some warning message']
        ];

        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($this->validReportData)
            ->andReturn($expectedValidation);

        // Should not attempt to create report
        $this->mockCreateUseCase->shouldReceive('execute')->never();

        $result = $this->useCase->validateOnly($this->validReportData);

        $this->assertEquals($expectedValidation, $result);
    }

    public function test_validate_only_returns_validation_errors(): void
    {
        $invalidData = ['invalid' => 'structure'];
        $expectedValidation = [
            'valid' => false,
            'errors' => ['Multiple validation errors'],
            'warnings' => []
        ];

        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($invalidData)
            ->andReturn($expectedValidation);

        $this->mockCreateUseCase->shouldReceive('execute')->never();

        $result = $this->useCase->validateOnly($invalidData);

        $this->assertEquals($expectedValidation, $result);
    }

    public function test_handles_empty_report_data(): void
    {
        $domainId = 1;
        $emptyData = [];

        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($emptyData)
            ->andReturn([
                'valid' => false,
                'errors' => ['Empty report data'],
                'warnings' => []
            ]);

        $this->mockCreateUseCase->shouldReceive('execute')->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid report structure: Empty report data');

        $this->useCase->execute($domainId, $emptyData);
    }

    public function test_handles_validation_with_warnings_but_valid(): void
    {
        $domainId = 1;
        $mockReport = Mockery::mock(Report::class);

        // Mock validation success with warnings
        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($this->validReportData)
            ->andReturn([
                'valid' => true,
                'errors' => [],
                'warnings' => [
                    "Optional 'providers' section is missing",
                    "Optional 'geographic' section is missing"
                ]
            ]);

        $this->mockCreateUseCase->shouldReceive('execute')
            ->once()
            ->with($domainId, $this->validReportData)
            ->andReturn($mockReport);

        // Should still succeed despite warnings
        $result = $this->useCase->execute($domainId, $this->validReportData);

        $this->assertSame($mockReport, $result);
    }

    public function test_preserves_original_report_data(): void
    {
        $domainId = 1;
        $originalData = $this->validReportData;
        $mockReport = Mockery::mock(Report::class);

        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($originalData)
            ->andReturn(['valid' => true, 'errors' => [], 'warnings' => []]);

        $this->mockCreateUseCase->shouldReceive('execute')
            ->once()
            ->with($domainId, $originalData)
            ->andReturn($mockReport);

        $this->useCase->execute($domainId, $originalData);
        
        $this->assertTrue(true);

        // Verify original data wasn't modified (by checking it's still the same)
        $this->assertEquals($this->validReportData, $originalData);
    }

    public function test_handles_multiple_validation_errors(): void
    {
        $domainId = 1;
        $invalidData = ['completely' => 'wrong'];

        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($invalidData)
            ->andReturn([
                'valid' => false,
                'errors' => [
                    "Missing or invalid 'source' section",
                    "Missing or invalid 'metadata' section",
                    "Missing or invalid 'summary' section",
                    "Invalid domain format"
                ],
                'warnings' => []
            ]);

        $this->mockCreateUseCase->shouldReceive('execute')->never();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid report structure: Missing or invalid 'source' section, Missing or invalid 'metadata' section, Missing or invalid 'summary' section, Invalid domain format");

        $this->useCase->execute($domainId, $invalidData);
    }

    public function test_validation_called_with_exact_data(): void
    {
        $domainId = 1;
        $specificData = [
            'source' => ['domain' => 'specific.test.com'],
            'metadata' => ['report_date' => '2025-01-01'],
            'summary' => ['total_requests' => 999]
        ];
        $mockReport = Mockery::mock(Report::class);

        $this->mockValidateUseCase->shouldReceive('execute')
            ->once()
            ->with($specificData) // Exact match verification
            ->andReturn(['valid' => true, 'errors' => [], 'warnings' => []]);

        $this->mockCreateUseCase->shouldReceive('execute')
            ->once()
            ->with($domainId, $specificData)
            ->andReturn($mockReport);

        $this->useCase->execute($domainId, $specificData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
