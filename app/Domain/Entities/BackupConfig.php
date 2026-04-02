<?php

namespace App\Domain\Entities;

use DateTimeInterface;

class BackupConfig
{
    public function __construct(
        public readonly int $id,
        public readonly bool $periodicEnabled,
        public readonly int $intervalHours,
        public readonly ?DateTimeInterface $lastRunAt,
        public readonly ?DateTimeInterface $nextRunAt,
        public readonly ?int $updatedByAdminId,
        public readonly ?DateTimeInterface $createdAt,
        public readonly ?DateTimeInterface $updatedAt,
    ) {}
}
