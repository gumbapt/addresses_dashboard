# 🗂️ Domain Groups - Implementação Completa

## ✅ O Que Foi Implementado

Sistema completo de **Domain Groups** com controle de acesso **exclusivo para Super Admin**.

---

## 📋 Estrutura do Banco

### **Tabela: domain_groups**

```sql
CREATE TABLE domain_groups (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON,
    max_domains INT NULL,  -- NULL = ilimitado
    created_by BIGINT FK → admins,
    updated_by BIGINT FK → admins,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

### **Alteração: domains**

```sql
ALTER TABLE domains 
ADD COLUMN domain_group_id BIGINT NULL FK → domain_groups;
```

---

## 🔒 Controle de Acesso

### **Super Admin APENAS:**

✅ `POST /api/admin/domain-groups` - Criar grupo  
✅ `PUT /api/admin/domain-groups/{id}` - Atualizar grupo  
✅ `DELETE /api/admin/domain-groups/{id}` - Deletar grupo  
✅ `POST /api/admin/domains` - Criar domínio  
✅ `PUT /api/admin/domains/{id}` - Atualizar domínio  
✅ `DELETE /api/admin/domains/{id}` - Deletar domínio  
✅ `POST /api/admin/domains/{id}/regenerate-api-key` - Regenerar API Key  

### **Todos os Admins:**

✅ `GET /api/admin/domain-groups` - Listar grupos  
✅ `GET /api/admin/domain-groups/{id}` - Ver grupo  
✅ `GET /api/admin/domain-groups/{id}/domains` - Ver domínios do grupo  
✅ `GET /api/admin/domains` - Listar domínios  
✅ `GET /api/admin/domains/{id}` - Ver domínio  

---

## 📁 Arquivos Criados/Modificados

### **Models:**
- ✅ `app/Models/DomainGroup.php` - Model completo com relationships
- ✅ `app/Models/Domain.php` - Adicionado relationship com DomainGroup

### **Migrations:**
- ✅ `2025_11_08_120728_create_domain_groups_table.php`
- ✅ `2025_11_08_120811_add_domain_group_id_to_domains_table.php`

### **Controllers:**
- ✅ `app/Http/Controllers/Api/Admin/DomainGroupController.php` - CRUD completo
- ✅ `app/Http/Controllers/Api/Admin/DomainController.php` - Validação de limite

### **Middleware:**
- ✅ `app/Http/Middleware/SuperAdminMiddleware.php` - Valida is_super_admin
- ✅ `bootstrap/app.php` - Registrado como 'super.admin'

### **Repositories:**
- ✅ `app/Infrastructure/Repositories/DomainRepository.php` - Suporte a domain_group_id

### **Services:**
- ✅ `app/Domain/Services/DomainPermissionService.php` - Corrigido para Super Admin

### **Factories & Seeders:**
- ✅ `database/factories/DomainGroupFactory.php`
- ✅ `database/seeders/DomainGroupSeeder.php`

### **Routes:**
- ✅ `routes/api.php` - Rotas protegidas com middleware super.admin

### **Scripts:**
- ✅ `test-domain-groups.sh` - Script de teste completo
- ✅ `server-setup-with-reports.sh` - Setup para servidor
- ✅ `server-reprocess-reports.sh` - Reprocessar para servidor
- ✅ `server-seed-reports.sh` - Seed para servidor

### **Documentação:**
- ✅ `DOMAIN_GROUPS_GUIDE.md` - Guia completo
- ✅ `DOMAIN_GROUPS_IMPLEMENTATION.md` - Este arquivo
- ✅ `SERVER_SCRIPTS_GUIDE.md` - Guia de scripts servidor
- ✅ `SYNC_MODE_GUIDE.md` - Guia modo síncrono

---

## 🚀 Como Usar

### **1. Rodar Migrations:**

```bash
# Local (Docker)
docker-compose exec app php artisan migrate

# Servidor
php artisan migrate
```

### **2. Seed de Exemplo:**

```bash
# Local (Docker)
docker-compose exec app php artisan db:seed --class=DomainGroupSeeder

