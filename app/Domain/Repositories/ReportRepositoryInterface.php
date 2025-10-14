<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Report;
use DateTime;

interface ReportRepositoryInterface
{
    public function findById(int $id): ?Report;
    
    public function findAll(): array;
    
    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?int $domainId = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;
    
    public function findByDomain(int $domainId): array;
    
    public function findByStatus(string $status): array;
    
    public function findByDateRange(string $startDate, string $endDate): array;
    
    public function create(
        int $domainId,
        string $reportDate,
        DateTime $reportPeriodStart,
        DateTime $reportPeriodEnd,
        DateTime $generatedAt,
        int $totalProcessingTime,
        string $dataVersion,
        array $rawData,
        string $status = 'pending'
    ): Report;
    
    public function update(
        int $id,
        ?string $status = null,
        ?int $totalProcessingTime = null,
        ?array $rawData = null
    ): Report;
    
    public function updateStatus(int $id, string $status): void;
    
    public function delete(int $id): void;
    
    public function getRecentReports(int $limit = 10): array;
    
    public function getReportsByDomainAndDateRange(int $domainId, string $startDate, string $endDate): array;
    
    public function countByStatus(string $status): int;
}
