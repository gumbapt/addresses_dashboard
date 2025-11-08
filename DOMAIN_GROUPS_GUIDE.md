# 🗂️ Domain Groups - Guia Completo

## 📋 O Que É?

**Domain Groups** permite organizar domínios em grupos lógicos com:

✅ Controle de acesso apenas para **Super Admin**  
✅ Limite de domínios por grupo  
✅ Configurações personalizadas por grupo  
✅ Auditoria completa (quem criou/atualizou)  
✅ Soft deletes  

---

## 🏗️ Estrutura

### **Tabela: domain_groups**

```sql
- id (PK)
- name (único)
- slug (único, gerado automaticamente)
- description
- is_active
- settings (JSON)
- max_domains (nullable = ilimitado)
- created_by (FK → admins)
- updated_by (FK → admins)
- created_at, updated_at, deleted_at
```

### **Alteração na Tabela: domains**

```sql
- domain_group_id (FK → domain_groups, nullable)
```

---

## 🔐 Permissões

### **Apenas Super Admin pode:**

✅ Criar Domain Groups  
✅ Atualizar Domain Groups  
✅ Deletar Domain Groups  
✅ Criar Domains  
✅ Atualizar Domains  
✅ Deletar Domains  
✅ Regenerar API Keys  

### **Todos os Admins autenticados podem:**

✅ Listar Domain Groups (GET)  
✅ Ver detalhes de um Group (GET)  
✅ Listar Domains (GET)  
✅ Ver detalhes de um Domain (GET)  

---

## 🚀 Endpoints

### **Domain Groups**

```http
GET    /api/admin/domain-groups              # Listar (Super Admin)
GET    /api/admin/domain-groups/{id}         # Ver detalhes (Super Admin)
POST   /api/admin/domain-groups              # Criar (Super Admin)
PUT    /api/admin/domain-groups/{id}         # Atualizar (Super Admin)
DELETE /api/admin/domain-groups/{id}         # Deletar (Super Admin)
GET    /api/admin/domain-groups/{id}/domains # Listar domains do grupo (Super Admin)
```

### **Domains (movidos para Super Admin only)**

```http
GET    /api/admin/domains         # Listar (Todos)
GET    /api/admin/domains/{id}    # Ver detalhes (Todos)
POST   /api/admin/domains         # Criar (Super Admin)
PUT    /api/admin/domains/{id}    # Atualizar (Super Admin)
DELETE /api/admin/domains/{id}    # Deletar (Super Admin)
POST   /api/admin/domains/{id}/regenerate-api-key # Regenerar (Super Admin)
```

---

## 📊 Exemplos de Uso

### **1. Criar um Domain Group**

```bash
curl -X POST http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Premium Partners",
    "description": "Parceiros premium com recursos avançados",
    "max_domains": 20,
    "is_active": true,
    "settings": {
      "tier": "premium",
      "support": "priority"
    }
  }'
```

**Resposta:**
```json
{
  "success": true,
  "message": "Domain group created successfully.",
  "data": {
    "id": 1,
    "name": "Premium Partners",
    "slug": "premium-partners",
    "description": "Parceiros premium com recursos avançados",
    "is_active": true,
    "max_domains": 20,
    "settings": {
      "tier": "premium",
      "support": "priority"
    },
    "created_by": 1,
    "created_at": "2025-11-08T12:00:00.000000Z"
  }
}
```

---

### **2. Listar Domain Groups**

```bash
curl http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN"
```

**Com filtros:**
```bash
curl "http://localhost:8007/api/admin/domain-groups?search=premium&is_active=1&per_page=10" \
  -H "Authorization: Bearer $TOKEN"
```

---

### **3. Ver Detalhes de um Group**

```bash
curl http://localhost:8007/api/admin/domain-groups/1 \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Premium Partners",
    "slug": "premium-partners",
    "description": "Parceiros premium com recursos avançados",
    "is_active": true,
    "max_domains": 20,
    "domains_count": 3,
    "available_domains": 17,
    "has_reached_limit": false,
    "domains": [
      {
        "id": 1,
        "name": "partner1.com",
        "slug": "partner1-com",
        "domain_url": "https://partner1.com",
        "is_active": true
      }
    ],
    "created_by": {
      "id": 1,
      "name": "Super Admin",
      "email": "admin@dashboard.com"
    }
  }
}
```

---

### **4. Criar Domain em um Group**

```bash
curl -X POST http://localhost:8007/api/admin/domains \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "domain_group_id": 1,
    "name": "newpartner.com",
    "domain_url": "https://newpartner.com",
    "site_id": "wp-new-partner",
    "is_active": true
  }'
```

---

### **5. Atualizar Domain Group**

