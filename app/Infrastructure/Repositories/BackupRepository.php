<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Backup as BackupEntity;
use App\Domain\Entities\BackupFile as BackupFileEntity;
use App\Domain\Repositories\BackupRepositoryInterface;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Report;

class BackupRepository implements BackupRepositoryInterface
{
    public function countReports(?int $domainId): int
    {
        $query = Report::query();

        if ($domainId !== null) {
            $query->where('domain_id', $domainId);
        }

        return $query->count();
    }

    public function createBackup(
        string $scope,
        ?int $domainId,
        string $triggerType,
        ?int $requestedByAdminId,
        int $reportsCountAtBackup,
        string $status,
        ?string $notes = null,
    ): BackupEntity {
        $model = Backup::create([
            'scope' => $scope,
            'domain_id' => $domainId,
            'trigger_type' => $triggerType,
            'requested_by_admin_id' => $requestedByAdminId,
            'reports_count_at_backup' => $reportsCountAtBackup,
            'status' => $status,
            'notes' => $notes,
        ]);

        return $this->toEntity($model->fresh(), false);
    }

    public function updateBackup(
        int $id,
        ?string $status = null,
        ?string $errorMessage = null,
        ?\DateTimeInterface $startedAt = null,
        ?\DateTimeInterface $completedAt = null,
        ?int $sizeBytes = null,
    ): void {
        $data = array_filter(
            [
                'status' => $status,
                'error_message' => $errorMessage,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'size_bytes' => $sizeBytes,
            ],
            fn ($v) => $v !== null
        );

        if ($data !== []) {
            Backup::whereKey($id)->update($data);
        }
    }

    public function createBackupFile(
        int $backupId,
        string $disk,
        string $path,
        string $originalFilename,
        ?string $mimeType,
        int $sizeBytes,
    ): BackupFileEntity {
        $model = BackupFile::create([
            'backup_id' => $backupId,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
        ]);

        return $this->fileToEntity($model);
    }

    public function findById(int $id, bool $withFiles = false): ?BackupEntity
    {
        $query = Backup::query()->whereKey($id);

        if ($withFiles) {
            $query->with('files');
        }

        $model = $query->first();

        if (! $model) {
            return null;
        }

        return $this->toEntity($model, $withFiles);
    }

    public function listPaginated(
        int $page = 1,
        int $perPage = 15,
        ?string $scope = null,
        ?string $status = null,
        ?int $domainId = null,
    ): array {
        $query = Backup::query()->orderByDesc('created_at');

        if ($scope !== null) {
            $query->where('scope', $scope);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($domainId !== null) {
            $query->where('domain_id', $domainId);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(fn ($m) => $this->toEntity($m, false), $paginator->items()),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function toEntity(Backup $model, bool $includeFiles): BackupEntity
    {
        $files = [];

        if ($includeFiles) {
            foreach ($model->relationLoaded('files') ? $model->files : $model->files()->get() as $f) {
                $files[] = $this->fileToEntity($f);
            }
        }

        return new BackupEntity(
            id: $model->id,
            scope: $model->scope,
            domainId: $model->domain_id,
            startedAt: $model->started_at,
            completedAt: $model->completed_at,
            triggerType: $model->trigger_type,
            requestedByAdminId: $model->requested_by_admin_id,
            reportsCountAtBackup: (int) $model->reports_count_at_backup,
            status: $model->status,
            errorMessage: $model->error_message,
            notes: $model->notes,
            sizeBytes: $model->size_bytes !== null ? (int) $model->size_bytes : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            files: $files,
        );
    }

    private function fileToEntity(BackupFile $model): BackupFileEntity
    {
        return new BackupFileEntity(
            id: $model->id,
            backupId: $model->backup_id,
            disk: $model->disk,
            path: $model->path,
            originalFilename: $model->original_filename,
            mimeType: $model->mime_type,
            sizeBytes: (int) $model->size_bytes,
            createdAt: $model->created_at,
        );
    }
}
