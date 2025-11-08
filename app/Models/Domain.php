<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Entities\Domain as DomainEntity;

class Domain extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'domain_group_id',
        'name',
        'slug',
        'domain_url',
        'site_id',
        'api_key',
        'status',
        'timezone',
        'wordpress_version',
        'plugin_version',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
    
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    
    public function domainGroup()
    {
        return $this->belongsTo(DomainGroup::class, 'domain_group_id');
    }
    
    public function toEntity(): DomainEntity
    {
        return new DomainEntity(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            domain_url: $this->domain_url,
            site_id: $this->site_id,
            api_key: $this->api_key,
            status: $this->status,
            timezone: $this->timezone,
            wordpress_version: $this->wordpress_version,
            plugin_version: $this->plugin_version,
            settings: is_array($this->settings) ? $this->settings : json_decode($this->settings, true) ?? [],
            is_active: $this->is_active,
        );
    }
}
