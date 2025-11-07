# 🚀 Setup Completo - Após migrate:fresh --seed

## 📋 Guia Passo a Passo

Este guia explica **exatamente** o que fazer após executar `php artisan migrate:fresh --seed` para ter:

✅ Relatórios reais do **zip.50g.io**  
✅ Relatórios fictícios dos outros domínios  
✅ Permissões corretas para o admin  

---

## 🎯 Método 1: Script Automático (Recomendado)

### **Execute:**

```bash
./setup-after-fresh-seed.sh
```

O script faz tudo automaticamente:
1. ✅ Verifica estado atual
2. ✅ Cria domínios
3. ✅ Configura permissões do admin
4. ✅ Popula relatórios (real + fictícios)
5. ✅ Aguarda processamento
6. ✅ Mostra resumo final

---

## ⚙️ Método 2: Manual (Passo a Passo)

### **Passo 1: Verificar Estado Atual**

```bash
docker-compose exec app php artisan tinker --execute="
    \$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
    echo 'Admin: ' . (\$admin ? '✅' : '❌') . PHP_EOL;
    echo 'Super Admin: ' . (\$admin->is_super_admin ? 'SIM' : 'NÃO') . PHP_EOL;
    echo 'Domínios: ' . App\Models\Domain::count() . PHP_EOL;
    echo 'Reports: ' . App\Models\Report::count() . PHP_EOL;
"
```

**Resultado esperado:**
- ✅ Admin existe
- ✅ É Super Admin
- ✅ Tem domínios criados
- ❌ Não tem reports (ainda)

---

### **Passo 2: Criar Domínios (se necessário)**

```bash
docker-compose exec app php artisan db:seed --class=DomainSeeder
```

**Domínios criados:**
- `zip.50g.io` (REAL - vai receber dados reais)
- `smarterhome.ai` (FICTÍCIO)
- `ispfinder.net` (FICTÍCIO)
- `broadbandcheck.io` (FICTÍCIO)

---

### **Passo 3: Verificar Permissões do Admin**

O admin `admin@dashboard.com` é **Super Admin**, então tem acesso automático a **todos os domínios**.

**Verificar:**

```bash
docker-compose exec app php artisan tinker --execute="
    \$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
    \$domains = \$admin->getAccessibleDomains();
    echo 'Domínios acessíveis: ' . count(\$domains) . PHP_EOL;
    foreach (\$domains as \$d) {
        echo '  • ' . \$d->name . PHP_EOL;
    }
"
```

**Resultado esperado:**
```
Domínios acessíveis: 4
  • zip.50g.io
  • smarterhome.ai
  • ispfinder.net
  • broadbandcheck.io
```

---

### **Passo 4: Popular Relatórios**

Agora vem a parte principal! Você tem 3 opções:

#### **Opção A: Teste Rápido (5 arquivos por domínio)**

```bash
docker-compose exec app php artisan reports:seed-all-domains --limit=5
```

⏱️ **Tempo:** ~30 segundos  
📊 **Resultado:** 20 reports (5 × 4 domínios)

---

#### **Opção B: Período Específico (ex: junho 2025)**

```bash
docker-compose exec app php artisan reports:seed-all-domains \
  --date-from=2025-06-27 \
  --date-to=2025-06-30
```

⏱️ **Tempo:** ~1 minuto  
📊 **Resultado:** 16 reports (4 dias × 4 domínios)

---

#### **Opção C: Completo (TODOS os arquivos)**

```bash
docker-compose exec app php artisan reports:seed-all-domains
```

⏱️ **Tempo:** ~3-5 minutos  
📊 **Resultado:** 160 reports (40 arquivos × 4 domínios)

---

### **Passo 5: Aguardar Processamento**

Os reports são processados em **background** pelos jobs. Aguarde alguns segundos:

```bash
# Verificar progresso
watch -n 2 'docker-compose exec -T app php artisan tinker --execute="
    \$total = App\Models\Report::count();
    \$processed = App\Models\Report::where(\"status\", \"processed\")->count();
    echo \"Processados: \$processed / \$total\";
"'
```

Pressione `Ctrl+C` quando chegar a 100%.

---

### **Passo 6: Verificar Resultado Final**

```bash
docker-compose exec app php artisan tinker --execute="
    echo '═══════════════════════════════════════════════════════' . PHP_EOL;
    echo '📊 RESUMO FINAL' . PHP_EOL;
    echo '═══════════════════════════════════════════════════════' . PHP_EOL;
    echo PHP_EOL;
    
    \$domains = App\Models\Domain::where('is_active', true)->get();
    foreach (\$domains as \$domain) {
        \$count = \$domain->reports()->count();
        \$processed = \$domain->reports()->where('status', 'processed')->count();
        \$badge = \$domain->name === 'zip.50g.io' ? '📊 REAL' : '🎲 FICTÍCIO';
        echo \$domain->name . ' - ' . \$count . ' reports (' . \$processed . ' processados) ' . \$badge . PHP_EOL;
    }
    echo PHP_EOL;
    
    echo 'Total: ' . App\Models\Report::count() . ' reports' . PHP_EOL;
    echo 'Estados: ' . App\Models\State::count() . PHP_EOL;
    echo 'Cidades: ' . App\Models\City::count() . PHP_EOL;
    echo 'Provedores: ' . App\Models\Provider::count() . PHP_EOL;
"
```

---

## 🎯 Diferenças entre Dados Reais e Fictícios

### **zip.50g.io (REAL)**
- Usa dados **exatos** dos arquivos JSON
- Representa tráfego real do WordPress
- Volume médio: ~1,490 requisições/dia
- Taxa de sucesso: ~92.4%

