<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Entities\Provider as ProviderEntity;

class Provider extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'website',
        'logo_url',
        'description',
        'technologies',
        'is_active',
    ];

    protected $casts = [
        'technologies' => 'array',
        'is_active' => 'boolean',
    ];
    
    public function toEntity(): ProviderEntity
    {
        return new ProviderEntity(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            website: $this->website,
            logoUrl: $this->logo_url,
            description: $this->description,
            technologies: $this->technologies ?? [],
            isActive: $this->is_active
        );
    }
}
