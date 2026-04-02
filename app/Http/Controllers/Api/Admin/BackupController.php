<?php

namespace App\Http\Controllers\Api\Admin;

use App\Application\Services\AdminFactory;
use App\Application\UseCases\Admin\Authorization\AuthorizeActionUseCase;
use App\Application\UseCases\Backup\CreateBackupUseCase;
use App\Application\UseCases\Backup\GetBackupByIdUseCase;
use App\Application\UseCases\Backup\GetBackupConfigUseCase;
use App\Application\UseCases\Backup\ListBackupAuditUseCase;
use App\Application\UseCases\Backup\ListBackupsUseCase;
use App\Application\UseCases\Backup\RestoreBackupUseCase;
use App\Application\UseCases\Backup\UpdateBackupConfigUseCase;
use App\Domain\Entities\Backup;
use App\Domain\Exceptions\AuthorizationException;
use App\Domain\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Backup as BackupModel;
use App\Models\BackupFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        private ListBackupsUseCase $listBackupsUseCase,
        private CreateBackupUseCase $createBackupUseCase,
        private GetBackupByIdUseCase $getBackupByIdUseCase,
        private GetBackupConfigUseCase $getBackupConfigUseCase,
        private UpdateBackupConfigUseCase $updateBackupConfigUseCase,
        private ListBackupAuditUseCase $listBackupAuditUseCase,
        private RestoreBackupUseCase $restoreBackupUseCase,
        private AuthorizeActionUseCase $authorizeActionUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'backup-read');

            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
            $scope = $request->get('scope');
            $status = $request->get('status');
            $domainId = $request->get('domain_id');

            $result = $this->listBackupsUseCase->execute(
                $page,
                $perPage,
                is_string($scope) ? $scope : null,
                is_string($status) ? $status : null,
                $domainId !== null && $domainId !== '' ? (int) $domainId : null,
            );

            return response()->json([
                'success' => true,
                'data' => array_map(fn (Backup $b) => $this->backupToArray($b, false), $result['data']),
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'scope' => 'required|string|in:global,domain',
            'domain_id' => 'nullable|integer|exists:domains,id',
            'notes' => 'nullable|string|max:65535',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'backup-create');

            $data = $validator->validated();
            $scope = $data['scope'];
            $domainId = isset($data['domain_id']) ? (int) $data['domain_id'] : null;

            $backup = $this->createBackupUseCase->execute(
                scope: $scope,
                domainId: $domainId,
                triggerType: BackupModel::TRIGGER_MANUAL,
                requestedByAdminId: $adminModel->id,
                notes: $data['notes'] ?? null,
                requestContext: [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup completed successfully.',
                'data' => $this->backupToArray($backup, true),
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $admin = AdminFactory::createFromModel($request->user());
            $this->authorizeActionUseCase->execute($admin, 'backup-read');

            $backup = $this->getBackupByIdUseCase->execute($id);

            return response()->json([
                'success' => true,
                'data' => $this->backupToArray($backup, true),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (NotFoundException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function download(Request $request, int $backupId, int $fileId): StreamedResponse|JsonResponse
    {
        try {
            $admin = AdminFactory::createFromModel($request->user());
            $this->authorizeActionUseCase->execute($admin, 'backup-read');

            $file = BackupFile::query()
                ->whereKey($fileId)
                ->where('backup_id', $backupId)
                ->first();

            if (! $file) {
                return response()->json(['success' => false, 'message' => 'File not found.'], 404);
            }

            return Storage::disk($file->disk)->download($file->path, $file->original_filename);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        try {
            $admin = AdminFactory::createFromModel($request->user());
            $this->authorizeActionUseCase->execute($admin, 'backup-restore');

            $this->restoreBackupUseCase->execute($id);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (NotFoundException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 501);
        }
    }

    public function getConfig(Request $request): JsonResponse
    {
        try {
            $admin = AdminFactory::createFromModel($request->user());
            $this->authorizeActionUseCase->execute($admin, 'backup-config-manage');

            $config = $this->getBackupConfigUseCase->execute();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $config->id,
                    'periodic_enabled' => $config->periodicEnabled,
                    'interval_hours' => $config->intervalHours,
                    'last_run_at' => $config->lastRunAt?->format(\DateTimeInterface::ATOM),
                    'next_run_at' => $config->nextRunAt?->format(\DateTimeInterface::ATOM),
                    'updated_by_admin_id' => $config->updatedByAdminId,
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    public function putConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'periodic_enabled' => 'sometimes|boolean',
            'interval_hours' => 'sometimes|integer|min:1|max:8760',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $adminModel = $request->user();
            $admin = AdminFactory::createFromModel($adminModel);
            $this->authorizeActionUseCase->execute($admin, 'backup-config-manage');

            $data = $validator->validated();

            $config = $this->updateBackupConfigUseCase->execute(
                periodicEnabled: array_key_exists('periodic_enabled', $data) ? (bool) $data['periodic_enabled'] : null,
                intervalHours: isset($data['interval_hours']) ? (int) $data['interval_hours'] : null,
                updatedByAdminId: $adminModel->id,
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup configuration updated.',
                'data' => [
                    'id' => $config->id,
                    'periodic_enabled' => $config->periodicEnabled,
                    'interval_hours' => $config->intervalHours,
                    'last_run_at' => $config->lastRunAt?->format(\DateTimeInterface::ATOM),
                    'next_run_at' => $config->nextRunAt?->format(\DateTimeInterface::ATOM),
                    'updated_by_admin_id' => $config->updatedByAdminId,
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function audit(Request $request): JsonResponse
    {
        try {
            $admin = AdminFactory::createFromModel($request->user());
            $this->authorizeActionUseCase->execute($admin, 'backup-audit-read');

            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

            $result = $this->listBackupAuditUseCase->execute($page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    private function backupToArray(Backup $b, bool $includeFiles): array
    {
        $row = [
            'id' => $b->id,
            'scope' => $b->scope,
            'domain_id' => $b->domainId,
            'started_at' => $b->startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $b->completedAt?->format('Y-m-d H:i:s'),
            'trigger_type' => $b->triggerType,
            'requested_by_admin_id' => $b->requestedByAdminId,
            'reports_count_at_backup' => $b->reportsCountAtBackup,
            'status' => $b->status,
            'error_message' => $b->errorMessage,
            'notes' => $b->notes,
            'size_bytes' => $b->sizeBytes,
            'created_at' => $b->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $b->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($includeFiles) {
            $row['files'] = array_map(static fn ($f) => [
                'id' => $f->id,
                'disk' => $f->disk,
                'path' => $f->path,
                'original_filename' => $f->originalFilename,
                'mime_type' => $f->mimeType,
                'size_bytes' => $f->sizeBytes,
                'created_at' => $f->createdAt?->format('Y-m-d H:i:s'),
            ], $b->files);
        }

        return $row;
    }
}
