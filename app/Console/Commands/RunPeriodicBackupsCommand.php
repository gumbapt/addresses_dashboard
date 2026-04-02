<?php

namespace App\Console\Commands;

use App\Application\UseCases\Backup\CreateBackupUseCase;
use App\Domain\Repositories\BackupConfigRepositoryInterface;
use App\Models\Backup;
use Illuminate\Console\Command;

class RunPeriodicBackupsCommand extends Command
{
    protected $signature = 'backup:run-periodic';

    protected $description = 'Run a global backup when periodic backups are enabled and the next run time has passed.';

    public function handle(
        BackupConfigRepositoryInterface $configRepository,
        CreateBackupUseCase $createBackupUseCase,
    ): int {
        $config = $configRepository->getSingleton();

        if (! $config->periodicEnabled) {
            $this->info('Periodic backups are disabled.');

            return self::SUCCESS;
        }

        if ($config->nextRunAt !== null && $config->nextRunAt > now()) {
            $this->info('Next run not due yet.');

            return self::SUCCESS;
        }

        try {
            $createBackupUseCase->execute(
                scope: Backup::SCOPE_GLOBAL,
                domainId: null,
                triggerType: Backup::TRIGGER_AUTOMATIC,
                requestedByAdminId: null,
                notes: 'Periodic backup',
                requestContext: [],
            );

            $freshConfig = $configRepository->getSingleton();
            $configRepository->updateConfig(
                lastRunAt: now(),
                setLastRunAt: true,
                nextRunAt: now()->addHours($freshConfig->intervalHours),
                setNextRunAt: true,
            );

            $this->info('Periodic backup completed.');
        } catch (\Throwable $e) {
            $this->error('Periodic backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
