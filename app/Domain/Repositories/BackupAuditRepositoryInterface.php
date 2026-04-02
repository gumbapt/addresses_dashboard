<?php

namespace App\Domain\Repositories;

interface BackupAuditRepositoryInterface
{
    public function record(
        string $action,
        ?int $adminId = null,
        ?int $backupId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null,
    ): void;

    /**
     * @return array{data: array<int, array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int, from: int|null, to: int|null}
     */
    public function listPaginated(int $page, int $perPage): array;
}
