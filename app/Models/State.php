<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Entities\State as StateEntity;

class State extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'code',
        'name',
        'timezone',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
    
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
    
    public function zipCodes(): HasMany
    {
        return $this->hasMany(ZipCode::class);
    }
    
    public function toEntity(): StateEntity
    {
        return new StateEntity(
            id: $this->id,
            code: $this->code,
            name: $this->name,
            timezone: $this->timezone,
            latitude: $this->latitude,
            longitude: $this->longitude,
            isActive: $this->is_active
        );
    }
}

