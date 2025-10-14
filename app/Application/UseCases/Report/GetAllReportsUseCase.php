<?php

namespace App\Application\UseCases\Report;

use App\Domain\Repositories\ReportRepositoryInterface;

class GetAllReportsUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    public function execute(): array
    {
        return $this->reportRepository->findAll();
    }

    public function executePaginated(
        int $page = 1,
        int $perPage = 15,
        ?int $domainId = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        // Validate limits
        $perPage = min(max($perPage, 1), 100);
        $page = max($page, 1);
        
        return $this->reportRepository->findAllPaginated(
            $page,
            $perPage,
            $domainId,
            $status,
            $startDate,
            $endDate
        );
    }

    public function executeByDomain(int $domainId): array
    {
        return $this->reportRepository->findByDomain($domainId);
    }

    public function executeByStatus(string $status): array
    {
        return $this->reportRepository->findByStatus($status);
    }

    public function executeRecent(int $limit = 10): array
    {
        return $this->reportRepository->getRecentReports($limit);
    }
}