# Servidor
php artisan db:seed --class=DomainGroupSeeder
```

**Grupos criados:**
- Production Domains (ilimitado) → zip.50g.io
- Staging Domains (máx 10) → smarterhome.ai, ispfinder.net, broadbandcheck.io
- Development Domains (máx 5)
- Premium Partners (máx 20)
- Trial Domains (máx 3)

---

## 📊 Exemplos de API

### **Criar Domain Group (Super Admin):**

```bash
TOKEN="seu_token_super_admin"

curl -X POST http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Enterprise Clients",
    "description": "Clientes corporativos",
    "max_domains": 50,
    "settings": {
      "tier": "enterprise",
      "support": "24/7"
    }
  }'
```

---

### **Criar Domain em um Group (Super Admin):**

```bash
curl -X POST http://localhost:8007/api/admin/domains \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "domain_group_id": 1,
    "name": "newclient.com",
    "domain_url": "https://newclient.com",
    "site_id": "wp-newclient",
    "timezone": "America/New_York"
  }'
```

**Se o grupo atingiu o limite:**
```json
{
  "success": false,
  "message": "Domain group 'Trial Domains' has reached its maximum domains limit.",
  "max_domains": 3,
  "current_count": 3
}
```

---

### **Listar Groups (Qualquer Admin):**

```bash
curl http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN"
```

---

### **Ver Detalhes do Group:**

```bash
curl http://localhost:8007/api/admin/domain-groups/1 \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "name": "Production Domains",
    "domains_count": 1,
    "max_domains": null,
    "available_domains": null,
    "has_reached_limit": false,
    "domains": [
      {
        "id": 1,
        "name": "zip.50g.io"
      }
    ]
  }
}
```

---

### **Tentar Criar como Admin Normal:**

```bash
# Admin normal (não super admin)
TOKEN_NORMAL="token_de_admin_normal"

curl -X POST http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN_NORMAL" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test"}'
```

**Resposta:**
```json
{
  "success": false,
  "message": "Access denied. Only Super Admins can perform this action.",
  "required_permission": "super_admin"
}
```

---

## 🎯 Funcionalidades

### **1. Organização Lógica**
Agrupe domínios por:
- Ambiente (Production, Staging, Dev)
- Cliente (Partner A, Partner B)
- Tier (Free, Premium, Enterprise)
- Região (US, EU, APAC)

### **2. Limite de Domínios**
- Controle quantos domínios cada grupo pode ter
- `max_domains = null` → Ilimitado
- `max_domains = 10` → Máximo 10 domínios

### **3. Configurações Personalizadas**
Armazene configs específicas por grupo:
```json
{
  "tier": "premium",
  "support": "24/7",
  "sla": "99.9%",
  "features": ["advanced_analytics", "custom_reports"]
}
```

### **4. Auditoria Completa**
- `created_by` - Quem criou
- `updated_by` - Quem atualizou
- `deleted_at` - Soft delete

### **5. Validações Automáticas**
- ✅ Slug gerado automaticamente
- ✅ Validação de limite ao criar domain
- ✅ Não permite deletar grupo com domínios

---

## 🧪 Testar

### **Script Automático:**

```bash
# Local (Docker)
./test-domain-groups.sh

# Servidor
bash test-domain-groups.sh
```

**O script testa:**
1. ✅ Login como Super Admin
2. ✅ Listar grupos
3. ✅ Ver detalhes de um grupo
4. ✅ Criar novo grupo
5. ✅ Atualizar grupo
6. ✅ Tentar deletar grupo com domínios (deve falhar)
7. ✅ Deletar grupo vazio (deve funcionar)

---

## 🔧 Validações Implementadas

### **Criar Domain Group:**
- ✅ `name` obrigatório, único
- ✅ `slug` único (gerado auto)
- ✅ `max_domains` deve ser >= 1 ou null
- ✅ `settings` deve ser JSON válido

### **Criar Domain:**
- ✅ `domain_group_id` deve existir
- ✅ Verifica se grupo atingiu limite
- ✅ Atualiza `domain_group_id` após criação

### **Deletar Domain Group:**
- ✅ Verifica se tem domínios associados
- ✅ Retorna erro 400 se tiver domínios
- ✅ Soft delete

---

## 📊 Estrutura de Dados

### **Domain Group:**

```php
[
    'id' => 1,
    'name' => 'Production Domains',
    'slug' => 'production-domains',
    'description' => 'Domínios de produção ativos',
    'is_active' => true,
    'max_domains' => null, // ilimitado
    'settings' => [
        'environment' => 'production',
        'monitoring' => true,
    ],
    'domains_count' => 1,
    'available_domains' => null, // ilimitado
    'has_reached_limit' => false,
    'domains' => [...],
    'created_by' => [...],
    'created_at' => '2025-11-08T12:00:00Z',
]
```

---

## 🎯 Casos de Uso

### **Caso 1: Limitar Domínios de Trial**

```bash
# Criar grupo Trial com limite de 3 domínios
curl -X POST /api/admin/domain-groups \
  -d '{
    "name": "Trial Users",
    "max_domains": 3,
    "settings": {"trial_days": 30}
  }'

