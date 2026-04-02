<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\BackupConfig;

interface BackupConfigRepositoryInterface
{
    public function getSingleton(): BackupConfig;

    public function updateConfig(
        ?bool $periodicEnabled = null,
        ?int $intervalHours = null,
        ?\DateTimeInterface $lastRunAt = null,
        bool $setLastRunAt = false,
        ?\DateTimeInterface $nextRunAt = null,
        bool $setNextRunAt = false,
        ?int $updatedByAdminId = null,
    ): BackupConfig;
}
