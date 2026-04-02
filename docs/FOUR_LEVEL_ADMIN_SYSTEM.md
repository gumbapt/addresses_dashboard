# 🔐 Sistema de 4 Níveis de Admin - Documentação Completa

## 📋 Visão Geral

Sistema hierárquico de administradores com controle granular de acesso baseado em **grupos de domínios** e **herança de permissões**.

---

## 🎯 Os 4 Níveis

### 1️⃣ **Sudo Admin** (Nível 1)
**Role:** `sudo-admin`

**Permissões:**
- ✅ Criar domínios (`domain-create`)
- ✅ Criar providers (`provider-create`)
- ✅ Criar grupos de domínios (`domain-group-create`)
- ✅ Atualizar/deletar domínios, providers e grupos
- ✅ Associar grupos de domínios a admins
- ✅ Criar e gerenciar todos os níveis de admin
- ✅ Acesso total ao sistema

**Grupos de Domínios:**
- Acesso a **TODOS** os grupos automaticamente

---

### 2️⃣ **Admin** (Nível 2)
**Role:** `admin`

**Permissões:**
- ❌ **NÃO pode** criar domínios
- ❌ **NÃO pode** criar providers
- ❌ **NÃO pode** criar grupos de domínios
- ✅ Visualizar domínios, providers e grupos
- ✅ **Adicionar usuários** aos domínios que tem acesso (`admin-assign-users`)
- ✅ Criar admins de nível 3 (Manager)
- ✅ Gerenciar dashboards dos seus grupos
- ✅ Visualizar e gerenciar relatórios

**Grupos de Domínios:**
- Recebe grupos atribuídos pelo **Sudo Admin**
- Pode atribuir seus grupos a admins de nível 3 (herança)
- Pode remover grupos de admins de nível 3
- **NÃO pode** dar grupos que não possui

---

### 3️⃣ **Manager** (Nível 3)
**Role:** `manager`

**Permissões:**
- ✅ Visualizar domínios, providers e grupos
- ✅ Ver dashboards de **todos os domínios** dos seus grupos
- ✅ Visualizar e gerenciar relatórios
- ❌ **NÃO pode** adicionar/remover usuários
- ❌ **NÃO pode** criar admins

**Grupos de Domínios:**
- Herda **automaticamente** os grupos do Admin (nível 2) que o criou
- Só tem acesso aos grupos que o Admin (nível 2) possui
- Admin (nível 2) pode remover grupos do Manager
- Admin (nível 2) **NÃO pode** dar grupos que não possui

---

### 4️⃣ **Viewer** (Nível 4)
**Role:** `viewer`

**Permissões:**
- ✅ Apenas **visualização** dos domínios atribuídos
- ✅ Ver dashboards dos domínios dos seus grupos
- ✅ Visualizar relatórios (read-only)
- ❌ Sem permissões de escrita/edição

**Grupos de Domínios:**
- Recebe grupos atribuídos pelo Admin (nível 2) ou Sudo Admin

---

## 🗄️ Estrutura de Banco de Dados

### **Tabela: `admin_domain_groups`** (NOVA)

```sql
CREATE TABLE admin_domain_groups (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT NOT NULL FK → admins(id),
    domain_group_id BIGINT NOT NULL FK → domain_groups(id),
    assigned_at DATETIME NOT NULL,
    assigned_by BIGINT NOT NULL FK → admins(id),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE(admin_id, domain_group_id)
);
```

### **Alteração: `admins`**

```sql
ALTER TABLE admins 
ADD COLUMN created_by BIGINT NULL FK → admins(id);
```

**Propósito:** Rastrear qual admin criou outro admin (hierarquia)

---

## 🔗 Relacionamentos

### **Admin ↔ DomainGroup (Many-to-Many)**

```
Admin ──< admin_domain_groups >── DomainGroup
```

- Um Admin pode ter acesso a vários DomainGroups
- Um DomainGroup pode ser acessado por vários Admins
- Campos do pivot: `assigned_at`, `assigned_by`, `is_active`

### **Hierarquia de Admins**

```
Admin (created_by) ──> Admin (filho)
```

- Admin pode criar outros admins
- Admin filho herda grupos do admin pai (quando aplicável)

---

## 🔧 Models Criados/Atualizados

### **1. AdminDomainGroup** (NOVO)
- Model Pivot para relacionamento Admin ↔ DomainGroup
- Métodos: `admin()`, `domainGroup()`, `assignedBy()`

### **2. Admin** (ATUALIZADO)
**Novos relacionamentos:**
- `domainGroups()` - Grupos atribuídos (apenas ativos)
- `allDomainGroups()` - Todos os grupos (incluindo inativos)
- `adminDomainGroups()` - Associações completas
- `createdAdmins()` - Admins criados por este admin
- `creator()` - Admin que criou este admin

