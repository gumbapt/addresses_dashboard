<?php

namespace App\Domain\Entities;

use DateTimeInterface;

class Backup
{
    /**
     * @param  list<BackupFile>  $files
     */
    public function __construct(
        public readonly int $id,
        public readonly string $scope,
        public readonly ?int $domainId,
        public readonly ?DateTimeInterface $startedAt,
        public readonly ?DateTimeInterface $completedAt,
        public readonly string $triggerType,
        public readonly ?int $requestedByAdminId,
        public readonly int $reportsCountAtBackup,
        public readonly string $status,
        public readonly ?string $errorMessage,
        public readonly ?string $notes,
        public readonly ?int $sizeBytes,
        public readonly ?DateTimeInterface $createdAt,
        public readonly ?DateTimeInterface $updatedAt,
        public readonly array $files = [],
    ) {}
}
