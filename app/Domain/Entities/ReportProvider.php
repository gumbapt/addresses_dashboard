<?php

namespace App\Domain\Entities;

use App\Application\DTOs\Report\ReportProviderDto;

class ReportProvider
{
    public function __construct(
        public readonly int $id,
        public readonly int $reportId,          // FK → reports
        public readonly int $providerId,        // FK → providers (normalizado)
        public readonly string $originalName,   // Nome original no JSON
        public readonly string $technology,     // Mobile, Fiber, etc.
        public readonly int $totalCount,        // 46
        public readonly float $successRate,     // 0
        public readonly float $avgSpeed,        // 0
        public readonly ?int $rankPosition      // posição no top_providers
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function getProviderId(): int
    {
        return $this->providerId;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getTechnology(): string
    {
        return $this->technology;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    public function getAvgSpeed(): float
    {
        return $this->avgSpeed;
    }

    public function getRankPosition(): ?int
    {
        return $this->rankPosition;
    }

    public function isInTopRanking(): bool
    {
        return $this->rankPosition !== null;
    }

    public function toDto(): ReportProviderDto
    {
        return new ReportProviderDto(
            id: $this->id,
            report_id: $this->reportId,
            provider_id: $this->providerId,
            original_name: $this->originalName,
            technology: $this->technology,
            total_count: $this->totalCount,
            success_rate: $this->successRate,
            avg_speed: $this->avgSpeed,
            rank_position: $this->rankPosition
        );
    }
}
