<?php

namespace App\Application\UseCases\Backup;

use App\Domain\Entities\BackupConfig;
use App\Domain\Repositories\BackupConfigRepositoryInterface;

class GetBackupConfigUseCase
{
    public function __construct(
        private BackupConfigRepositoryInterface $configRepository
    ) {}

    public function execute(): BackupConfig
    {
        return $this->configRepository->getSingleton();
    }
}
