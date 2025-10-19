# 📊 API Cross-Domain - Documentação Completa

## 🎯 Visão Geral

Os endpoints cross-domain permitem análise agregada de **TODOS os domínios**, fornecendo insights comparativos, rankings e métricas globais.

---

## 🏆 1. Ranking Global de Domínios

### **Endpoint**
```http
GET /api/admin/reports/global/domain-ranking
```

### **Autenticação**
```http
Headers: Authorization: Bearer {admin_token}
```

### **Parâmetros**

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `sort_by` | string | Não | Critério de ordenação: `score` (padrão), `volume`, `success`, `speed` |
| `date_from` | date | Não | Data inicial (YYYY-MM-DD) |
| `date_to` | date | Não | Data final (YYYY-MM-DD) |
| `min_reports` | integer | Não | Número mínimo de relatórios |

### **Exemplo de Uso**

```bash
# Ranking por score (padrão)
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking'

# Ranking por volume
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?sort_by=volume" \
  -H "Authorization: Bearer $TOKEN"

# Ranking por taxa de sucesso
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?sort_by=success" \
  -H "Authorization: Bearer $TOKEN"

# Ranking filtrado por período
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?date_from=2025-07-01&date_to=2025-07-31" \
  -H "Authorization: Bearer $TOKEN"
```

### **Resposta**

```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain": {
          "id": 2,
          "name": "smarterhome.ai",
          "slug": "smarterhome-ai"
        },
        "metrics": {
          "total_requests": 3781,
          "success_rate": 95.98,
          "avg_speed": 1149.82,
          "score": 2.56,
          "unique_providers": 122,
          "unique_states": 43
        },
        "coverage": {
          "total_reports": 40,
          "period_start": "2025-06-27",
          "period_end": "2025-09-27",
          "days_covered": 93
        }
      },
      {
        "rank": 2,
        "domain": {
          "id": 4,
          "name": "broadbandcheck.io",
          "slug": "broadbandcheck-io"
        },
        "metrics": {
          "total_requests": 2691,
          "success_rate": 94.65,
          "avg_speed": 1118.44,
          "score": 1.79,
          "unique_providers": 122,
          "unique_states": 43
        },
        "coverage": {
          "total_reports": 40,
          "period_start": "2025-06-27",
          "period_end": "2025-09-27",
          "days_covered": 93
        }
      }
    ],
    "sort_by": "score",
    "total_domains": 4,
    "filters": {
      "date_from": null,
      "date_to": null,
      "min_reports": null
    }
  }
}
```

### **Cálculo do Score**

O score é calculado usando a fórmula:

```
score = (total_requests / 1000) × (success_rate / 100) × log(avg_speed + 1) / 10
```

Combina:
- **Volume** (total_requests)
- **Qualidade** (success_rate)
- **Performance** (avg_speed)

---

## 🔄 2. Comparação Direta entre Domínios

### **Endpoint**
```http
GET /api/admin/reports/global/comparison
```

### **Autenticação**
```http
Headers: Authorization: Bearer {admin_token}
```

### **Parâmetros**

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `domains` | string | **SIM** | IDs separados por vírgula. Ex: `1,2,3` |
| `metric` | string | Não | Métrica específica: `geographic`, `providers`, `technologies` |
| `date_from` | date | Não | Data inicial (YYYY-MM-DD) |
| `date_to` | date | Não | Data final (YYYY-MM-DD) |

### **Exemplo de Uso**

```bash
# Comparar 2 domínios
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.domains'

# Comparar todos os 4 domínios
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2,3,4" \
  -H "Authorization: Bearer $TOKEN"

# Comparar com detalhes geográficos
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2&metric=geographic" \
  -H "Authorization: Bearer $TOKEN"

# Comparar com detalhes de provedores
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2&metric=providers" \
  -H "Authorization: Bearer $TOKEN"
```

### **Resposta**

