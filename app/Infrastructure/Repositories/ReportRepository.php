<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Report as ReportEntity;
use App\Domain\Repositories\ReportRepositoryInterface;
use App\Models\Report as ReportModel;
use DateTime;

class ReportRepository implements ReportRepositoryInterface
{
    public function findById(int $id): ?ReportEntity
    {
        $report = ReportModel::find($id);
        
        if (!$report) {
            return null;
        }
        
        return $report->toEntity();
    }

    public function findAll(): array
    {
        $reports = ReportModel::with(['domain'])
            ->recentFirst()
            ->get();
        
        return $reports->map(function ($report) {
            return $report->toEntity();
        })->toArray();
    }

    public function findAllPaginated(
        int $page = 1,
        int $perPage = 15,
        ?int $domainId = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = ReportModel::with(['domain']);
        
        // Apply filters
        if ($domainId) {
            $query->byDomain($domainId);
        }
        
        if ($status) {
            $query->byStatus($status);
        }
        
        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }
        
        // Execute pagination
        $paginator = $query->recentFirst()
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'data' => $paginator->items() ? array_values(array_map(fn($report) => $report->toEntity(), $paginator->items())) : [],
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    public function findByDomain(int $domainId): array
    {
        $reports = ReportModel::byDomain($domainId)
            ->recentFirst()
            ->get();
        
        return $reports->map(function ($report) {
            return $report->toEntity();
        })->toArray();
    }

    public function findByStatus(string $status): array
    {
        $reports = ReportModel::byStatus($status)
            ->recentFirst()
            ->get();
        
        return $reports->map(function ($report) {
            return $report->toEntity();
        })->toArray();
    }

    public function findByDateRange(string $startDate, string $endDate): array
    {
        $reports = ReportModel::byDateRange($startDate, $endDate)
            ->recentFirst()
            ->get();
        
        return $reports->map(function ($report) {
            return $report->toEntity();
        })->toArray();
    }

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
    ): ReportEntity {
        $report = ReportModel::create([
            'domain_id' => $domainId,
            'report_date' => $reportDate,
            'report_period_start' => $reportPeriodStart,
            'report_period_end' => $reportPeriodEnd,
            'generated_at' => $generatedAt,
            'total_processing_time' => $totalProcessingTime,
            'data_version' => $dataVersion,
            'raw_data' => $rawData,
            'status' => $status,
        ]);
        
        return $report->toEntity();
    }

    public function update(
        int $id,
        ?string $status = null,
        ?int $totalProcessingTime = null,
        ?array $rawData = null
    ): ReportEntity {
        $report = ReportModel::findOrFail($id);
        
        $updateData = [];
        
        if ($status !== null) $updateData['status'] = $status;
        if ($totalProcessingTime !== null) $updateData['total_processing_time'] = $totalProcessingTime;
        if ($rawData !== null) $updateData['raw_data'] = $rawData;
        
        $report->update($updateData);
        
        return $report->fresh()->toEntity();
    }

    public function updateStatus(int $id, string $status): void
    {
        ReportModel::findOrFail($id)->update(['status' => $status]);
    }

    public function delete(int $id): void
    {
        ReportModel::findOrFail($id)->delete();
    }

    public function getRecentReports(int $limit = 10): array
    {
        $reports = ReportModel::with(['domain'])
            ->recentFirst()
            ->limit($limit)
            ->get();
        
        return $reports->map(function ($report) {
            return $report->toEntity();
        })->toArray();
    }

    public function getReportsByDomainAndDateRange(int $domainId, string $startDate, string $endDate): array
    {
        $reports = ReportModel::byDomain($domainId)
            ->byDateRange($startDate, $endDate)
            ->recentFirst()
            ->get();
        
        return $reports->map(function ($report) {
            return $report->toEntity();
        })->toArray();
    }

    public function countByStatus(string $status): int
    {
        return ReportModel::byStatus($status)->count();
    }
}
