<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Entities\ReportZipCode as ReportZipCodeEntity;

class ReportZipCode extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'report_id',
        'zip_code_id',
        'request_count',
        'percentage',
    ];

    protected $casts = [
        'request_count' => 'integer',
        'percentage' => 'decimal:2',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function zipCode(): BelongsTo
    {
        return $this->belongsTo(ZipCode::class);
    }

    // Scopes
    public function scopeByZipCode($query, int $zipCodeId)
    {
        return $query->where('zip_code_id', $zipCodeId);
    }

    public function scopeTopByRequests($query, int $limit = 20)
    {
        return $query->orderBy('request_count', 'desc')->limit($limit);
    }

    public function scopeTopByPercentage($query, int $limit = 20)
    {
        return $query->orderBy('percentage', 'desc')->limit($limit);
    }

    public function toEntity(): ReportZipCodeEntity
    {
        return new ReportZipCodeEntity(
            id: $this->id,
            reportId: $this->report_id,
            zipCodeId: $this->zip_code_id,
            requestCount: $this->request_count,
            percentage: $this->percentage
        );
    }
}