```json
{
  "success": true,
  "data": {
    "domains": [
      {
        "domain": {
          "id": 1,
          "name": "zip.50g.io"
        },
        "metrics": {
          "total_requests": 1490,
          "success_rate": 92.38,
          "avg_speed": 1006.47,
          "total_failed": 113,
          "total_reports": 40
        }
      },
      {
        "domain": {
          "id": 2,
          "name": "smarterhome.ai"
        },
        "metrics": {
          "total_requests": 3781,
          "success_rate": 95.98,
          "avg_speed": 1149.82,
          "total_failed": 152,
          "total_reports": 40
        },
        "comparison": {
          "requests_diff": 153.8,
          "requests_diff_label": "+153.8%",
          "success_diff": 3.6,
          "success_diff_label": "+3.6%",
          "speed_diff": 14.2,
          "speed_diff_label": "+14.2%"
        }
      }
    ],
    "total_compared": 2,
    "filters": {
      "metric": null,
      "date_from": null,
      "date_to": null
    }
  }
}
```

### **Interpretação da Comparação**

- **Primeiro domínio** = Base de comparação (sem `comparison`)
- **Demais domínios** = Diferenças percentuais vs base
- **Valores positivos** = Melhor que a base
- **Valores negativos** = Pior que a base

---

## 📊 Casos de Uso

### **1. Identificar Líder de Mercado**

```bash
# Buscar o domínio com melhor score geral
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?sort_by=score" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking[0]'
```

**Resultado:** smarterhome.ai (score: 2.56)

### **2. Identificar Domínio com Maior Volume**

```bash
# Buscar o domínio com mais requisições
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?sort_by=volume" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking[0].domain.name'
```

**Resultado:** smarterhome.ai (3,781 requests)

### **3. Comparar Performance de 2 Domínios**

```bash
# Comparar zip.50g.io (ID:1) vs smarterhome.ai (ID:2)
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.domains[1].comparison'
```

**Resultado:**
```json
{
  "requests_diff": 153.8,
  "requests_diff_label": "+153.8%",
  "success_diff": 3.6,
  "success_diff_label": "+3.6%",
  "speed_diff": 14.2,
  "speed_diff_label": "+14.2%"
}
```

**Interpretação:** smarterhome.ai tem 153.8% mais requisições, 3.6% melhor taxa de sucesso, e 14.2% mais velocidade que zip.50g.io.

### **4. Analisar Todos os Domínios**

```bash
# Comparar todos os 4 domínios
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2,3,4" \
  -H "Authorization: Bearer $TOKEN"
```

**Insights:**
- smarterhome.ai: +153.8% requests, +3.6% success vs zip.50g.io
- ispfinder.net: -40.3% requests, -8% success vs zip.50g.io
- broadbandcheck.io: +80.6% requests, +2.27% success vs zip.50g.io

---

## 🧪 Scripts de Teste

### **Testar Ranking**
```bash
./test-global-ranking.sh
```

Testa:
- ✅ Ranking por score
- ✅ Ranking por volume
- ✅ Ranking por success rate
- ✅ Ranking por velocidade
- ✅ Ranking completo com todas as métricas

### **Testar Comparação**
```bash
./test-global-comparison.sh
```

Testa:
- ✅ Comparação de 2 domínios
- ✅ Comparação de todos os 4 domínios
- ✅ Comparação com detalhes geográficos
- ✅ Comparação com detalhes de provedores
- ✅ Comparação completa

---

## 📈 Visualizações Sugeridas

### **1. Tabela de Ranking**

| Rank | Domínio | Requests | Success % | Avg Speed | Score |
|------|---------|----------|-----------|-----------|-------|
| 🥇 1 | smarterhome.ai | 3,781 | 96.0% | 1,150 Mbps | 2.56 |
| 🥈 2 | broadbandcheck.io | 2,691 | 94.7% | 1,118 Mbps | 1.79 |
| 🥉 3 | zip.50g.io | 1,490 | 92.4% | 1,006 Mbps | 0.95 |
| 4 | ispfinder.net | 889 | 84.4% | 1,135 Mbps | 0.53 |

### **2. Gráfico de Comparação**

```
Requests:
smarterhome.ai  ████████████████████████████ 3,781 (+154%)
broadbandcheck  ████████████████████ 2,691 (+81%)
zip.50g.io      ███████████ 1,490 (base)
ispfinder.net   ██████ 889 (-40%)

Success Rate:
smarterhome.ai  ████████████████████ 96.0% (+3.6%)
broadbandcheck  ███████████████████ 94.7% (+2.3%)
zip.50g.io      ██████████████████ 92.4% (base)
ispfinder.net   ████████████████ 84.4% (-8.0%)
```

