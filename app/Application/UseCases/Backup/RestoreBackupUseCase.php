<?php

namespace App\Application\UseCases\Backup;

use App\Domain\Exceptions\NotFoundException;
use App\Domain\Repositories\BackupRepositoryInterface;

/**
 * Restore is not implemented yet. Future work: replay JSON/SQL into reports (and related) tables
 * inside a transaction with domain or global scope rules.
 */
class RestoreBackupUseCase
{
    public function __construct(
        private BackupRepositoryInterface $backupRepository
    ) {}

    /**
     * @throws NotFoundException
     * @throws \RuntimeException
     */
    public function execute(int $backupId): never
    {
        $backup = $this->backupRepository->findById($backupId, false);

        if ($backup === null) {
            throw new NotFoundException('Backup not found.');
        }

        throw new \RuntimeException(
            'Restore is not implemented. Use the exported JSON with a controlled import process once defined.'
        );
    }
}
