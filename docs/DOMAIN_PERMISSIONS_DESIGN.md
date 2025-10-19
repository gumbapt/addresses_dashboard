# 🔐 Sistema de Permissões por Domínio - Design Completo

## 🎯 Objetivo

Permitir que **roles** tenham acesso limitado a **domínios específicos**, implementando controle de acesso granular no nível de domínio.

---

## 🏗️ Arquitetura Proposta

### **Cenários de Uso**

#### **Cenário 1: Admin Global (Super Admin)**
- **Acesso:** TODOS os domínios
- **Permissão:** `domain.access.all`
- **Configuração:** Sem restrições de domínio

#### **Cenário 2: Admin de Domínio Único**
- **Acesso:** Apenas 1 domínio específico
- **Permissão:** `domain.access.assigned`
- **Configuração:** Lista de domain_ids na tabela `role_domain_permissions`

#### **Cenário 3: Admin Multi-Domínio**
- **Acesso:** Múltiplos domínios específicos (ex: smarterhome.ai + ispfinder.net)
- **Permissão:** `domain.access.assigned`
- **Configuração:** Lista de domain_ids na tabela `role_domain_permissions`

#### **Cenário 4: Admin sem Acesso a Relatórios**
- **Acesso:** Nenhum domínio
- **Permissão:** Sem permissão `domain.access.*`
- **Configuração:** Não pode ver relatórios

---

## 🗄️ Estrutura de Banco de Dados

### **Nova Tabela: `role_domain_permissions`**

```sql
CREATE TABLE role_domain_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    domain_id BIGINT UNSIGNED NOT NULL,
    can_view BOOLEAN DEFAULT TRUE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    can_submit_reports BOOLEAN DEFAULT FALSE,
    assigned_at DATETIME NOT NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES admins(id),
    
    UNIQUE KEY unique_role_domain (role_id, domain_id)
);

CREATE INDEX idx_role_domain_active ON role_domain_permissions(role_id, is_active);
CREATE INDEX idx_domain_role_active ON role_domain_permissions(domain_id, is_active);
```

### **Nova Permissão na Tabela `permissions`**

```php
// Adicionar novas permissões
[
    'slug' => 'domain.access.all',
    'name' => 'Access All Domains',
    'description' => 'Can access reports from all domains without restrictions',
],
[
    'slug' => 'domain.access.assigned',
    'name' => 'Access Assigned Domains',
    'description' => 'Can access only specifically assigned domains',
],
```

---

## 🔧 Classes e Estrutura de Código

