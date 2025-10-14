<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Domain\Entities\Report as ReportEntity;
use Carbon\Carbon;

class Report extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'domain_id',
        'report_date',
        'report_period_start',
        'report_period_end',
        'generated_at',
        'total_processing_time',
        'data_version',
        'raw_data',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
        'report_period_start' => 'datetime',
        'report_period_end' => 'datetime',
        'generated_at' => 'datetime',
        'raw_data' => 'array',
        'total_processing_time' => 'integer',
    ];

    // Relationships
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function summary(): HasOne
    {
        return $this->hasOne(ReportSummary::class);
    }

    public function reportProviders(): HasMany
    {
        return $this->hasMany(ReportProvider::class);
    }

    public function reportStates(): HasMany
    {
        return $this->hasMany(ReportState::class);
    }

    public function reportCities(): HasMany
    {
        return $this->hasMany(ReportCity::class);
    }

    public function reportZipCodes(): HasMany
    {
        return $this->hasMany(ReportZipCode::class);
    }

    // Scopes
    public function scopeByDomain($query, int $domainId)
    {
        return $query->where('domain_id', $domainId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('report_date', 'desc')->orderBy('generated_at', 'desc');
    }

    /**
     * Convert model to domain entity
     */
    public function toEntity(): ReportEntity
    {
        return new ReportEntity(
            id: $this->id,
            domainId: $this->domain_id,
            reportDate: $this->report_date->format('Y-m-d'),
            reportPeriodStart: $this->report_period_start,
            reportPeriodEnd: $this->report_period_end,
            generatedAt: $this->generated_at,
            totalProcessingTime: $this->total_processing_time,
            dataVersion: $this->data_version,
            rawData: $this->raw_data,
            status: $this->status,
            createdAt: $this->created_at,
            updatedAt: $this->updated_at
        );
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }
}
