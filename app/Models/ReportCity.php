<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Entities\ReportCity as ReportCityEntity;

class ReportCity extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'report_id',
        'city_id',
        'request_count',
        'zip_codes',
    ];

    protected $casts = [
        'request_count' => 'integer',
        'zip_codes' => 'array',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Scopes
    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeTopByRequests($query, int $limit = 10)
    {
        return $query->orderBy('request_count', 'desc')->limit($limit);
    }

    public function scopeWithZipCodes($query)
    {
        return $query->whereJsonLength('zip_codes', '>', 0);
    }

    // Helper methods
    public function hasZipCodes(): bool
    {
        return !empty($this->zip_codes);
    }

    public function getZipCodeCount(): int
    {
        return count($this->zip_codes ?? []);
    }

    public function toEntity(): ReportCityEntity
    {
        return new ReportCityEntity(
            id: $this->id,
            reportId: $this->report_id,
            cityId: $this->city_id,
            requestCount: $this->request_count,
            zipCodes: $this->zip_codes ?? []
        );
    }
}