### **smarterhome.ai (FICTÍCIO)**
- Dados **modificados** sinteticamente
- Volume: **2.5x maior** que zip.50g.io (~3,700 requisições)
- Taxa de sucesso: **+5%** (~96%)
- Foco geográfico: CA, NY, TX
- Preferência: Fiber

### **ispfinder.net (FICTÍCIO)**
- Volume: **0.6x menor** (~900 requisições)
- Taxa de sucesso: **-8%** (~84%)
- Foco geográfico: FL, GA, NC
- Preferência: Mobile

### **broadbandcheck.io (FICTÍCIO)**
- Volume: **1.8x maior** (~2,700 requisições)
- Taxa de sucesso: **+3%** (~95%)
- Foco geográfico: IL, OH, PA
- Preferência: Cable

---

## 🔐 Testando com a API

### **1. Fazer Login**

```bash
TOKEN=$(curl -s http://localhost:8007/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

echo "Token: $TOKEN"
```

---

### **2. Listar Domínios Acessíveis**

```bash
curl -s http://localhost:8007/api/admin/domains \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.data[] | {id, name, total_reports: .reports_count}'
```

**Resultado esperado:**
```json
{
  "id": 1,
  "name": "zip.50g.io",
  "total_reports": 40
}
{
  "id": 2,
  "name": "smarterhome.ai",
  "total_reports": 40
}
...
```

---

### **3. Ver Dashboard do zip.50g.io (dados reais)**

```bash
curl -s http://localhost:8007/api/admin/reports/domain/1/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.data.kpis'
```

**Resultado esperado:**
```json
{
  "total_requests": 59760,
  "success_rate": 92.4,
  "unique_providers": 122,
  "unique_states": 43,
  "avg_speed_mbps": 1502.89
}
```

---

### **4. Ver Ranking Global (todos os domínios)**

```bash
curl -s http://localhost:8007/api/admin/reports/global/domain-ranking \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.data[] | {rank, domain: .domain.name, requests: .total_requests, success_rate, score}'
```

**Resultado esperado:**
```json
{
  "rank": 1,
  "domain": "smarterhome.ai",
  "requests": 149400,
  "success_rate": 96.0,
  "score": 1.5
}
{
  "rank": 2,
  "domain": "broadbandcheck.io",
  "requests": 107892,
  "success_rate": 94.6,
  "score": 1.2
}
{
  "rank": 3,
  "domain": "zip.50g.io",
  "requests": 59760,
  "success_rate": 92.4,
  "score": 1.0
}
{
  "rank": 4,
  "domain": "ispfinder.net",
  "requests": 35856,
  "success_rate": 84.4,
  "score": 0.6
}
```

---

### **5. Comparar zip.50g.io vs smarterhome.ai**

```bash
curl -s "http://localhost:8007/api/admin/reports/global/comparison?domain_ids[]=1&domain_ids[]=2" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.data.comparison'
```

**Resultado esperado:**
```json
{
  "smarterhome.ai": {
    "vs_zip.50g.io": {
      "requests_diff_percent": 150.0,
      "success_rate_diff_percent": 3.6,
      "speed_diff_percent": 14.2
    }
  }
}
```

---

## 📊 Estrutura dos Dados Gerados

### **Por Domínio:**
- ✅ Reports diários processados
- ✅ Dados agregados (summary)
- ✅ Estados mapeados
- ✅ Cidades mapeadas
- ✅ CEPs mapeados
- ✅ Provedores com tecnologias

### **Global:**
- ✅ Ranking de domínios
- ✅ Comparação cross-domain
- ✅ Métricas agregadas
- ✅ Tendências temporais

---

## ⚠️ Troubleshooting

### **Problema: Admin não tem acesso aos domínios**

```bash
# Verificar se é Super Admin
docker-compose exec app php artisan tinker --execute="
    \$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
    echo 'Super Admin: ' . (\$admin->is_super_admin ? 'SIM' : 'NÃO') . PHP_EOL;
"

# Se não for, tornar Super Admin
docker-compose exec app php artisan tinker --execute="
    \$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
    \$admin->is_super_admin = true;
    \$admin->save();
    echo '✅ Admin agora é Super Admin!' . PHP_EOL;
"
```

---

### **Problema: Reports não estão sendo processados**

```bash
# Verificar queue workers
docker-compose ps queue_messages queue_events

# Reiniciar workers
docker-compose restart queue_messages queue_events

# Processar manualmente
docker-compose exec app php artisan queue:work --stop-when-empty
```

---

### **Problema: Arquivos JSON não encontrados**

```bash
# Verificar se existem
ls -la docs/daily_reports/*.json | wc -l

# Deve retornar: 40

# Se não houver arquivos, você precisa obtê-los do servidor de produção
```

---

## 🎉 Conclusão

Após seguir este guia, você terá:

✅ **4 domínios ativos** (1 real + 3 fictícios)  
✅ **160 reports processados** (40 arquivos × 4 domínios)  
✅ **Admin com acesso total** a todos os domínios  
✅ **Dados realistas** para comparação cross-domain  
✅ **Sistema pronto** para uso em produção  

---

## 📚 Próximos Passos

1. **Testar endpoints** com Postman ou curl
2. **Implementar frontend** usando a API
3. **Configurar alertas** para anomalias
4. **Adicionar mais domínios** conforme necessário
5. **Automatizar coleta** de dados do WordPress

---

**Criado em:** Novembro 7, 2025  
**Versão:** 1.0  
**Status:** ✅ Pronto para Uso

