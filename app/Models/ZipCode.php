<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Entities\ZipCode as ZipCodeEntity;

class ZipCode extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'code',
        'state_id',
        'city_id',
        'latitude',
        'longitude',
        'type',
        'population',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'population' => 'integer',
    ];
    
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function toEntity(): ZipCodeEntity
    {
        return new ZipCodeEntity(
            id: $this->id,
            code: $this->code,
            stateId: $this->state_id,
            cityId: $this->city_id,
            latitude: $this->latitude,
            longitude: $this->longitude,
            type: $this->type,
            population: $this->population,
            isActive: $this->is_active
        );
    }
}