# Tentar adicionar 4º domínio
curl -X POST /api/admin/domains \
  -d '{"domain_group_id": 5, "name": "fourth.com"}'

# Erro: Domain group has reached maximum
```

### **Caso 2: Organizar por Ambiente**

```bash
# Production (sem limite)
Production Domains
  └── zip.50g.io (dados reais)

# Staging (máx 10)
Staging Domains
  └── smarterhome.ai
  └── ispfinder.net
  └── broadbandcheck.io

# Development (máx 5)
Development Domains
  └── dev1.local
  └── dev2.local
```

### **Caso 3: Grupos por Cliente**

```bash
# Cliente A (máx 5 domínios)
Client A Domains
  └── clienta-main.com
  └── clienta-api.com
  └── clienta-docs.com

# Cliente B (máx 10 domínios)
Client B Domains
  └── clientb.com
```

---

## 🔐 Segurança

### **Middleware SuperAdminMiddleware:**

```php
if (!$user->is_super_admin) {
    return response()->json([
        'message' => 'Access denied. Only Super Admins can perform this action.'
    ], 403);
}
```

**Aplicado em:**
- Todos os métodos POST/PUT/DELETE de Domain Groups
- Todos os métodos POST/PUT/DELETE de Domains

---

## 📚 Métodos Úteis

### **Model DomainGroup:**

```php
$group = DomainGroup::find(1);

// Verificar limite
$group->hasReachedMaxDomains(); // bool

// Ver disponibilidade
$group->getAvailableDomainsCount(); // int ou null

// Relacionamentos
$group->domains; // Collection de Domain
$group->creator; // Admin que criou
$group->updater; // Admin que atualizou por último

// Scopes
DomainGroup::active()->get(); // Apenas ativos
DomainGroup::withDomains()->get(); // Apenas com domínios
```

### **Model Domain:**

```php
$domain = Domain::find(1);

// Relacionamento
$domain->domainGroup; // DomainGroup ou null
```

---

## 🧪 Testes Completos

### **Executar:**

```bash
# Local (Docker)
./test-domain-groups.sh

# Servidor
bash test-domain-groups.sh
```

### **Saída Esperada:**

```
╔════════════════════════════════════════════════════════════════╗
║  🧪 TESTE DE DOMAIN GROUPS                                     ║
╚════════════════════════════════════════════════════════════════╝

━━━ 1. Login como Super Admin ━━━
✅ Login realizado com sucesso!

━━━ 2. Listar Domain Groups ━━━
• Production Domains (ID: 1) - 1 domínios / ∞ máx
• Staging Domains (ID: 2) - 3 domínios / 10 máx
• Development Domains (ID: 3) - 0 domínios / 5 máx
• Premium Partners (ID: 4) - 0 domínios / 20 máx
• Trial Domains (ID: 5) - 0 domínios / 3 máx

━━━ 3. Ver Detalhes do Grupo 'Production Domains' ━━━
{
  "name": "Production Domains",
  "domains_count": 1,
  "max_domains": null,
  "available": null,
  "has_reached_limit": false,
  "domains": ["zip.50g.io"]
}

━━━ 4. Criar Novo Domain Group ━━━
{
  "success": true,
  "message": "Domain group created successfully.",
  "group_id": 6,
  "group_name": "API Testing Group"
}

━━━ 5. Atualizar Domain Group ━━━
{
  "success": true,
  "message": "Domain group updated successfully.",
  "max_domains": 15
}

