<?php

namespace App\Application\UseCases\Backup;

use App\Domain\Repositories\BackupRepositoryInterface;

class ListBackupsUseCase
{
    public function __construct(
        private BackupRepositoryInterface $backupRepository
    ) {}

    /**
     * @return array{data: array, total: int, per_page: int, current_page: int, last_page: int, from: int|null, to: int|null}
     */
    public function execute(
        int $page = 1,
        int $perPage = 15,
        ?string $scope = null,
        ?string $status = null,
        ?int $domainId = null,
    ): array {
        return $this->backupRepository->listPaginated($page, $perPage, $scope, $status, $domainId);
    }
}
