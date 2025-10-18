# 📊 Sistema Multi-Domínio - Guia Rápido

## 🎉 Status Atual

✅ **Sistema configurado com 4 domínios:**

| Domínio | Tipo | Relatórios | Status |
|---------|------|-----------|--------|
| **zip.50g.io** | 📊 Dados Reais | 40 | ✅ Processados |
| **smarterhome.ai** | 🎲 Sintéticos | 40 | ✅ Processados |
| **ispfinder.net** | 🎲 Sintéticos | 40 | ✅ Processados |
| **broadbandcheck.io** | 🎲 Sintéticos | 40 | ✅ Processados |

**Total:** 160 relatórios processados

---

## 🚀 Comandos Rápidos

### **1. Comparar Domínios**
```bash
./compare-domains.sh
```
Mostra comparação de métricas, top provedores, top estados e distribuição de tecnologias.

### **2. Resetar e Popular Tudo**
```bash
./seed-all-domains.sh --reset
```
Reseta a database e popula todos os domínios com 40 relatórios cada.

### **3. Adicionar Mais Dados**
```bash
./seed-all-domains.sh
```
Adiciona dados apenas para domínios que ainda não têm relatórios.

### **4. Popular com Limite**
```bash
./seed-all-domains.sh --limit=10
```
Popula apenas 10 relatórios por domínio (útil para testes).

---

## 📊 Estatísticas Globais (Dados Divergentes)

### **Dados Atuais:**
- **Domínios Ativos:** 4
- **Total de Relatórios:** 160 (40 por domínio)
- **Período Coberto:** 93 dias (2025-06-27 a 2025-09-27)
- **Requisições Totais:** 8,884
- **Provedores Únicos:** 122
- **Estados Cobertos:** 43 estados

### **Comparação entre Domínios:**

| Domínio | Total Requests | Success Rate | Diferença vs zip.50g.io |
|---------|---------------|--------------|------------------------|
| **smarterhome.ai** | 3,729 | 96% | +150% volume, +3.6% taxa |
| **broadbandcheck.io** | 2,737 | 94.6% | +84% volume, +2.2% taxa |
| **zip.50g.io** (real) | 1,490 | 92.4% | Base |
| **ispfinder.net** | 928 | 84.4% | -38% volume, -8% taxa |

### **Top 5 Provedores (Global):**
1. Viasat Carrier Services Inc - 8,081 requisições
2. Verizon - 8,070 requisições
3. HughesNet - 7,812 requisições
4. Earthlink - 7,688 requisições
5. T-Mobile - 7,605 requisições

### **Top 5 Estados (Global):**
1. California (CA) - 2,029 requisições
2. Texas (TX) - 1,539 requisições
3. New York (NY) - 1,342 requisições
4. Florida (FL) - 169 requisições
5. Ohio (OH) - 154 requisições

### **Distribuição de Tecnologias:**
- Mobile: 36.1%
- Satellite: 25.5%
- Unknown: 25%
- Cable: 11.9%
- DSL: 1.5%
- Fiber: 0.1%

### **Perfis dos Domínios:**

- **smarterhome.ai:** Alto volume, alta qualidade, foco CA/NY/TX
- **broadbandcheck.io:** Médio-alto volume, boa qualidade, foco IL/OH/PA
- **zip.50g.io:** Dados reais, distribuição natural
- **ispfinder.net:** Baixo volume, qualidade regular, foco FL/GA/NC

---

## 🔧 Arquivos Criados

### **Seeders:**
- `database/seeders/DomainSeeder.php` - Cria os 4 domínios

### **Comandos Artisan:**
- `app/Console/Commands/SeedAllDomainsWithReports.php` - Popula todos os domínios

### **Scripts Bash:**
- `seed-all-domains.sh` - Seed completo automatizado
- `compare-domains.sh` - Comparação entre domínios

### **Documentação:**
- `docs/MULTI_DOMAIN_SETUP_GUIDE.md` - Guia completo e detalhado

---

## 🎯 Próximos Passos

### **Fase 1: Implementar Ranking Global** 🚧

Criar endpoints para análise cross-domain:

```http
GET /api/admin/reports/global/domain-ranking
GET /api/admin/reports/global/technology-analysis
GET /api/admin/reports/global/metrics
```

Ver detalhes em: `docs/SISTEMA_RELATORIOS_DESIGN_COMPLETO.md`

### **Fase 2: Dashboard Global** 🚧

Criar dashboard que mostre:
- Comparação entre domínios
- Ranking de domínios por métricas
- Trends globais
- Insights automáticos

### **Fase 3: Filtros Avançados** 🚧

Implementar filtros:
- Por período (date_from, date_to)
- Por tecnologia (Mobile, Satellite, Cable, etc.)
- Por estado
- Por status

---

## 📚 Documentação Completa

- **Guia Multi-Domínio:** [docs/MULTI_DOMAIN_SETUP_GUIDE.md](./docs/MULTI_DOMAIN_SETUP_GUIDE.md)
- **Design do Sistema:** [docs/SISTEMA_RELATORIOS_DESIGN_COMPLETO.md](./docs/SISTEMA_RELATORIOS_DESIGN_COMPLETO.md)
- **API Guide:** [docs/REPORTS_API_GUIDE.md](./docs/REPORTS_API_GUIDE.md)
- **Dashboard Guide:** [docs/DASHBOARD_COMPLETO.md](./docs/DASHBOARD_COMPLETO.md)

---

## 🧪 Exemplos de Uso

### **Ver Dashboard de um Domínio:**
```bash
TOKEN=$(curl -s http://localhost:8006/api/admin/login -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' | jq -r '.token')

curl -s "http://localhost:8006/api/admin/reports/domain/1/dashboard" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.kpis'
```

### **Ver Agregação de um Domínio:**
```bash
curl -s "http://localhost:8006/api/admin/reports/domain/1/aggregate" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.summary'
```

### **Comparar Todos os Domínios:**
```bash
./compare-domains.sh
```

---

## 🎊 Sistema Pronto para Análise Cross-Domain!

Agora você tem:
- ✅ 4 domínios configurados
- ✅ 160 relatórios processados
- ✅ Dados reais + sintéticos
- ✅ Scripts de comparação
- ✅ Documentação completa
- ✅ Pronto para implementar ranking global

**Próximo passo:** Implementar os endpoints de análise cross-domain conforme documentado em `docs/SISTEMA_RELATORIOS_DESIGN_COMPLETO.md` 🚀