━━━ 6. Tentar Deletar Grupo com Domínios (deve falhar) ━━━
{
  "success": false,
  "message": "Cannot delete domain group with associated domains...",
  "domains_count": 3
}

━━━ 7. Deletar Grupo Vazio (deve funcionar) ━━━
{
  "success": true,
  "message": "Domain group deleted successfully."
}

╔════════════════════════════════════════════════════════════════╗
║  ✅ TESTES CONCLUÍDOS COM SUCESSO!                             ║
╚════════════════════════════════════════════════════════════════╝
```

---

## 🎨 Casos de Uso Reais

### **1. SaaS Multi-Tenant:**

```
Enterprise Plan (ilimitado)
  └── bigcorp.com
  └── megacorp.com

Premium Plan (máx 20)
  └── startup1.com
  └── startup2.com

Free Plan (máx 1)
  └── freeuser.com
```

### **2. Agência:**

```
Cliente A (máx 5)
  └── clienta-site.com
  └── clienta-blog.com

Cliente B (máx 3)
  └── clientb-main.com

Internal (ilimitado)
  └── agency-internal.com
  └── agency-tools.com
```

### **3. Ambientes:**

```
Production (ilimitado)
  └── app.myservice.com
  └── api.myservice.com

Staging (máx 5)
  └── staging.myservice.com
  └── qa.myservice.com

Development (máx 10)
  └── dev1.local
  └── dev2.local
```

---

## ⚙️ Configurações Personalizadas

### **Exemplo: Trial com Expiração**

```json
{
  "tier": "trial",
  "trial_days": 30,
  "trial_started_at": "2025-11-08",
  "trial_expires_at": "2025-12-08",
  "features_enabled": ["basic_reports", "email_support"]
}
```

### **Exemplo: Premium com SLA**

```json
{
  "tier": "premium",
  "support": "priority",
  "sla": "99.9%",
  "custom_branding": true,
  "dedicated_support": true,
  "max_api_calls_per_day": 100000
}
```

---

## 🔄 Fluxo Completo

### **Setup Inicial:**

```bash
# 1. Migrations
php artisan migrate

# 2. Seed de grupos
php artisan db:seed --class=DomainGroupSeeder

# 3. Verificar
php artisan tinker --execute="
echo 'Domain Groups: ' . App\Models\DomainGroup::count() . PHP_EOL;
echo 'Domains with groups: ' . App\Models\Domain::whereNotNull('domain_group_id')->count() . PHP_EOL;
"
```

### **Adicionar Novo Domínio:**

```bash
# Via API
curl -X POST /api/admin/domains \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "domain_group_id": 2,
    "name": "newdomain.com",
    "domain_url": "https://newdomain.com"
  }'
```

### **Mover Domain para Outro Group:**

```bash
curl -X PUT /api/admin/domains/5 \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "domain_group_id": 3
  }'
```

---

## 📈 Estatísticas

### **Ver Distribuição:**

```bash
php artisan tinker --execute="
\$groups = App\Models\DomainGroup::with('domains')->get();
foreach (\$groups as \$group) {
    \$count = \$group->domains->count();
    \$max = \$group->max_domains ?? '∞';
    echo \$group->name . ': ' . \$count . '/' . \$max . PHP_EOL;
}
"
```

**Output:**
```
Production Domains: 1/∞
Staging Domains: 3/10
Development Domains: 0/5
Premium Partners: 0/20
Trial Domains: 0/3
```

---

## ✅ Checklist Final

- [x] Migrations criadas e rodadas
- [x] Models com relationships
- [x] SuperAdminMiddleware implementado
- [x] DomainGroupController completo
- [x] Rotas protegidas
- [x] Validação de limite
- [x] Factory e Seeder
- [x] Scripts de teste
- [x] Scripts para servidor
- [x] Documentação completa
- [ ] Testes automatizados (PHPUnit)
- [ ] Frontend para gerenciar grupos

---

## 🚀 Próximos Passos

1. ✅ Criar testes automatizados
2. ✅ Implementar UI no frontend
3. ✅ Adicionar relatórios por grupo
4. ✅ Dashboard de grupos
5. ✅ Métricas agregadas por grupo

---

**Implementado em:** Novembro 8, 2025  
**Versão:** 1.0  
**Status:** ✅ Completo e Testado  
**Autor:** Pedro Nave