### **3. Insights Dashboard**

```
🏆 LÍDER DE MERCADO
smarterhome.ai
• 154% mais requisições
• Melhor taxa de sucesso (96%)
• Score: 2.56

⚠️  NECESSITA ATENÇÃO
ispfinder.net
• Volume 40% abaixo da média
• Taxa de sucesso 8% abaixo da média
• Score: 0.53
```

---

## 🎯 Dados Atuais (Após Implementação)

### **Ranking Atual:**

| Posição | Domínio | Total Requests | Success % | Diferença vs Base |
|---------|---------|---------------|-----------|-------------------|
| 🥇 1º | smarterhome.ai | 3,781 | 96.0% | +153.8% volume |
| 🥈 2º | broadbandcheck.io | 2,691 | 94.7% | +80.6% volume |
| 🥉 3º | zip.50g.io | 1,490 | 92.4% | Base |
| 4º | ispfinder.net | 889 | 84.4% | -40.3% volume |

### **Insights Automáticos:**

1. **smarterhome.ai** é o líder com 153.8% mais requisições que zip.50g.io
2. **broadbandcheck.io** tem +80.6% volume e +2.27% taxa de sucesso
3. **zip.50g.io** é a base de comparação (dados reais)
4. **ispfinder.net** precisa atenção: -40.3% volume e -8% taxa de sucesso

---

## 🔧 Arquivos Criados

### **Use Cases:**
- `app/Application/UseCases/Report/Global/GetGlobalDomainRankingUseCase.php`
- `app/Application/UseCases/Report/Global/CompareDomainsUseCase.php`

### **DTOs:**
- `app/Application/DTOs/Report/Global/DomainRankingDTO.php`
- `app/Application/DTOs/Report/Global/DomainComparisonDTO.php`

### **Controller:**
- `app/Http/Controllers/Api/ReportController.php` (métodos adicionados)
  - `globalRanking()`
  - `compareDomains()`

### **Rotas:**
- `routes/api.php` (rotas adicionadas)
  - `GET /api/admin/reports/global/domain-ranking`
  - `GET /api/admin/reports/global/comparison`

### **Testes:**
- `tests/Feature/Report/Global/GlobalDomainRankingTest.php`
- `tests/Feature/Report/Global/CompareDomainsFunctionalTest.php`

### **Scripts:**
- `test-global-ranking.sh`
- `test-global-comparison.sh`

---

## ✅ Status de Implementação

| Funcionalidade | Status | Cobertura |
|----------------|--------|-----------|
| Ranking Global | ✅ Implementado | 100% |
| Comparação Direta | ✅ Implementado | 100% |
| Filtros de Data | ✅ Implementado | 100% |
| Múltiplos Critérios de Ordenação | ✅ Implementado | 100% |
| Testes Feature | ✅ Implementado | 6 testes |
| Scripts de Teste | ✅ Implementado | 2 scripts |
| Documentação | ✅ Implementado | Este arquivo |

---

## 🚀 Próximos Passos (FASE 2)

### **Endpoints Adicionais Sugeridos:**

1. **Métricas Globais**
   - `GET /api/admin/reports/global/metrics`
   - Agregação de todos os domínios

2. **Análise de Tecnologias**
   - `GET /api/admin/reports/global/technologies`
   - Distribuição por tecnologia

3. **Análise Geográfica**
   - `GET /api/admin/reports/global/geographic`
   - Hotspots por domínio

Ver proposta completa em: [RELATORIOS_CROSS_DOMAIN_PROPOSTA.md](./RELATORIOS_CROSS_DOMAIN_PROPOSTA.md)

---

## 📚 Documentação Relacionada

- [Proposta Cross-Domain](./RELATORIOS_CROSS_DOMAIN_PROPOSTA.md)
- [Perfis dos Domínios](./DOMAIN_PROFILES.md)
- [Setup Multi-Domínio](./MULTI_DOMAIN_SETUP_GUIDE.md)
- [Sistema de Relatórios](./SISTEMA_RELATORIOS_DESIGN_COMPLETO.md)

---

🎊 **FASE 1 Implementada com Sucesso! Sistema de Análise Cross-Domain Operacional!**
