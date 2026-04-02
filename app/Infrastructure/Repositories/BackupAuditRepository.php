<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\BackupAuditRepositoryInterface;
use App\Models\BackupAuditLog;

class BackupAuditRepository implements BackupAuditRepositoryInterface
{
    public function record(
        string $action,
        ?int $adminId = null,
        ?int $backupId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null,
    ): void {
        BackupAuditLog::create([
            'admin_id' => $adminId,
            'action' => $action,
            'backup_id' => $backupId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function listPaginated(int $page, int $perPage): array
    {
        $paginator = BackupAuditLog::query()
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = [];
        foreach ($paginator->items() as $row) {
            $data[] = [
                'id' => $row->id,
                'admin_id' => $row->admin_id,
                'action' => $row->action,
                'backup_id' => $row->backup_id,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'metadata' => $row->metadata,
                'created_at' => $row->created_at?->toIso8601String(),
            ];
        }

        return [
            'data' => $data,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
