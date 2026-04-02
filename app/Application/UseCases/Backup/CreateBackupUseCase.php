<?php

namespace App\Application\UseCases\Backup;

use App\Domain\Entities\Backup;
use App\Domain\Repositories\BackupAuditRepositoryInterface;
use App\Domain\Repositories\BackupRepositoryInterface;
use App\Infrastructure\Services\ReportBackupExporter;
use App\Models\Backup as BackupModel;
use App\Models\Domain;

class CreateBackupUseCase
{
    public function __construct(
        private BackupRepositoryInterface $backupRepository,
        private BackupAuditRepositoryInterface $backupAuditRepository,
        private ReportBackupExporter $exporter,
    ) {}

    /**
     * @param  array{ip?: string|null, user_agent?: string|null}  $requestContext
     */
    public function execute(
        string $scope,
        ?int $domainId,
        string $triggerType,
        ?int $requestedByAdminId,
        ?string $notes = null,
        array $requestContext = [],
    ): Backup {
        if ($scope === BackupModel::SCOPE_DOMAIN) {
            if ($domainId === null || ! Domain::query()->whereKey($domainId)->exists()) {
                throw new \InvalidArgumentException('domain_id is required and must exist for domain scope backups.');
            }
        } elseif ($scope !== BackupModel::SCOPE_GLOBAL) {
            throw new \InvalidArgumentException('Invalid backup scope.');
        } else {
            $domainId = null;
        }

        $reportsCount = $this->backupRepository->countReports(
            $scope === BackupModel::SCOPE_DOMAIN ? $domainId : null
        );

        $backup = $this->backupRepository->createBackup(
            scope: $scope,
            domainId: $domainId,
            triggerType: $triggerType,
            requestedByAdminId: $requestedByAdminId,
            reportsCountAtBackup: $reportsCount,
            status: BackupModel::STATUS_PENDING,
            notes: $notes,
        );

        $this->backupAuditRepository->record(
            action: 'backup.requested',
            adminId: $requestedByAdminId,
            backupId: $backup->id,
            ipAddress: $requestContext['ip'] ?? null,
            userAgent: $requestContext['user_agent'] ?? null,
            metadata: [
                'scope' => $scope,
                'domain_id' => $domainId,
                'trigger' => $triggerType,
                'reports_count' => $reportsCount,
            ],
        );

        $this->backupRepository->updateBackup(
            $backup->id,
            status: BackupModel::STATUS_RUNNING,
            startedAt: new \DateTimeImmutable,
        );

        $this->backupAuditRepository->record(
            action: 'backup.started',
            adminId: $requestedByAdminId,
            backupId: $backup->id,
            ipAddress: $requestContext['ip'] ?? null,
            userAgent: $requestContext['user_agent'] ?? null,
            metadata: ['scope' => $scope, 'domain_id' => $domainId],
        );

        try {
            [$disk, $path, $size, $mime] = $this->exporter->exportToJson(
                $backup->id,
                $domainId,
                $scope
            );

            $this->backupRepository->createBackupFile(
                $backup->id,
                $disk,
                $path,
                'reports_export.json',
                $mime,
                $size
            );

            $this->backupRepository->updateBackup(
                $backup->id,
                status: BackupModel::STATUS_COMPLETED,
                completedAt: new \DateTimeImmutable,
                sizeBytes: $size,
            );

            $this->backupAuditRepository->record(
                action: 'backup.completed',
                adminId: $requestedByAdminId,
                backupId: $backup->id,
                ipAddress: $requestContext['ip'] ?? null,
                userAgent: $requestContext['user_agent'] ?? null,
                metadata: ['path' => $path, 'size_bytes' => $size],
            );
        } catch (\Throwable $e) {
            $this->backupRepository->updateBackup(
                $backup->id,
                status: BackupModel::STATUS_FAILED,
                errorMessage: $e->getMessage(),
                completedAt: new \DateTimeImmutable,
            );

            $this->backupAuditRepository->record(
                action: 'backup.failed',
                adminId: $requestedByAdminId,
                backupId: $backup->id,
                ipAddress: $requestContext['ip'] ?? null,
                userAgent: $requestContext['user_agent'] ?? null,
                metadata: ['error' => $e->getMessage()],
            );

            throw $e;
        }

        return $this->backupRepository->findById($backup->id, true)
            ?? $backup;
    }
}
