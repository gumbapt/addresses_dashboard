<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Entities\ReportProvider as ReportProviderEntity;

class ReportProvider extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'report_id',
        'provider_id',
        'original_name',
        'technology',
        'total_count',
        'success_rate',
        'avg_speed',
        'rank_position',
    ];

    protected $casts = [
        'total_count' => 'integer',
        'success_rate' => 'decimal:2',
        'avg_speed' => 'decimal:2',
        'rank_position' => 'integer',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    // Scopes
    public function scopeByTechnology($query, string $technology)
    {
        return $query->where('technology', $technology);
    }

    public function scopeTopRanked($query)
    {
        return $query->whereNotNull('rank_position')->orderBy('rank_position');
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function toEntity(): ReportProviderEntity
    {
        return new ReportProviderEntity(
            id: $this->id,
            reportId: $this->report_id,
            providerId: $this->provider_id,
            originalName: $this->original_name,
            technology: $this->technology,
            totalCount: $this->total_count,
            successRate: $this->success_rate,
            avgSpeed: $this->avg_speed,
            rankPosition: $this->rank_position
        );
    }
}
