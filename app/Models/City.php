<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Entities\City as CityEntity;

class City extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'state_id',
        'latitude',
        'longitude',
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
    
    public function zipCodes(): HasMany
    {
        return $this->hasMany(ZipCode::class);
    }
    
    public function toEntity(): CityEntity
    {
        return new CityEntity(
            id: $this->id,
            name: $this->name,
            stateId: $this->state_id,
            latitude: $this->latitude,
            longitude: $this->longitude,
            population: $this->population,
            isActive: $this->is_active
        );
    }
}

