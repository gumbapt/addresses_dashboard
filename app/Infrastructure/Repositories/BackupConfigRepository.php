<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\BackupConfig as BackupConfigEntity;
use App\Domain\Repositories\BackupConfigRepositoryInterface;
use App\Models\BackupConfig;

class BackupConfigRepository implements BackupConfigRepositoryInterface
{
    public function getSingleton(): BackupConfigEntity
    {
        $model = BackupConfig::query()->first();

        if (! $model) {
            $model = BackupConfig::create([
                'periodic_enabled' => false,
                'interval_hours' => 24,
            ]);
        }

        return $this->toEntity($model);
    }

    public function updateConfig(
        ?bool $periodicEnabled = null,
        ?int $intervalHours = null,
        ?\DateTimeInterface $lastRunAt = null,
        bool $setLastRunAt = false,
        ?\DateTimeInterface $nextRunAt = null,
        bool $setNextRunAt = false,
        ?int $updatedByAdminId = null,
    ): BackupConfigEntity {
        $model = BackupConfig::query()->first();

        if (! $model) {
            $model = BackupConfig::create([
                'periodic_enabled' => false,
                'interval_hours' => 24,
            ]);
        }

        $updates = [];
        if ($periodicEnabled !== null) {
            $updates['periodic_enabled'] = $periodicEnabled;
        }
        if ($intervalHours !== null) {
            $updates['interval_hours'] = $intervalHours;
        }
        if ($setLastRunAt) {
            $updates['last_run_at'] = $lastRunAt;
        }
        if ($setNextRunAt) {
            $updates['next_run_at'] = $nextRunAt;
        }
        if ($updatedByAdminId !== null) {
            $updates['updated_by_admin_id'] = $updatedByAdminId;
        }

        if ($updates !== []) {
            $model->update($updates);
        }

        return $this->toEntity($model->fresh());
    }

    private function toEntity(BackupConfig $model): BackupConfigEntity
    {
        return new BackupConfigEntity(
            id: $model->id,
            periodicEnabled: (bool) $model->periodic_enabled,
            intervalHours: (int) $model->interval_hours,
            lastRunAt: $model->last_run_at,
            nextRunAt: $model->next_run_at,
            updatedByAdminId: $model->updated_by_admin_id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
