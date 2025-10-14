<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Report\ReportDto;
use DateTime;

class Report
{
    public function __construct(
        public readonly int $id,
        public readonly int $domainId,           // FK → domains
        public readonly string $reportDate,      // 2025-10-11
        public readonly DateTime $reportPeriodStart,
        public readonly DateTime $reportPeriodEnd, 
        public readonly DateTime $generatedAt,
        public readonly int $totalProcessingTime,
        public readonly string $dataVersion,     // 2.0.0
        public readonly array $rawData,          // JSON completo original
        public readonly string $status = 'pending', // pending, processing, processed, failed
        public readonly DateTime $createdAt,
        public readonly DateTime $updatedAt
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getDomainId(): int
    {
        return $this->domainId;
    }

    public function getReportDate(): string
    {
        return $this->reportDate;
    }

    public function getReportPeriodStart(): DateTime
    {
        return $this->reportPeriodStart;
    }

    public function getReportPeriodEnd(): DateTime
    {
        return $this->reportPeriodEnd;
    }

    public function getGeneratedAt(): DateTime
    {
        return $this->generatedAt;
    }

    public function getTotalProcessingTime(): int
    {
        return $this->totalProcessingTime;
    }

    public function getDataVersion(): string
    {
        return $this->dataVersion;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function toDto(): ReportDto
    {
        return new ReportDto(
            id: $this->id,
            domain_id: $this->domainId,
            report_date: $this->reportDate,
            report_period_start: $this->reportPeriodStart->format('Y-m-d H:i:s'),
            report_period_end: $this->reportPeriodEnd->format('Y-m-d H:i:s'),
            generated_at: $this->generatedAt->format('Y-m-d H:i:s'),
            total_processing_time: $this->totalProcessingTime,
            data_version: $this->dataVersion,
            raw_data: $this->rawData,
            status: $this->status,
            created_at: $this->createdAt->format('Y-m-d H:i:s'),
            updated_at: $this->updatedAt->format('Y-m-d H:i:s')
        );
    }
}