<?php

namespace App\Models;

use App\Domain\Entities\ChatUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Domain\Entities\Admin as AdminEntity;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Permission;
use App\Models\Role;

class Admin extends Authenticatable implements ChatUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_super_admin',
        'last_login_at',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function toEntity(): ChatUser
    {
        return new AdminEntity(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $this->password,
            isActive: $this->is_active,
            isSuperAdmin: $this->is_super_admin,
            lastLoginAt: $this->last_login_at,
            createdAt: $this->created_at,
            updatedAt: $this->updated_at
        );
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'admin_roles')
                    ->withPivot(['assigned_at', 'assigned_by']);
    }

    public function permissions()
    {
        return $this->roles()->with('permissions')->get()
                    ->pluck('permissions')->flatten()->unique('id');
    }

    /**
     * Relacionamento: Grupos de domínios atribuídos a este admin
     */
    public function domainGroups()
    {
        return $this->belongsToMany(DomainGroup::class, 'admin_domain_groups')
                    ->using(AdminDomainGroup::class)
                    ->withPivot(['assigned_at', 'assigned_by', 'is_active'])
                    ->wherePivot('is_active', true)
                    ->withTimestamps();
    }

    /**
     * Relacionamento: Associações admin-domain-group (com todos os dados do pivot)
     */
    public function adminDomainGroups()
    {
        return $this->hasMany(AdminDomainGroup::class);
    }

    /**
     * Relacionamento: Admins criados por este admin (hierarquia)
     */
    public function createdAdmins()
    {
        return $this->hasMany(Admin::class, 'created_by');
    }

    /**
     * Relacionamento: Admin que criou este admin (hierarquia)
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Obter grupos de domínios acessíveis (incluindo herança)
     */
    public function getAccessibleDomainGroups(): array
    {
        // Se for sudo admin, retorna todos os grupos
        if ($this->is_super_admin) {
            return DomainGroup::active()->pluck('id')->toArray();
        }

        // Retorna grupos atribuídos diretamente
        return $this->domainGroups()->pluck('domain_groups.id')->toArray();
    }

    /**
     * Verificar se admin pode acessar um grupo específico
     */
    public function canAccessDomainGroup(int $domainGroupId): bool
    {
        // Sudo admin tem acesso a todos
        if ($this->is_super_admin) {
            return true;
        }

        return $this->domainGroups()->where('domain_groups.id', $domainGroupId)->exists();
    }

    /**
     * Obter grupos que este admin pode atribuir a outros (para herança)
     */
    public function getAssignableDomainGroups(): array
    {
        // Sudo admin pode atribuir todos os grupos
        if ($this->is_super_admin) {
            return DomainGroup::active()->pluck('id')->toArray();
        }

        // Admin nível 2 pode atribuir apenas os grupos que possui
        return $this->getAccessibleDomainGroups();
    }

    /**
     * Get accessible domains for this admin
     */
    public function getAccessibleDomains(): array
    {
        $service = app(\App\Domain\Services\DomainPermissionService::class);
        return $service->getAccessibleDomains($this);
    }

    /**
     * Check if admin can access a specific domain
     */
    public function canAccessDomain(int $domainId): bool
    {
        $service = app(\App\Domain\Services\DomainPermissionService::class);
        return $service->canAccessDomain($this, $domainId);
    }

    /**
     * Check if admin has global domain access
     */
    public function hasGlobalDomainAccess(): bool
    {
        $service = app(\App\Domain\Services\DomainPermissionService::class);
        return $service->hasGlobalDomainAccess($this);
    }

    // Implementação da interface ChatUser

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getType(): string
    {
        return 'admin';
    }

    // O método isActive() já existe e é compatível com a interface
}
