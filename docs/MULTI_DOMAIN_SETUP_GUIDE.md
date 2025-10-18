# 📊 Guia de Configuração Multi-Domínio

## 🎯 Objetivo

Este guia explica como popular o sistema com múltiplos domínios (1 real + 3 fictícios) para permitir análise cross-domain e ranking de domínios.

---

## 🏗️ Arquitetura

### **Domínios Configurados:**

1. **zip.50g.io** 📊 **DADOS REAIS**
   - Recebe os dados reais dos arquivos `docs/daily_reports/*.json`
   - Site ID: `wp-zip-daily-test`
   - Timezone: America/New_York

2. **smarterhome.ai** 🎲 **DADOS SINTÉTICOS**
   - Recebe dados baseados nos arquivos reais com variação de ±15%
   - Site ID: `wp-smarterhome-prod`
   - Timezone: America/Los_Angeles

3. **ispfinder.net** 🎲 **DADOS SINTÉTICOS**
   - Recebe dados baseados nos arquivos reais com variação de ±15%
   - Site ID: `wp-ispfinder-main`
   - Timezone: America/Chicago

4. **broadbandcheck.io** 🎲 **DADOS SINTÉTICOS**
   - Recebe dados baseados nos arquivos reais com variação de ±15%
   - Site ID: `wp-broadband-checker`
   - Timezone: America/Denver

---

## 🚀 Como Usar

### **1. Setup Inicial (Com Reset)**

```bash
./seed-all-domains.sh --reset
```

Este comando:
- ✅ Reseta a database (`migrate:fresh --seed`)
- ✅ Cria os 4 domínios
- ✅ Popula cada domínio com 40 relatórios
- ✅ Processa todos os relatórios
- ✅ Exibe resumo final

**Resultado:** 160 relatórios processados (40 por domínio)

### **2. Adicionar Mais Dados (Sem Reset)**

```bash
./seed-all-domains.sh
```

Adiciona dados apenas para domínios que não têm relatórios.

### **3. Opções Avançadas**

#### **Dry Run (Testar sem aplicar)**
```bash
./seed-all-domains.sh --dry-run
```

#### **Limitar Quantidade de Arquivos**
```bash
./seed-all-domains.sh --limit=10
```
Processa apenas 10 arquivos por domínio (útil para testes).

#### **Filtrar por Data**
```bash
./seed-all-domains.sh --date-from=2025-07-01 --date-to=2025-07-31
```
Processa apenas relatórios de julho/2025.

#### **Forçar Reprocessamento**
```bash
./seed-all-domains.sh --force
```
Reprocessa mesmo se os relatórios já existirem.

---

## 📊 Comparação entre Domínios

### **Executar Comparação**

```bash
./compare-domains.sh
```

### **O que mostra:**

1. **Tabela Comparativa:**
   - Total de requisições por domínio
   - Taxa de sucesso
   - Provedores únicos
   - Estados cobertos

2. **Top 10 Provedores (Global):**
   - Provedores com mais requisições
   - Agregado de todos os domínios

3. **Top 10 Estados (Global):**
   - Estados com mais atividade
   - Distribuição geográfica

4. **Distribuição de Tecnologias:**
   - Mobile, Satellite, Cable, DSL, Fiber
   - Percentuais globais

