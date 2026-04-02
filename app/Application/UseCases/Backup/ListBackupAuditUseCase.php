<?php

namespace App\Application\UseCases\Backup;

use App\Domain\Repositories\BackupAuditRepositoryInterface;

class ListBackupAuditUseCase
{
    public function __construct(
        private BackupAuditRepositoryInterface $backupAuditRepository
    ) {}

    /**
     * @return array{data: array, total: int, per_page: int, current_page: int, last_page: int, from: int|null, to: int|null}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->backupAuditRepository->listPaginated($page, $perPage);
    }
}
