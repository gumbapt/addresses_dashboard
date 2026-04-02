<?php

namespace App\Application\UseCases\Backup;

use App\Domain\Entities\BackupConfig;
use App\Domain\Repositories\BackupAuditRepositoryInterface;
use App\Domain\Repositories\BackupConfigRepositoryInterface;

class UpdateBackupConfigUseCase
{
    public function __construct(
        private BackupConfigRepositoryInterface $configRepository,
        private BackupAuditRepositoryInterface $backupAuditRepository,
    ) {}

    public function execute(
        ?bool $periodicEnabled = null,
        ?int $intervalHours = null,
        int $updatedByAdminId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): BackupConfig {
        if ($intervalHours !== null && $intervalHours < 1) {
            throw new \InvalidArgumentException('interval_hours must be at least 1.');
        }

        $current = $this->configRepository->getSingleton();
        $hours = $intervalHours ?? $current->intervalHours;

        $enabled = $periodicEnabled ?? $current->periodicEnabled;
        $nextRunAt = $enabled ? now()->addHours($hours) : null;

        $config = $this->configRepository->updateConfig(
            periodicEnabled: $periodicEnabled,
            intervalHours: $intervalHours,
            setNextRunAt: true,
            nextRunAt: $nextRunAt,
            updatedByAdminId: $updatedByAdminId,
        );

        $this->backupAuditRepository->record(
            action: 'backup.config.updated',
            adminId: $updatedByAdminId,
            backupId: null,
            ipAddress: $ip,
            userAgent: $userAgent,
            metadata: [
                'periodic_enabled' => $config->periodicEnabled,
                'interval_hours' => $config->intervalHours,
            ],
        );

        return $config;
    }
}
