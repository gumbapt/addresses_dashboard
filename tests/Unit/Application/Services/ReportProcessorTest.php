<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\ReportProcessor;
use App\Domain\Repositories\ProviderRepositoryInterface;
use App\Domain\Repositories\StateRepositoryInterface;
use App\Domain\Repositories\CityRepositoryInterface;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ReportProcessorTest extends TestCase
{
    private ReportProcessor $processor;
    private ProviderRepositoryInterface $mockProviderRepository;
    private StateRepositoryInterface $mockStateRepository;
    private CityRepositoryInterface $mockCityRepository;
    private ZipCodeRepositoryInterface $mockZipCodeRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock repositories
        $this->mockProviderRepository = Mockery::mock(ProviderRepositoryInterface::class);
        $this->mockStateRepository = Mockery::mock(StateRepositoryInterface::class);
        $this->mockCityRepository = Mockery::mock(CityRepositoryInterface::class);
        $this->mockZipCodeRepository = Mockery::mock(ZipCodeRepositoryInterface::class);

        $this->processor = new ReportProcessor(
            $this->mockProviderRepository,
            $this->mockStateRepository,
            $this->mockCityRepository,
            $this->mockZipCodeRepository
        );
    }

    public function test_handles_empty_report_data(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Processing report sections', ['report_id' => 1]);

        Log::shouldReceive('info')
            ->once()
            ->with('Report processing completed', ['report_id' => 1]);

        // Should not crash with empty data - this is the main thing we can test in a unit test
        $this->processor->process(1, []);
        
        $this->assertTrue(true); // If we reach here without exception, test passes
    }

    public function test_handles_empty_summary_section(): void
    {
        Log::shouldReceive('info')->twice();

        // Should handle empty summary gracefully
        $this->processor->process(1, ['summary' => []]);
        
        $this->assertTrue(true);
    }

    public function test_handles_empty_providers_section(): void
    {
        Log::shouldReceive('info')->twice();

        // Should handle empty providers gracefully
        $this->processor->process(1, ['providers' => ['top_providers' => []]]);
        
        $this->assertTrue(true);
    }

    public function test_handles_empty_geographic_sections(): void
    {
        Log::shouldReceive('info')->twice();

        // Should handle empty geographic sections gracefully
        $this->processor->process(1, [
            'geographic' => [
                'states' => [],
                'top_cities' => [],
                'top_zip_codes' => []
            ]
        ]);
        
        $this->assertTrue(true);
    }

    public function test_logs_processing_start_and_completion(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Processing report sections', ['report_id' => 42]);

        Log::shouldReceive('info')
            ->once()
            ->with('Report processing completed', ['report_id' => 42]);

        $this->processor->process(42, []);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}