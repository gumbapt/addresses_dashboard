# 🗂️ Domain Groups - Sistema Simplificado (Tipo Google Tag Manager)

## 📋 Conceito

Sistema simples de organização de domínios em grupos hierárquicos, inspirado no Google Tag Manager:

✅ **2 Grupos Principais** (Production e Testing)  
✅ **Super Admin** gerencia tudo  
✅ **Perfis automáticos** por grupo  
✅ **Hierarquia simples** e clara  

---

## 🏗️ Estrutura

### **Grupo 1: Production**
- **Domínios:** zip.50g.io, fiberfinder.com
- **Dados:** Reais (sem modificação)
- **Profile:** volume_multiplier = 1.0

### **Grupo 2: Testing**
- **Domínios:** smarterhome.ai, ispfinder.net, broadbandcheck.io  
- **Dados:** Sintéticos (+50% volume, +2% success)
- **Profile:** volume_multiplier = 1.5

---

## 🎯 Como Funciona

### **1. Ao Popular Reports:**

```bash
php artisan reports:seed-all-domains --sync --limit=10
```

**O sistema:**
1. Lê o grupo do domínio
2. Aplica o profile do grupo automaticamente
3. Gera dados conforme o grupo

**Exemplo:**
```
🌐 Processando domínio: zip.50g.io
   Tipo: 📊 DADOS REAIS
   📁 Grupo: Production
   
🌐 Processando domínio: smarterhome.ai
   Tipo: 🎲 DADOS SINTÉTICOS
   📁 Grupo: Testing
```

---

## 📊 Profiles por Grupo

### **Production (dados reais):**
```php
[
    'volume_multiplier' => 1.0,  // Sem modificação
    'success_bias' => 0,
    'state_focus' => [],
    'provider_shuffle' => 0,
]
```

### **Testing (dados sintéticos):**
```php
[
    'volume_multiplier' => 1.5,  // +50% volume
    'success_bias' => 0.02,      // +2% success
    'state_focus' => ['CA', 'NY', 'TX', 'FL'],
    'provider_shuffle' => 0.5,   // 50% variação
]
```

---

## 🚀 Setup Completo

### **1. Criar Grupos e Domínios:**

```bash
# Rodar migrations
php artisan migrate

# Criar domínios
php artisan db:seed --class=DomainSeeder

# Criar grupos e associar
php artisan db:seed --class=DomainGroupSeeder
```

**Resultado:**
```
✅ Grupo criado: Production
✅ Grupo criado: Testing
   → zip.50g.io → Production
   → fiberfinder.com → Production
   → smarterhome.ai → Testing
   → ispfinder.net → Testing
   → broadbandcheck.io → Testing
```

---

### **2. Popular Reports:**

```bash
# Com grupos configurados
php artisan reports:seed-all-domains --sync --limit=10
```

**Agora usa os grupos automaticamente!**

---

## 🎨 Hierarquia (Tipo Google Tag Manager)

```
📁 Production (Grupo 1)
   ├── 🌐 zip.50g.io
   └── 🌐 fiberfinder.com
   
📁 Testing (Grupo 2)
   ├── 🌐 smarterhome.ai
   ├── 🌐 ispfinder.net
   └── 🌐 broadbandcheck.io
```

---

## 🔄 Ordem de Prioridade

Ao gerar dados sintéticos, o sistema usa esta ordem:

```
1. Profile do GRUPO (se domínio tiver grupo)
   ↓ (se não houver)
2. Profile do DOMÍNIO (hardcoded por nome)
   ↓ (se não houver)
3. Profile DEFAULT (sem modificação)
```

**Exemplo:**
- `zip.50g.io` no grupo "Production" → Usa profile do grupo Production ✅
- `smarterhome.ai` no grupo "Testing" → Usa profile do grupo Testing ✅
- `novoDominio.com` sem grupo → Usa profile default

---

## 📊 Vantagens

### **Antes (por nome de domínio):**
```php
// Tinha que adicionar profile manualmente para cada domínio
$profiles['novo-dominio.com'] = [...];
```

### **Agora (por grupo):**
```php
// Basta associar ao grupo!
$domain->update(['domain_group_id' => 1]);
// Automaticamente usa profile do grupo Production
```

---

## 🎯 Casos de Uso

### **1. Adicionar Novo Domínio de Produção:**

```bash
curl -X POST /api/admin/domains \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "domain_group_id": 1,
    "name": "newprod.com",
    "domain_url": "https://newprod.com"
  }'
```

**Resultado:** Automaticamente usa profile do grupo Production (dados reais)!

---

### **2. Adicionar Novo Domínio de Teste:**

```bash
curl -X POST /api/admin/domains \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "domain_group_id": 2,
    "name": "testdomain.com",
    "domain_url": "https://testdomain.com"
  }'
```

**Resultado:** Automaticamente usa profile do grupo Testing (dados sintéticos +50%)!

---

## 🔧 Customização

Se precisar de grupos adicionais no futuro:

```bash
# 1. Criar novo grupo
curl -X POST /api/admin/domain-groups \
  -d '{"name": "Development", "slug": "development"}'

# 2. Adicionar profile em SeedAllDomainsWithReports.php
$groupProfiles = [
    'production' => [...],
    'testing' => [...],
    'development' => [
        'volume_multiplier' => 0.5,  // 50% volume
        'success_bias' => -0.05,     // -5% success
    ],
];

# 3. Associar domínios
$domain->update(['domain_group_id' => 3]);
```

---

## ✅ Benefícios

✅ **Escalável** - Adicione domínios sem mudar código  
✅ **Organizado** - Hierarquia clara  
✅ **Automático** - Profile aplicado pelo grupo  
✅ **Simples** - Apenas 2 grupos principais  
✅ **Flexível** - Pode adicionar mais grupos facilmente  

---

## 🗄️ Estrutura no Banco

```sql
-- Grupos
ID | Name       | Slug       | Max Domains
1  | Production | production | NULL
2  | Testing    | testing    | NULL

-- Domínios
ID | Name              | Domain Group ID
1  | zip.50g.io        | 1  (Production)
2  | fiberfinder.com   | 1  (Production)
3  | smarterhome.ai    | 2  (Testing)
4  | ispfinder.net     | 2  (Testing)
5  | broadbandcheck.io | 2  (Testing)
```

---

## 🚀 Comandos Úteis

```bash
# Ver grupos e domínios
php artisan tinker --execute="
\$groups = App\Models\DomainGroup::with('domains')->get();
foreach (\$groups as \$group) {
    echo \$group->name . ':' . PHP_EOL;
    foreach (\$group->domains as \$d) {
        echo '  → ' . \$d->name . PHP_EOL;
    }
}
"

# Popular com grupos
php artisan reports:seed-all-domains --sync --limit=5

# Setup completo
./full-setup-with-reports.sh --quick
```

---

**Versão:** 2.0 (Simplificada)  
**Inspiração:** Google Tag Manager  
**Status:** ✅ Implementado