### **1. Model: `RoleDomainPermission.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleDomainPermission extends Model
{
    protected $fillable = [
        'role_id',
        'domain_id',
        'can_view',
        'can_edit',
        'can_delete',
        'can_submit_reports',
        'assigned_at',
        'assigned_by',
        'is_active',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_submit_reports' => 'boolean',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }
}
```

### **2. Service: `DomainPermissionService.php`**

```php
<?php

namespace App\Domain\Services;

use App\Models\Admin;
use App\Models\Domain;
use App\Models\Role;
use App\Models\RoleDomainPermission;
use App\Domain\Exceptions\UnauthorizedException;

class DomainPermissionService
{
    /**
     * Verifica se um admin tem acesso a um domínio específico
     */
    public function canAccessDomain(Admin $admin, int $domainId): bool
    {
        // 1. Verificar se tem permissão global
        if ($this->hasGlobalDomainAccess($admin)) {
            return true;
        }

        // 2. Verificar se tem acesso ao domínio específico
        return $this->hasAssignedDomainAccess($admin, $domainId);
    }

    /**
     * Verifica se admin tem acesso global a todos os domínios
     */
    public function hasGlobalDomainAccess(Admin $admin): bool
    {
        foreach ($admin->roles as $role) {
            $permissions = $role->permissions->pluck('slug');
            if ($permissions->contains('domain.access.all')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se admin tem acesso a um domínio específico via role
     */
    public function hasAssignedDomainAccess(Admin $admin, int $domainId): bool
    {
        $roleIds = $admin->roles->pluck('id');

        return RoleDomainPermission::whereIn('role_id', $roleIds)
            ->where('domain_id', $domainId)
            ->where('can_view', true)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Retorna lista de domínios acessíveis por um admin
     */
    public function getAccessibleDomains(Admin $admin): array
    {
        // Se tem acesso global, retorna todos
        if ($this->hasGlobalDomainAccess($admin)) {
            return Domain::where('is_active', true)->pluck('id')->toArray();
        }

        // Senão, retorna apenas os domínios atribuídos
        $roleIds = $admin->roles->pluck('id');

        return RoleDomainPermission::whereIn('role_id', $roleIds)
            ->where('can_view', true)
            ->where('is_active', true)
            ->pluck('domain_id')
            ->unique()
            ->toArray();
    }

    /**
     * Atribui domínios a uma role
     */
    public function assignDomainsToRole(
        Role $role,
        array $domainIds,
        Admin $assignedBy,
        array $permissions = ['can_view' => true]
    ): void {
        foreach ($domainIds as $domainId) {
            RoleDomainPermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'domain_id' => $domainId,
                ],
                [
                    'can_view' => $permissions['can_view'] ?? true,
                    'can_edit' => $permissions['can_edit'] ?? false,
                    'can_delete' => $permissions['can_delete'] ?? false,
                    'can_submit_reports' => $permissions['can_submit_reports'] ?? false,
                    'assigned_at' => now(),
                    'assigned_by' => $assignedBy->id,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Remove domínios de uma role
     */
    public function revokeDomainsFromRole(Role $role, array $domainIds): void
    {
        RoleDomainPermission::where('role_id', $role->id)
            ->whereIn('domain_id', $domainIds)
            ->delete();
    }

    /**
     * Retorna domínios atribuídos a uma role
     */
    public function getRoleDomains(Role $role): array
    {
        return RoleDomainPermission::where('role_id', $role->id)
            ->where('is_active', true)
            ->with('domain')
            ->get()
            ->map(fn($rdp) => [
                'domain_id' => $rdp->domain_id,
                'domain_name' => $rdp->domain->name,
                'can_view' => $rdp->can_view,
                'can_edit' => $rdp->can_edit,
                'can_delete' => $rdp->can_delete,
                'can_submit_reports' => $rdp->can_submit_reports,
                'assigned_at' => $rdp->assigned_at,
            ])
            ->toArray();
    }
}
```

### **3. Middleware: `CheckDomainAccess.php`**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domain\Services\DomainPermissionService;
use App\Models\Admin;

class CheckDomainAccess
{
    public function __construct(
        private DomainPermissionService $domainPermissionService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof Admin) {
            return response()->json(['message' => 'Admin privileges required.'], 401);
        }

        // Extrair domain_id da rota
        $domainId = $request->route('domainId') ?? $request->route('id');

        // Se não há domain_id na rota, permitir (será tratado no controller)
        if (!$domainId) {
            return $next($request);
        }

        // Verificar acesso ao domínio
        if (!$this->domainPermissionService->canAccessDomain($user, (int) $domainId)) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to access this domain.',
            ], 403);
        }

        return $next($request);
    }
}
```

### **4. Repository: `DomainPermissionRepositoryInterface.php`**

```php
<?php

namespace App\Domain\Repositories;

use App\Models\Admin;
use App\Models\Role;

interface DomainPermissionRepositoryInterface
{
    public function canAccessDomain(int $adminId, int $domainId): bool;
    
    public function getAccessibleDomains(int $adminId): array;
    
    public function assignDomainsToRole(
        int $roleId,
        array $domainIds,
        int $assignedBy,
        array $permissions = []
    ): void;
    
    public function revokeDomainsFromRole(int $roleId, array $domainIds): void;
    
    public function getRoleDomains(int $roleId): array;
}
```

---

## 📋 Migration

### **`create_role_domain_permissions_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_domain_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('domain_id');
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_submit_reports')->default(false);
            $table->dateTime('assigned_at');
            $table->unsignedBigInteger('assigned_by');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('admins');
            
            $table->unique(['role_id', 'domain_id'], 'unique_role_domain');
            $table->index(['role_id', 'is_active'], 'idx_role_active');
            $table->index(['domain_id', 'is_active'], 'idx_domain_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_domain_permissions');
    }
};
```

---

## 🔄 Fluxo de Autorização

### **Diagrama de Fluxo**

```
Admin acessa relatório de domínio
           ↓
    CheckDomainAccess Middleware
           ↓
    ┌─────────────────────┐
    │ Extrair domain_id   │
    │ da rota/request     │
    └─────────────────────┘
           ↓
    ┌─────────────────────┐
    │ Verificar permissão │
    │ global?             │
    └─────────────────────┘
        ↓           ↓
       SIM         NÃO
        ↓           ↓
    PERMITIR   Verificar domain
                específico?
                    ↓
                SIM   NÃO
                 ↓     ↓
            PERMITIR  NEGAR (403)
```

### **Lógica de Verificação**

```php
// Passo 1: Verificar permissão global
if ($admin->hasPermission('domain.access.all')) {
    return true; // Acesso total
}

// Passo 2: Verificar permissão de domínio específico
if ($admin->hasPermission('domain.access.assigned')) {
    // Verificar se o domínio está na lista de acessos
    $accessibleDomains = $domainPermissionService->getAccessibleDomains($admin);
    return in_array($domainId, $accessibleDomains);
}

// Passo 3: Sem permissão
return false;
```

---

## 🎯 API Endpoints Necessários

### **1. Atribuir Domínios a uma Role**

```http
POST /api/admin/role/assign-domains
```

**Request:**
```json
{
  "role_id": 2,
  "domain_ids": [1, 3, 5],
  "permissions": {
    "can_view": true,
    "can_edit": false,
    "can_delete": false,
    "can_submit_reports": false
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Domains assigned to role successfully",
  "data": {
    "role_id": 2,
    "role_name": "Domain Manager",
    "assigned_domains": 3,
    "domains": [
      {"id": 1, "name": "zip.50g.io"},
      {"id": 3, "name": "ispfinder.net"},
      {"id": 5, "name": "broadbandcheck.io"}
    ]
  }
}
```

### **2. Remover Domínios de uma Role**

```http
DELETE /api/admin/role/revoke-domains
```

**Request:**
```json
{
  "role_id": 2,
  "domain_ids": [3, 5]
}
```

### **3. Listar Domínios de uma Role**

```http
GET /api/admin/role/{roleId}/domains
```

**Response:**
```json
{
  "success": true,
  "data": {
    "role": {
      "id": 2,
      "name": "Domain Manager"
    },
    "domains": [
      {
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "can_view": true,
        "can_edit": false,
        "can_delete": false,
        "assigned_at": "2025-10-18T10:30:00Z"
      }
    ],
    "total": 1
  }
}
```

### **4. Listar Domínios Acessíveis por um Admin**

```http
GET /api/admin/my-domains
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_type": "assigned", // ou "all"
    "domains": [
      {
        "id": 1,
        "name": "zip.50g.io",
        "slug": "zip-50g-io",
        "permissions": {
          "can_view": true,
          "can_edit": false,
          "can_delete": false
        }
      }
    ],
    "total": 1
  }
}
```

---

## 🔧 Modificações Necessárias

### **1. Atualizar Role Model**

```php
// app/Models/Role.php

public function domainPermissions()
{
    return $this->hasMany(RoleDomainPermission::class);
}

public function domains()
{
    return $this->belongsToMany(Domain::class, 'role_domain_permissions')
                ->withPivot(['can_view', 'can_edit', 'can_delete', 'can_submit_reports', 'assigned_at', 'assigned_by'])
                ->wherePivot('is_active', true);
}
```

### **2. Atualizar Admin Model**

```php
// app/Models/Admin.php

public function getAccessibleDomains(): array
{
    $service = app(DomainPermissionService::class);
    return $service->getAccessibleDomains($this);
}

public function canAccessDomain(int $domainId): bool
{
    $service = app(DomainPermissionService::class);
    return $service->canAccessDomain($this, $domainId);
}
```

### **3. Atualizar Endpoints Existentes**

**Aplicar middleware em rotas de domínio:**

```php
// routes/api.php

Route::middleware(['auth:sanctum', 'admin.auth'])->prefix('admin/reports')->group(function () {
    // Rotas sem restrição de domínio
    Route::get('/', [ReportController::class, 'index']);
    Route::get('/recent', [ReportController::class, 'recent']);
    
    // Rotas que precisam verificar acesso ao domínio
    Route::middleware('check.domain.access')->group(function () {
        Route::get('/domain/{domainId}/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/domain/{domainId}/aggregate', [ReportController::class, 'aggregate']);
    });
    
    // Global reports - apenas para quem tem domain.access.all
    Route::middleware('require.global.domain.access')->prefix('global')->group(function () {
        Route::get('/domain-ranking', [ReportController::class, 'globalRanking']);
        Route::get('/comparison', [ReportController::class, 'compareDomains']);
    });
});
```

### **4. Filtrar Endpoints Globais**

**Modificar `GetGlobalDomainRankingUseCase`:**

```php
public function execute(
    string $sortBy = 'score',
    ?string $dateFrom = null,
    ?string $dateTo = null,
    ?int $minReports = null,
    ?array $accessibleDomainIds = null // NOVO parâmetro
): array {
    // Get domains
    $query = Domain::where('is_active', true);
    
    // Filtrar por domínios acessíveis se não for global
    if ($accessibleDomainIds !== null) {
        $query->whereIn('id', $accessibleDomainIds);
    }
    
    $domains = $query->get();
    
    // ... resto do código
}
```

**Modificar Controller:**

```php
public function globalRanking(Request $request): JsonResponse
{
    $admin = $request->user();
    
    // Verificar se tem acesso global
    $domainPermissionService = app(DomainPermissionService::class);
    $accessibleDomains = null;
    
    if (!$domainPermissionService->hasGlobalDomainAccess($admin)) {
        // Limitar apenas aos domínios acessíveis
        $accessibleDomains = $domainPermissionService->getAccessibleDomains($admin);
    }
    
    $ranking = $this->getGlobalDomainRankingUseCase->execute(
        $sortBy,
        $dateFrom,
        $dateTo,
        $minReports,
        $accessibleDomains // Passar domínios acessíveis
    );
    
    // ... resto do código
}
```

---

## 📊 Casos de Uso Práticos

### **Caso 1: Super Admin (Acesso Total)**

```php
// Setup
$superAdminRole = Role::create(['name' => 'Super Admin']);
$superAdminRole->permissions()->attach(
    Permission::where('slug', 'domain.access.all')->first()
);

// Resultado
$admin->canAccessDomain(1); // true
$admin->canAccessDomain(2); // true
$admin->canAccessDomain(3); // true
$admin->getAccessibleDomains(); // [1, 2, 3, 4] (todos)
```

### **Caso 2: Domain Manager (Domínios Específicos)**

```php
// Setup
$managerRole = Role::create(['name' => 'Domain Manager']);
$managerRole->permissions()->attach(
    Permission::where('slug', 'domain.access.assigned')->first()
);

$domainPermissionService->assignDomainsToRole(
    $managerRole,
    [1, 3], // Apenas zip.50g.io e ispfinder.net
    $superAdmin
);

// Resultado
$admin->canAccessDomain(1); // true
$admin->canAccessDomain(2); // false
$admin->canAccessDomain(3); // true
$admin->getAccessibleDomains(); // [1, 3]
```

### **Caso 3: Cliente de Domínio Único**

```php
// Setup
$clientRole = Role::create(['name' => 'Client']);
$clientRole->permissions()->attach(
    Permission::where('slug', 'domain.access.assigned')->first()
);

$domainPermissionService->assignDomainsToRole(
    $clientRole,
    [2], // Apenas smarterhome.ai
    $superAdmin,
    ['can_view' => true, 'can_edit' => false]
);

// Resultado
$admin->canAccessDomain(1); // false
$admin->canAccessDomain(2); // true (apenas visualização)
$admin->getAccessibleDomains(); // [2]
```

---

## 🧪 Testes Sugeridos

### **Feature Tests**

```php
// tests/Feature/DomainPermissionsTest.php

test_super_admin_can_access_all_domains()
test_admin_can_access_only_assigned_domains()
test_admin_cannot_access_unassigned_domains()
test_admin_with_no_domain_permission_cannot_access_any()
test_global_endpoints_respect_domain_permissions()
test_domain_ranking_shows_only_accessible_domains()
test_domain_comparison_only_includes_accessible_domains()
```

### **Unit Tests**

```php
// tests/Unit/Services/DomainPermissionServiceTest.php

test_hasGlobalDomainAccess_returns_true_for_global_permission()
test_hasAssignedDomainAccess_returns_true_for_assigned_domain()
test_getAccessibleDomains_returns_all_for_global_access()
test_getAccessibleDomains_returns_assigned_only()
test_assignDomainsToRole_creates_permissions()
test_revokeDomainsFromRole_removes_permissions()
```

---

## 📝 Seeders

### **DomainPermissionSeeder.php**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Admin;
use App\Domain\Services\DomainPermissionService;

class DomainPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar permissões
        Permission::firstOrCreate(
            ['slug' => 'domain.access.all'],
            [
                'name' => 'Access All Domains',
                'description' => 'Can access reports from all domains',
                'is_active' => true,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'domain.access.assigned'],
            [
                'name' => 'Access Assigned Domains',
                'description' => 'Can access only assigned domains',
                'is_active' => true,
            ]
        );

        // 2. Atribuir permissão global ao super-admin
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $permission = Permission::where('slug', 'domain.access.all')->first();
            if (!$superAdminRole->permissions->contains($permission->id)) {
                $superAdminRole->permissions()->attach($permission);
            }
        }

        // 3. Criar role de exemplo: Domain Manager
        $domainManagerRole = Role::firstOrCreate(
            ['slug' => 'domain-manager'],
            [
                'name' => 'Domain Manager',
                'description' => 'Manages specific domains',
                'is_active' => true,
            ]
        );

        $permission = Permission::where('slug', 'domain.access.assigned')->first();
        if (!$domainManagerRole->permissions->contains($permission->id)) {
            $domainManagerRole->permissions()->attach($permission);
        }

        // 4. Exemplo: Atribuir domínios 1 e 2 à role Domain Manager
        $superAdmin = Admin::where('email', 'admin@dashboard.com')->first();
        if ($superAdmin) {
            $service = app(DomainPermissionService::class);
            $service->assignDomainsToRole(
                $domainManagerRole,
                [1, 2],
                $superAdmin
            );
        }

        $this->command->info('✅ Domain permissions seeded!');
    }
}
```

---

## 🎯 Rotas Propostas

```php
// routes/api.php

Route::middleware(['auth:sanctum', 'admin.auth'])->prefix('admin')->group(function () {
    // Domain Permissions Management
    Route::prefix('role')->group(function () {
        Route::post('/assign-domains', [RoleController::class, 'assignDomains'])
            ->name('admin.role.assign-domains');
        
        Route::delete('/revoke-domains', [RoleController::class, 'revokeDomains'])
            ->name('admin.role.revoke-domains');
        
        Route::get('/{roleId}/domains', [RoleController::class, 'getDomains'])
            ->name('admin.role.domains');
    });
    
    // Admin's accessible domains
    Route::get('/my-domains', [AdminController::class, 'getMyDomains'])
        ->name('admin.my-domains');
});
```

---

## 💡 Exemplo de Implementação Completa

### **Controller Method**

```php
// app/Http/Controllers/Api/Admin/RoleController.php

public function assignDomains(Request $request): JsonResponse
{
    $request->validate([
        'role_id' => 'required|exists:roles,id',
        'domain_ids' => 'required|array',
        'domain_ids.*' => 'exists:domains,id',
        'permissions' => 'sometimes|array',
        'permissions.can_view' => 'boolean',
        'permissions.can_edit' => 'boolean',
        'permissions.can_delete' => 'boolean',
        'permissions.can_submit_reports' => 'boolean',
    ]);

    $role = Role::findOrFail($request->role_id);
    $admin = $request->user();

    $domainPermissionService = app(DomainPermissionService::class);
    $domainPermissionService->assignDomainsToRole(
        $role,
        $request->domain_ids,
        $admin,
        $request->permissions ?? []
    );

    $assignedDomains = Domain::whereIn('id', $request->domain_ids)->get();

    return response()->json([
        'success' => true,
        'message' => 'Domains assigned to role successfully',
        'data' => [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'assigned_domains' => count($request->domain_ids),
            'domains' => $assignedDomains->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ]),
        ],
    ]);
}
```

---

## 🎨 Frontend UI Sugerida

### **Tela de Edição de Role**

```
┌─────────────────────────────────────────────────────────┐
│ Editar Role: Domain Manager                             │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Nome: Domain Manager                                    │
│ Slug: domain-manager                                    │
│                                                         │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Permissões Gerais:                                  │ │
│ │ ☑ Reports: View                                     │ │
│ │ ☑ Reports: Create                                   │ │
│ │ ☐ Reports: Delete                                   │ │
│ │                                                     │ │
│ │ Acesso a Domínios:                                  │ │
│ │ ◉ Domínios Específicos                              │ │
│ │ ○ Todos os Domínios                                 │ │
│ │                                                     │ │
│ │ ┌───────────────────────────────────────────────┐   │ │
│ │ │ Domínios Atribuídos:                          │   │ │
│ │ │                                               │   │ │
│ │ │ ☑ zip.50g.io                                  │   │ │
│ │ │   ☑ View  ☐ Edit  ☐ Delete                   │   │ │
│ │ │                                               │   │ │
│ │ │ ☑ smarterhome.ai                              │   │ │
│ │ │   ☑ View  ☐ Edit  ☐ Delete                   │   │ │
│ │ │                                               │   │ │
│ │ │ ☐ ispfinder.net                               │   │ │
│ │ │                                               │   │ │
│ │ │ ☐ broadbandcheck.io                           │   │ │
│ │ └───────────────────────────────────────────────┘   │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│              [Salvar]  [Cancelar]                       │
└─────────────────────────────────────────────────────────┘
```

---

## 🚀 Ordem de Implementação Sugerida

### **FASE 1: Base** (3-4 horas)
1. ✅ Criar migration `role_domain_permissions`
2. ✅ Criar model `RoleDomainPermission`
3. ✅ Criar permissões `domain.access.all` e `domain.access.assigned`
4. ✅ Criar `DomainPermissionService`

### **FASE 2: Middleware** (2 horas)
5. ✅ Criar `CheckDomainAccess` middleware
6. ✅ Registrar middleware no `bootstrap/app.php`
7. ✅ Aplicar middleware nas rotas necessárias

### **FASE 3: API** (3 horas)
8. ✅ Implementar métodos no `RoleController`
9. ✅ Criar rotas para gerenciar permissões de domínio
10. ✅ Atualizar endpoints globais para respeitar permissões

### **FASE 4: Testes** (2 horas)
11. ✅ Criar testes Feature
12. ✅ Criar testes Unit
13. ✅ Criar seeder

### **FASE 5: Documentação** (1 hora)
14. ✅ Documentar API
15. ✅ Criar guia de uso

**Tempo Total Estimado: 11-12 horas**

---

## 📊 Benefícios

### **Segurança**
- ✅ Controle granular de acesso
- ✅ Separação de responsabilidades
- ✅ Auditoria de acessos

### **Escalabilidade**
- ✅ Suporta centenas de domínios
- ✅ Fácil adicionar/remover domínios
- ✅ Permissões flexíveis por ação

### **Usabilidade**
- ✅ Clientes veem apenas seus domínios
- ✅ Admins gerenciam subconjuntos
- ✅ Super admins têm visão completa

---

## 🎉 Conclusão

Este design fornece um **sistema robusto e escalável** de permissões por domínio, permitindo:

1. **Controle Granular:** Permissões específicas por domínio (view, edit, delete)
2. **Flexibilidade:** Acesso global ou restrito
3. **Escalabilidade:** Suporta crescimento ilimitado de domínios
4. **Segurança:** Middleware e service dedicados
5. **Manutenibilidade:** Código limpo e testável

**Pronto para implementação! 🚀**
