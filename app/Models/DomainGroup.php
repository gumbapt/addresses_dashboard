<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DomainGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'settings',
        'max_domains',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'max_domains' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Boot method para gerar slug automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($domainGroup) {
            if (empty($domainGroup->slug)) {
                $domainGroup->slug = Str::slug($domainGroup->name);
            }
        });

        static::updating(function ($domainGroup) {
            if ($domainGroup->isDirty('name') && empty($domainGroup->slug)) {
                $domainGroup->slug = Str::slug($domainGroup->name);
            }
        });
    }

    /**
     * Relacionamento: Domains pertencentes ao grupo
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'domain_group_id');
    }

    /**
     * Relacionamento: Admin que criou o grupo
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Relacionamento: Admin que atualizou o grupo
     */
    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * Scope: Apenas grupos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Grupos com domínios
     */
    public function scopeWithDomains($query)
    {
        return $query->has('domains');
    }

    /**
     * Verifica se o grupo atingiu o limite de domínios
     */
    public function hasReachedMaxDomains(): bool
    {
        if (is_null($this->max_domains)) {
            return false;
        }

        return $this->domains()->count() >= $this->max_domains;
    }

    /**
     * Retorna quantidade de domínios disponíveis
     */
    public function getAvailableDomainsCount(): ?int
    {
        if (is_null($this->max_domains)) {
            return null;
        }

        return max(0, $this->max_domains - $this->domains()->count());
    }
}
