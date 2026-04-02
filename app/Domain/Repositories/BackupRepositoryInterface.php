<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Backup;
use App\Domain\Entities\BackupFile;

interface BackupRepositoryInterface
{
    public function countReports(?int $domainId): int;

    public function createBackup(
        string $scope,
        ?int $domainId,
        string $triggerType,
        ?int $requestedByAdminId,
        int $reportsCountAtBackup,
        string $status,
        ?string $notes = null,
    ): Backup;

    public function updateBackup(
        int $id,
        ?string $status = null,
        ?string $errorMessage = null,
        ?\DateTimeInterface $startedAt = null,
        ?\DateTimeInterface $completedAt = null,
        ?int $sizeBytes = null,
    ): void;

    public function createBackupFile(
        int $backupId,
        string $disk,
        string $path,
        string $originalFilename,
        ?string $mimeType,
        int $sizeBytes,
    ): BackupFile;

    public function findById(int $id, bool $withFiles = false): ?Backup;

    public function listPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $scope = null,
        ?string $status = null,
        ?int $domainId = null,
    ): array;
}
