<?php

namespace App\Application\UseCases\Report;

use App\Domain\Entities\Report;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Repositories\ReportRepositoryInterface;

class GetReportByIdUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    public function execute(int $id): Report
    {
        $report = $this->reportRepository->findById($id);
        
        if (!$report) {
            throw new NotFoundException("Report with ID {$id} not found");
        }
        
        return $report;
    }
}
