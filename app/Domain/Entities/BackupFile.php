<?php

namespace App\Domain\Entities;

use DateTimeInterface;

class BackupFile
{
    public function __construct(
        public readonly int $id,
        public readonly int $backupId,
        public readonly string $disk,
        public readonly string $path,
        public readonly string $originalFilename,
        public readonly ?string $mimeType,
        public readonly int $sizeBytes,
        public readonly ?DateTimeInterface $createdAt,
    ) {}
}
