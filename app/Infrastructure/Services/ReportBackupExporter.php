<?php

namespace App\Infrastructure\Services;

use App\Models\Report;
use Illuminate\Support\Facades\Storage;

class ReportBackupExporter
{
    /**
     * Stream reports as JSON to storage. Returns [disk, relative_path, size_bytes, mime_type].
     *
     * @return array{0: string, 1: string, 2: int, 3: string}
     */
    public function exportToJson(int $backupId, ?int $domainId, string $scope): array
    {
        $disk = 'local';
        $relativePath = "backups/{$backupId}/reports_export.json";
        $fullPath = Storage::disk($disk)->path($relativePath);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $handle = fopen($fullPath, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create backup file.');
        }

        $meta = [
            'version' => 1,
            'format' => 'json',
            'scope' => $scope,
            'domain_id' => $domainId,
            'exported_at' => now()->toIso8601String(),
        ];

        fwrite($handle, '{"meta":'.json_encode($meta).',"reports":[');

        $first = true;
        $query = Report::query()->orderBy('id');
        if ($domainId !== null) {
            $query->where('domain_id', $domainId);
        }

        $query->chunkById(200, function ($reports) use ($handle, &$first): void {
            foreach ($reports as $report) {
                if (! $first) {
                    fwrite($handle, ',');
                }
                $first = false;
                $payload = [
                    'id' => $report->id,
                    'domain_id' => $report->domain_id,
                    'report_date' => $report->report_date?->format('Y-m-d'),
                    'report_period_start' => $report->report_period_start?->toIso8601String(),
                    'report_period_end' => $report->report_period_end?->toIso8601String(),
                    'generated_at' => $report->generated_at?->toIso8601String(),
                    'total_processing_time' => $report->total_processing_time,
                    'data_version' => $report->data_version,
                    'raw_data' => $report->raw_data,
                    'status' => $report->status,
                    'created_at' => $report->created_at?->toIso8601String(),
                    'updated_at' => $report->updated_at?->toIso8601String(),
                ];
                fwrite($handle, json_encode($payload));
            }
        });

        fwrite($handle, ']}');
        fclose($handle);

        $size = (int) filesize($fullPath);

        return [$disk, $relativePath, $size, 'application/json'];
    }
}
