<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Entities\ReportState as ReportStateEntity;

class ReportState extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'report_id',
        'state_id',
        'request_count',
        'success_rate',
        'avg_speed',
    ];

    protected $casts = [
        'request_count' => 'integer',
        'success_rate' => 'decimal:2',
        'avg_speed' => 'decimal:2',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    // Scopes
    public function scopeByState($query, int $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeTopByRequests($query, int $limit = 10)
    {
        return $query->orderBy('request_count', 'desc')->limit($limit);
    }

    public function toEntity(): ReportStateEntity
    {
        return new ReportStateEntity(
            id: $this->id,
            reportId: $this->report_id,
            stateId: $this->state_id,
            requestCount: $this->request_count,
            successRate: $this->success_rate,
            avgSpeed: $this->avg_speed
        );
    }
}