```bash
curl -X PUT http://localhost:8007/api/admin/domain-groups/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "max_domains": 30,
    "settings": {
      "tier": "premium_plus",
      "support": "priority",
      "custom_branding": true
    }
  }'
```

---

### **6. Deletar Domain Group**

```bash
curl -X DELETE http://localhost:8007/api/admin/domain-groups/1 \
  -H "Authorization: Bearer $TOKEN"
```

**⚠️ Não pode deletar se tiver domínios associados!**

**Resposta de erro:**
```json
{
  "success": false,
  "message": "Cannot delete domain group with associated domains. Please remove or reassign the domains first.",
  "domains_count": 3
}
```

---

## 🎯 Casos de Uso

### **1. Organização por Ambiente**

```
Production Domains (sem limite)
  └── zip.50g.io (dados reais)
  
Staging Domains (máx 10)
  └── smarterhome.ai
  └── ispfinder.net
  └── broadbandcheck.io

Development Domains (máx 5)
  └── test.local
  └── dev.local
```

---

### **2. Organização por Cliente/Tier**

```
Premium Partners (máx 20)
  └── Settings: { tier: "premium", support: "priority" }
  
Trial Domains (máx 3)
  └── Settings: { tier: "trial", trial_days: 30 }
  
Free Tier (máx 1)
  └── Settings: { tier: "free", limited_features: true }
```

---

### **3. Controle de Limite**

```php
$group = DomainGroup::find(1);

// Verificar se atingiu o limite
if ($group->hasReachedMaxDomains()) {
    return response()->json([
        'error' => 'Domain group has reached maximum domains limit'
    ], 400);
}

// Ver quantos domínios estão disponíveis
$available = $group->getAvailableDomainsCount();
// Retorna: 17 (se max=20 e tem 3)
// Retorna: null (se max=null = ilimitado)
```

---

## 🗄️ Migrations

### **Rodar as migrations:**

```bash
# Local (Docker)
docker-compose exec app php artisan migrate

# Servidor
php artisan migrate
```

### **Seed de exemplo:**

```bash
# Local (Docker)
docker-compose exec app php artisan db:seed --class=DomainGroupSeeder

# Servidor
php artisan db:seed --class=DomainGroupSeeder
```

---

## 🔒 Middleware

### **SuperAdminMiddleware**

Arquivo: `app/Http/Middleware/SuperAdminMiddleware.php`

**Verifica:**
1. ✅ Usuário está autenticado
2. ✅ Usuário é um Admin (não User)
3. ✅ Admin tem `is_super_admin = true`

**Resposta se não for Super Admin:**
```json
{
  "success": false,
  "message": "Access denied. Only Super Admins can perform this action.",
  "required_permission": "super_admin"
}
```

---

## 🧪 Testando

### **1. Como Super Admin:**

```bash
# Login como Super Admin
TOKEN=$(curl -s http://localhost:8007/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

# Criar Domain Group (deve funcionar)
curl -X POST http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Group","description":"Testing"}' | jq '.'
```

### **2. Como Admin Normal:**

```bash
# Login como Admin Normal
TOKEN=$(curl -s http://localhost:8007/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"normal.admin@example.com","password":"password"}' \
  | jq -r '.token')

# Tentar criar Domain Group (deve dar erro 403)
curl -X POST http://localhost:8007/api/admin/domain-groups \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Group"}' | jq '.'

# Resultado:
{
  "success": false,
  "message": "Access denied. Only Super Admins can perform this action.",
  "required_permission": "super_admin"
}
```

---

## 📚 Model Relationships

### **DomainGroup**

```php
// Um grupo tem muitos domínios
$group->domains; // Collection de Domain

// Criador do grupo
$group->creator; // Admin

// Quem atualizou por último
$group->updater; // Admin
```

### **Domain**

```php
// Um domínio pertence a um grupo
$domain->domainGroup; // DomainGroup ou null
```

---

## ✅ Checklist de Implementação

- [x] Migration `create_domain_groups_table`
- [x] Migration `add_domain_group_id_to_domains_table`
- [x] Model `DomainGroup` com relationships
- [x] Model `Domain` atualizado com relationship
- [x] Controller `DomainGroupController` completo
- [x] Middleware `SuperAdminMiddleware`
- [x] Rotas protegidas com middleware
- [x] Factory `DomainGroupFactory`
- [x] Seeder `DomainGroupSeeder`
- [x] Documentação completa

---

## 🚀 Próximos Passos

1. ✅ Rodar migrations
2. ✅ Rodar seeder
3. ✅ Testar endpoints como Super Admin
4. ⏳ Criar testes automatizados
5. ⏳ Adicionar validação de limite de domínios no DomainController
6. ⏳ Implementar frontend

---

**Criado em:** Novembro 8, 2025  
**Versão:** 1.0  
**Status:** ✅ Pronto para Uso

