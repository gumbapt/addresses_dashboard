<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Entities\ReportSummary as ReportSummaryEntity;

class ReportSummary extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'report_id',
        'total_requests',
        'success_rate',
        'failed_requests',
        'avg_requests_per_hour',
        'unique_providers',
        'unique_states',
        'unique_zip_codes',
    ];

    protected $casts = [
        'total_requests' => 'integer',
        'success_rate' => 'decimal:2',
        'failed_requests' => 'integer',
        'avg_requests_per_hour' => 'decimal:2',
        'unique_providers' => 'integer',
        'unique_states' => 'integer',
        'unique_zip_codes' => 'integer',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function toEntity(): ReportSummaryEntity
    {
        return new ReportSummaryEntity(
            id: $this->id,
            reportId: $this->report_id,
            totalRequests: $this->total_requests,
            successRate: $this->success_rate,
            failedRequests: $this->failed_requests,
            avgRequestsPerHour: $this->avg_requests_per_hour,
            uniqueProviders: $this->unique_providers,
            uniqueStates: $this->unique_states,
            uniqueZipCodes: $this->unique_zip_codes
        );
    }
}