**Novos métodos:**
- `getAccessibleDomainGroups()` - Lista grupos acessíveis
- `canAccessDomainGroup($groupId)` - Verifica acesso a grupo
- `getAssignableDomainGroups()` - Grupos que pode atribuir

### **3. DomainGroup** (ATUALIZADO)
**Novos relacionamentos:**
- `admins()` - Admins com acesso (apenas ativos)
- `allAdmins()` - Todos os admins (incluindo inativos)
- `adminDomainGroups()` - Associações completas

---

## 🛠️ Service: AdminDomainGroupService

### **Métodos Principais:**

#### `assignDomainGroupsToAdmin(Admin $admin, array $domainGroupIds, Admin $assignedBy)`
- Atribui grupos a um admin
- **Validação:** Só atribui grupos que o `$assignedBy` possui
- Retorna IDs dos grupos atribuídos

#### `removeDomainGroupsFromAdmin(Admin $admin, array $domainGroupIds, Admin $removedBy)`
- Remove grupos de um admin
- **Validação:** Só remove grupos que o `$removedBy` pode gerenciar
- Retorna IDs dos grupos removidos

#### `inheritDomainGroups(Admin $parentAdmin, Admin $childAdmin)`
- **Herança automática:** Quando Admin (nível 2) cria Manager (nível 3)
- Manager herda todos os grupos do Admin que o criou
- Retorna IDs dos grupos herdados

#### `getAccessibleDomainsFromGroups(Admin $admin)`
- Retorna todos os domínios acessíveis através dos grupos
- Sudo Admin: todos os domínios
- Outros: domínios dos grupos atribuídos

#### `canAssignDomainGroup(Admin $admin, int $domainGroupId)`
- Verifica se admin pode atribuir um grupo específico

---

## 📊 Fluxo de Herança

### **Cenário: Admin (nível 2) cria Manager (nível 3)**

1. **Sudo Admin** cria grupos: "Grupo A", "Grupo B", "Grupo C"
2. **Sudo Admin** atribui "Grupo A" e "Grupo B" ao **Admin (nível 2)**
3. **Admin (nível 2)** cria **Manager (nível 3)**
   - ✅ Manager **herda automaticamente** "Grupo A" e "Grupo B"
   - ❌ Manager **NÃO** tem "Grupo C" (Admin não possui)
4. **Admin (nível 2)** pode:
   - ✅ Remover "Grupo A" do Manager
   - ❌ **NÃO pode** dar "Grupo C" ao Manager (Admin não possui)

---

## 🎯 Regras de Negócio

### **1. Atribuição de Grupos**
- Sudo Admin pode atribuir qualquer grupo
- Admin (nível 2) só pode atribuir grupos que possui
- Não é possível atribuir grupos que não se possui

### **2. Herança Automática**
- Quando Admin (nível 2) cria Manager (nível 3), herança é automática
- Manager herda **apenas** os grupos do Admin que o criou
- Se Admin receber novos grupos depois, Manager **NÃO** herda automaticamente

### **3. Remoção de Grupos**
- Admin (nível 2) pode remover grupos do Manager
- Sudo Admin pode remover grupos de qualquer admin
- Remoção é "soft delete" (`is_active = false`)

### **4. Acesso a Dashboards**
- **Manager (nível 3):** Vê dashboards de **todos** os domínios dos seus grupos
- **Viewer (nível 4):** Vê dashboards apenas dos domínios atribuídos

---

## 📁 Arquivos Criados

1. ✅ `app/Models/AdminDomainGroup.php` - Model Pivot
2. ✅ `app/Domain/Services/AdminDomainGroupService.php` - Lógica de negócio
3. ✅ `database/seeders/FourLevelAdminRolesSeeder.php` - Roles e permissões
4. ✅ `database/migrations/2025_01_20_000000_create_admin_domain_groups_table.php`
5. ✅ `database/migrations/2025_01_20_000001_add_created_by_to_admins_table.php`

## 📝 Arquivos Atualizados

1. ✅ `app/Models/Admin.php` - Relacionamentos e métodos
2. ✅ `app/Models/DomainGroup.php` - Relacionamentos

---

## 🚀 Próximos Passos

1. Executar migrations: `php artisan migrate`
2. Executar seeder: `php artisan db:seed --class=FourLevelAdminRolesSeeder`
3. Implementar controllers para gerenciar associações admin-domain-group
4. Implementar lógica de herança automática ao criar admins
5. Atualizar middleware de autorização para verificar grupos

---

## 📌 Notas Importantes

- **Sudo Admin** sempre tem acesso a todos os grupos (não precisa de associação)
- Herança é **unidirecional**: Admin → Manager (não funciona ao contrário)
- Grupos removidos mantêm histórico (`is_active = false`)
- Um admin pode ter múltiplos grupos
- Um grupo pode ser atribuído a múltiplos admins







