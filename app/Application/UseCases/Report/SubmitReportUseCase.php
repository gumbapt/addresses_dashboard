<?php

namespace App\Application\UseCases\Report;

use App\Domain\Entities\Report;
use App\Domain\Exceptions\InvalidArgumentException;

class SubmitReportUseCase
{
    public function __construct(
        private ValidateReportStructureUseCase $validateStructureUseCase,
        private CreateReportUseCase $createReportUseCase
    ) {}

    /**
     * Validate and submit a new report
     */
    public function execute(int $domainId, array $reportData): Report
    {
        // Validate report structure
        $validation = $this->validateStructureUseCase->execute($reportData);
        
        if (!$validation['valid']) {
            throw new InvalidArgumentException(
                'Invalid report structure: ' . implode(', ', $validation['errors'])
            );
        }

        // Create the report
        return $this->createReportUseCase->execute($domainId, $reportData);
    }

    /**
     * Validate report without creating it
     */
    public function validateOnly(array $reportData): array
    {
        return $this->validateStructureUseCase->execute($reportData);
    }
}
