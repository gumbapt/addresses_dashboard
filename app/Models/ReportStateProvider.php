<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportStateProvider extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'report_id',
        'state_id',
        'provider_id',
        'original_name',
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    // Scopes
    public function scopeByState($query, int $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeByStateAndProvider($query, int $stateId, int $providerId)
    {
        return $query->where('state_id', $stateId)
                     ->where('provider_id', $providerId);
    }

    public function scopeByReport($query, int $reportId)
    {
        return $query->where('report_id', $reportId);
    }
}