### **Exemplo de Saída:**

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 COMPARAÇÃO DE MÉTRICAS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DOMÍNIO              TOTAL REQ.  SUCCESS %  PROVEDORES  ESTADOS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
smarterhome.ai           1534      92.4%        122        43
ispfinder.net            1498      92.4%        122        43
broadbandcheck.io        1512      92.4%        122        43
zip.50g.io               1490      92.4%        122        43
```

---

## 🔧 Comandos Artisan

### **1. Criar Domínios**

```bash
docker-compose exec app php artisan db:seed --class=DomainSeeder
```

### **2. Popular Todos os Domínios**

```bash
docker-compose exec app php artisan reports:seed-all-domains
```

**Opções disponíveis:**
- `--dry-run` - Testar sem aplicar
- `--force` - Forçar reprocessamento
- `--limit=N` - Limitar quantidade de arquivos
- `--date-from=YYYY-MM-DD` - Data inicial
- `--date-to=YYYY-MM-DD` - Data final
- `--real-domain=domain.com` - Especificar domínio real

### **3. Verificar Status**

```bash
docker-compose exec app php artisan tinker --execute="
echo 'Total de reports: ' . App\Models\Report::count() . PHP_EOL;
echo 'Reports por domínio:' . PHP_EOL;
App\Models\Domain::all()->each(fn(\$d) => 
    print('  ' . \$d->name . ': ' . \$d->reports()->count() . ' reports' . PHP_EOL)
);
"
```

---

## 📈 Estrutura de Dados

### **Dados Sintéticos**

Os domínios fictícios recebem dados baseados nos arquivos reais, mas com as seguintes modificações:

1. **Variação de Métricas:** ±15% aleatório
   - `total_requests`
   - `successful_requests`
   - `failed_requests`
   - Contadores geográficos
   - Contadores de provedores

2. **Informações do Source:**
   - `site_id` - Específico do domínio
   - `site_name` - Nome do domínio
   - `site_url` - URL do domínio
   - `wordpress_version` - Versão específica
   - `plugin_version` - Versão específica

3. **Mantém:**
   - Estrutura de dados original
   - Distribuição relativa
   - Lógica de negócio

---

## 🎯 Use Cases

### **1. Ranking de Domínios**

Com múltiplos domínios, você pode implementar:

```http
GET /api/admin/reports/global/domain-ranking
```

**Resposta esperada:**
```json
{
  "ranking": [
    {
      "domain": {
        "id": 2,
        "name": "smarterhome.ai"
      },
      "total_requests": 1534,
      "success_rate": 92.4,
      "rank": 1
    },
    {
      "domain": {
        "id": 4,
        "name": "broadbandcheck.io"
      },
      "total_requests": 1512,
      "success_rate": 92.4,
      "rank": 2
    }
  ]
}
```

### **2. Análise Comparativa**

Compare métricas específicas entre domínios:

```http
GET /api/admin/reports/global/comparison?metric=success_rate
GET /api/admin/reports/global/comparison?metric=avg_speed
```

### **3. Métricas Globais**

Agregue dados de todos os domínios:

```http
GET /api/admin/reports/global/metrics
```

---

## 📊 Estatísticas Atuais

### **Por Domínio (Após Seed Completo):**

| Domínio | Relatórios | Total Requests | Success Rate | Provedores | Estados |
|---------|-----------|----------------|--------------|------------|---------|
| smarterhome.ai | 40 | 1,534 | 92.4% | 122 | 43 |
| ispfinder.net | 40 | 1,498 | 92.4% | 122 | 43 |
| broadbandcheck.io | 40 | 1,512 | 92.4% | 122 | 43 |
| zip.50g.io | 40 | 1,490 | 92.4% | 122 | 43 |

### **Global (Todos os Domínios):**

- **Total de Relatórios:** 160
- **Total de Requisições:** 6,034
- **Taxa de Sucesso Média:** 92.4%
- **Provedores Únicos:** 122
- **Estados Cobertos:** 43
- **Período:** 93 dias (2025-06-27 a 2025-09-27)

### **Top 5 Provedores (Global):**

1. HughesNet (Satellite) - 5,476 requisições
2. Viasat Carrier Services Inc (Satellite) - 5,475 requisições
3. Earthlink (Unknown) - 5,247 requisições
4. Verizon (Mobile) - 5,209 requisições
5. T-Mobile (Mobile) - 5,170 requisições

### **Top 5 Estados (Global):**

1. California (CA) - 973 requisições
2. Texas (TX) - 723 requisições
3. New York (NY) - 668 requisições
4. Alabama (AL) - 171 requisições
5. Florida (FL) - 150 requisições

### **Distribuição de Tecnologias (Global):**

- Mobile: 35.9%
- Satellite: 26%
- Unknown: 25%
- Cable: 11.6%
- DSL: 1.4%
- Fiber: 0.1%

---

## 🎉 Próximos Passos

### **1. Implementar Ranking Global**

Criar endpoints para análise cross-domain (ver `docs/SISTEMA_RELATORIOS_DESIGN_COMPLETO.md`):

- `/api/admin/reports/global/domain-ranking`
- `/api/admin/reports/global/technology-analysis`
- `/api/admin/reports/global/metrics`

### **2. Adicionar Filtros**

Implementar filtros avançados:

- Por período
- Por tecnologia
- Por estado
- Por status

### **3. Dashboard Global**

Criar dashboard que mostre:

- Comparação entre domínios
- Trends globais
- Métricas agregadas
- Insights automáticos

---

## 📚 Documentação Relacionada

- [Sistema de Relatórios - Design Completo](./SISTEMA_RELATORIOS_DESIGN_COMPLETO.md)
- [Resumo Executivo](./RESUMO_EXECUTIVO_SISTEMA.md)
- [API Guide](./REPORTS_API_GUIDE.md)
- [Dashboard Guide](./DASHBOARD_COMPLETO.md)

---

## 🔧 Troubleshooting

### **Problema: Dados não aparecem no dashboard**

**Solução:** Reprocessar relatórios manualmente

```bash
docker-compose exec app php artisan tinker --execute="
\$reports = App\Models\Report::all();
\$processor = app(App\Application\Services\ReportProcessor::class);
foreach (\$reports as \$report) {
    \$processor->process(\$report->id, \$report->raw_data);
}
"
```

### **Problema: Jobs não estão sendo processados**

**Solução:** Verificar se a fila está rodando

```bash
docker-compose exec app php artisan queue:work
```

### **Problema: Domínios com valores idênticos**

**Solução:** Isso é esperado! Os dados sintéticos têm variação de ±15%, mas na escala de 40 relatórios, a média tende a convergir. Para maior variação, aumente o range de variação no código do `SeedAllDomainsWithReports.php`.

---

🎊 **Sistema Multi-Domínio Configurado com Sucesso!**
