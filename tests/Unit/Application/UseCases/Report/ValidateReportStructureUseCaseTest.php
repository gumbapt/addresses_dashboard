<?php

namespace Tests\Unit\Application\UseCases\Report;

use App\Application\UseCases\Report\ValidateReportStructureUseCase;
use Tests\TestCase;

class ValidateReportStructureUseCaseTest extends TestCase
{
    private ValidateReportStructureUseCase $useCase;
    private array $validReportData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->useCase = new ValidateReportStructureUseCase();
        
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
                'success_rate' => 85.15,
                'failed_requests' => 223
            ]
        ];
    }

    public function test_validates_complete_valid_report(): void
    {
        $result = $this->useCase->execute($this->validReportData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertIsArray($result['warnings']);
    }

    public function test_fails_validation_without_required_sections(): void
    {
        $reportData = [];

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Missing or invalid 'source' section", $result['errors']);
        $this->assertContains("Missing or invalid 'metadata' section", $result['errors']);
        $this->assertContains("Missing or invalid 'summary' section", $result['errors']);
    }

    public function test_fails_validation_with_invalid_section_types(): void
    {
        $reportData = [
            'source' => 'not an array',
            'metadata' => 'not an array',
            'summary' => 'not an array'
        ];

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Missing or invalid 'source' section", $result['errors']);
        $this->assertContains("Missing or invalid 'metadata' section", $result['errors']);
        $this->assertContains("Missing or invalid 'summary' section", $result['errors']);
    }

    public function test_validates_source_section_required_fields(): void
    {
        $reportData = $this->validReportData;
        unset($reportData['source']['domain']);
        unset($reportData['source']['site_id']);

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Missing or empty 'source.domain' field", $result['errors']);
        $this->assertContains("Missing or empty 'source.site_id' field", $result['errors']);
    }

    public function test_validates_source_domain_format(): void
    {
        $reportData = $this->validReportData;
        $reportData['source']['domain'] = 'invalid-domain-format';

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid domain format in 'source.domain'", $result['errors']);
    }

    public function test_validates_metadata_required_fields(): void
    {
        $reportData = $this->validReportData;
        unset($reportData['metadata']['report_date']);
        unset($reportData['metadata']['report_period']);
        unset($reportData['metadata']['generated_at']);
        unset($reportData['metadata']['data_version']);

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Missing 'metadata.report_date' field", $result['errors']);
        $this->assertContains("Missing 'metadata.report_period' field", $result['errors']);
        $this->assertContains("Missing 'metadata.generated_at' field", $result['errors']);
        $this->assertContains("Missing 'metadata.data_version' field", $result['errors']);
    }

    public function test_validates_report_date_format(): void
    {
        $reportData = $this->validReportData;
        $reportData['metadata']['report_date'] = '2025/10/13'; // Wrong format

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid 'metadata.report_date' format (expected YYYY-MM-DD)", $result['errors']);
    }

    public function test_validates_generated_at_format(): void
    {
        $reportData = $this->validReportData;
        $reportData['metadata']['generated_at'] = '2025-10-13T18:54:50Z'; // Wrong format

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid 'metadata.generated_at' format (expected YYYY-MM-DD HH:MM:SS)", $result['errors']);
    }

    public function test_validates_report_period_structure(): void
    {
        $reportData = $this->validReportData;
        $reportData['metadata']['report_period'] = 'not an array';

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid 'metadata.report_period' structure (expected array)", $result['errors']);
    }

    public function test_validates_report_period_fields(): void
    {
        $reportData = $this->validReportData;
        $reportData['metadata']['report_period'] = ['start' => '2025-10-13 00:00:00']; // Missing end

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Missing 'metadata.report_period.start' or 'metadata.report_period.end'", $result['errors']);
    }

    public function test_validates_summary_numeric_fields(): void
    {
        $reportData = $this->validReportData;
        $reportData['summary']['total_requests'] = 'not a number';
        $reportData['summary']['failed_requests'] = 'also not a number';

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid 'summary.total_requests' value (expected numeric)", $result['errors']);
        $this->assertContains("Invalid 'summary.failed_requests' value (expected numeric)", $result['errors']);
    }

    public function test_validates_success_rate_range(): void
    {
        $reportData = $this->validReportData;
        $reportData['summary']['success_rate'] = 150; // Over 100%

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid 'summary.success_rate' value (expected 0-100)", $result['errors']);
    }

    public function test_validates_negative_success_rate(): void
    {
        $reportData = $this->validReportData;
        $reportData['summary']['success_rate'] = -10;

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid 'summary.success_rate' value (expected 0-100)", $result['errors']);
    }

    public function test_generates_warnings_for_missing_optional_sections(): void
    {
        $result = $this->useCase->execute($this->validReportData);

        $this->assertTrue($result['valid']);
        $this->assertContains("Optional 'providers' section is missing - analytics will be limited", $result['warnings']);
        $this->assertContains("Optional 'geographic' section is missing - analytics will be limited", $result['warnings']);
        $this->assertContains("Optional 'performance' section is missing - analytics will be limited", $result['warnings']);
        $this->assertContains("Optional 'speed_metrics' section is missing - analytics will be limited", $result['warnings']);
    }

    public function test_generates_warning_for_missing_provider_data(): void
    {
        $reportData = $this->validReportData;
        $reportData['providers'] = []; // Empty providers section

        $result = $this->useCase->execute($reportData);

        $this->assertTrue($result['valid']);
        $this->assertContains("No provider data found - provider analytics will be unavailable", $result['warnings']);
    }

    public function test_generates_warning_for_empty_provider_data(): void
    {
        $reportData = $this->validReportData;
        $reportData['providers'] = ['top_providers' => []]; // Empty providers array

        $result = $this->useCase->execute($reportData);

        $this->assertTrue($result['valid']);
        $this->assertContains("No provider data found - provider analytics will be unavailable", $result['warnings']);
    }

    public function test_generates_warning_for_missing_geographic_states(): void
    {
        $reportData = $this->validReportData;
        $reportData['geographic'] = []; // Empty geographic section

        $result = $this->useCase->execute($reportData);

        $this->assertTrue($result['valid']);
        $this->assertContains("No geographic state data found - location analytics will be limited", $result['warnings']);
    }

    public function test_generates_warning_for_empty_geographic_states(): void
    {
        $reportData = $this->validReportData;
        $reportData['geographic'] = ['states' => []]; // Empty states array

        $result = $this->useCase->execute($reportData);

        $this->assertTrue($result['valid']);
        $this->assertContains("No geographic state data found - location analytics will be limited", $result['warnings']);
    }

    public function test_does_not_generate_warnings_when_optional_data_present(): void
    {
        $reportData = $this->validReportData;
        $reportData['providers'] = [
            'top_providers' => [
                ['name' => 'AT&T', 'total_count' => 100]
            ]
        ];
        $reportData['geographic'] = [
            'states' => [
                ['code' => 'CA', 'name' => 'California', 'request_count' => 50]
            ]
        ];
        $reportData['performance'] = ['avg_response_time' => 100];
        $reportData['speed_metrics'] = ['overall' => ['avg_speed' => 50]];

        $result = $this->useCase->execute($reportData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['warnings']);
    }

    public function test_accepts_valid_numeric_strings(): void
    {
        $reportData = $this->validReportData;
        $reportData['summary']['total_requests'] = '1500';
        $reportData['summary']['success_rate'] = '85.15';

        $result = $this->useCase->execute($reportData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_accepts_zero_values(): void
    {
        $reportData = $this->validReportData;
        $reportData['summary']['total_requests'] = 0;
        $reportData['summary']['success_rate'] = 0;
        $reportData['summary']['failed_requests'] = 0;

        $result = $this->useCase->execute($reportData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_handles_empty_source_field_values(): void
    {
        $reportData = $this->validReportData;
        $reportData['source']['domain'] = '';
        $reportData['source']['site_name'] = '';

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        $this->assertContains("Missing or empty 'source.domain' field", $result['errors']);
        $this->assertContains("Missing or empty 'source.site_name' field", $result['errors']);
    }

    public function test_skips_field_validation_for_missing_sections(): void
    {
        $reportData = []; // No sections at all

        $result = $this->useCase->execute($reportData);

        $this->assertFalse($result['valid']);
        
        // Should have section errors but not field-specific errors
        $this->assertContains("Missing or invalid 'source' section", $result['errors']);
        $this->assertNotContains("Missing or empty 'source.domain' field", $result['errors']);
    }
}
