<?php

namespace App\Application\UseCases\Backup;

use App\Domain\Entities\Backup;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Repositories\BackupRepositoryInterface;

class GetBackupByIdUseCase
{
    public function __construct(
        private BackupRepositoryInterface $backupRepository
    ) {}

    public function execute(int $id): Backup
    {
        $backup = $this->backupRepository->findById($id, true);

        if ($backup === null) {
            throw new NotFoundException('Backup not found.');
        }

        return $backup;
    }
}
