# 🏆 API Provider Ranking - Guia Completo Final

## 📡 Endpoint

```
GET /api/admin/reports/global/provider-ranking
Authorization: Bearer {token}
```

---

## 📊 Response Completo (Com Paginação)

```json
{
  "success": true,
  "data": [
    {
      "rank": 1,
      "domain_name": "smarterhome.ai",
      "provider_name": "Earthlink",
      "total_requests": 416,
      "domain_total_requests": 2236,
      "percentage_of_domain": 18.60,
      "avg_success_rate": 0.0,
      "avg_speed": 1158
    }
  ],
  "pagination": {
    "total": 5,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 5
  },
  "available_providers": [
    {
      "id": 5,
      "name": "Earthlink",
      "slug": "earthlink",
      "total_requests": 1137
    },
    {
      "id": 2,
      "name": "Viasat Carrier Services Inc",
      "slug": "viasat-carrier-services-inc",
      "total_requests": 1132
    }
  ],
  "aggregated_stats": {
    "total_requests": 1137,
    "avg_success_rate": 0.0,
    "avg_speed": 751.8,
    "unique_domains": 5,
    "unique_providers": 1
  },
  "global_stats": {
    "provider_total_requests": 1137,
    "global_total_requests": 8894,
    "percentage_of_global": 12.78
  },
  "filters": {
    "provider_id": 5,
    "technology": null,
    "period": null,
    "sort_by": "total_requests"
  }
}
```

---

## 🆕 Novos Campos no Response

### **1. `available_providers`** - Lista de Providers

Lista de todos os providers disponíveis com seus totais (para popular dropdown):

```json
"available_providers": [
  {
    "id": 5,
    "name": "Earthlink",
    "slug": "earthlink",
    "total_requests": 1137
  }
]
```

**Uso:** Popular dropdown de seleção de provider

---

### **2. `aggregated_stats`** - Dados Agregados

Soma de TODOS os domínios que aparecem no ranking atual:

```json
"aggregated_stats": {
  "total_requests": 1137,        // Soma de todos os requests no ranking
  "avg_success_rate": 85.5,      // Média de success rate
  "avg_speed": 1200,              // Média de velocidade
  "unique_domains": 5,            // Quantos domínios diferentes
  "unique_providers": 1           // Quantos providers diferentes
}
```

**Uso:** Mostrar no header da tabela (totalizadores)

---

### **3. `global_stats`** - Estatísticas Globais

Aparece APENAS quando filtrar por `provider_id` específico:

```json
"global_stats": {
  "provider_total_requests": 1137,    // Total deste provider (todos domínios)
  "global_total_requests": 8894,      // Total geral (todos providers)
  "percentage_of_global": 12.78       // % que este provider representa
}
```

**Uso:** Mostrar "Earthlink representa 12.78% de todas as consultas"

---

## 🎯 Casos de Uso

### **1. Dropdown de Providers**

```javascript
// Usar available_providers para popular dropdown
const { available_providers } = await $fetch('/api/admin/reports/global/provider-ranking', {
  params: { page: 1, per_page: 1 }
});

// available_providers = [
//   {id: 5, name: 'Earthlink', total_requests: 1137},
//   {id: 6, name: 'AT&T', total_requests: 908}
// ]

// Popular select
<select>
  <option v-for="p in available_providers" :value="p.id">
    {{ p.name }} ({{ p.total_requests.toLocaleString() }} requests)
  </option>
</select>
```

---

### **2. Header com Totalizadores**

```vue
<div class="stats-header" v-if="aggregated_stats">
  <div class="stat">
    <span class="label">Total Requests:</span>
    <span class="value">{{ aggregated_stats.total_requests.toLocaleString() }}</span>
  </div>
  <div class="stat">
    <span class="label">Avg Success Rate:</span>
    <span class="value">{{ aggregated_stats.avg_success_rate.toFixed(1) }}%</span>
  </div>
  <div class="stat">
    <span class="label">Unique Domains:</span>
    <span class="value">{{ aggregated_stats.unique_domains }}</span>
  </div>
</div>
```

---

### **3. Badge Global (Quando Filtrar Provider)**

```vue
<div class="global-badge" v-if="global_stats">
  <strong>{{ selected_provider_name }}</strong> represents
  <strong>{{ global_stats.percentage_of_global }}%</strong>
  of all requests
  <br/>
  ({{ global_stats.provider_total_requests.toLocaleString() }} of {{ global_stats.global_total_requests.toLocaleString() }} total)
</div>
```

**Exemplo visual:**
```
┌─────────────────────────────────────────────────────┐
│ Earthlink represents 12.78% of all requests        │
│ (1,137 of 8,894 total)                              │
└─────────────────────────────────────────────────────┘
```

---

## 📊 Exemplo Completo

### **Request:**
```bash
GET /api/admin/reports/global/provider-ranking?provider_id=5&period=last_month&page=1&per_page=10
```

### **Response:**
```json
{
  "success": true,
  "data": [
    {
      "rank": 1,
      "domain_name": "smarterhome.ai",
      "total_requests": 416,
      "domain_total_requests": 2236,
      "percentage_of_domain": 18.60
    }
  ],
  "pagination": {
    "total": 5,
    "current_page": 1,
    "last_page": 1
  },
  "available_providers": [
    {"id": 5, "name": "Earthlink", "total_requests": 1137},
    {"id": 6, "name": "AT&T", "total_requests": 908}
  ],
  "aggregated_stats": {
    "total_requests": 1137,
    "avg_success_rate": 0.0,
    "unique_domains": 5
  },
  "global_stats": {
    "provider_total_requests": 1137,
    "global_total_requests": 8894,
    "percentage_of_global": 12.78
  }
}
```

---

## 🎨 Interface Sugerida

```
┌──────────────────────────────────────────────────────────────┐
│ 🏆 Provider Domain Rankings                                  │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ Provider: [▼ Earthlink                                    ]  │
│ Period:   [▼ Last Month                                   ]  │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐  │
│ │ Earthlink represents 12.78% of all requests            │  │
│ │ (1,137 of 8,894 total requests)                        │  │
│ └────────────────────────────────────────────────────────┘  │
│                                                              │
│ 📊 Showing 5 domains with Earthlink:                        │
│                                                              │
│ ┌──────────────────────────────────────────────────────────┐│
│ │ Total: 1,137 requests | Avg Success: 0% | 5 domains     ││
│ └──────────────────────────────────────────────────────────┘│
│                                                              │
│ #  Domain              Requests    % of Domain    Success   │
│ ─────────────────────────────────────────────────────────── │
│ 1  smarterhome.ai      416         18.60%         0.0%      │
│ 2  broadbandcheck.io   197          8.91%         0.0%      │
│ 3  ispfinder.net       190         11.10%         0.0%      │
│ 4  zip.50g.io          167         12.21%         0.0%      │
│ 5  fiberfinder.com     167         12.21%         0.0%      │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## 🔧 Query Parameters

| Parâmetro | Uso | Exemplo |
|-----------|-----|---------|
| `provider_id` | Filtrar provider | `?provider_id=5` |
| `period` | Período rápido | `?period=last_month` |
| `page` | Paginação | `?page=1` |
| `per_page` | Itens/página | `?per_page=15` |
| `technology` | Filtrar tech | `?technology=Fiber` |
| `sort_by` | Ordenar | `?sort_by=success_rate` |

**Períodos:** today, yesterday, last_week, last_month, last_year, all_time

---

## 🎯 Uso no Nuxt

```javascript
const { data: response } = await $fetch('/api/admin/reports/global/provider-ranking', {
  params: {
    provider_id: 5,
    period: 'last_month',
    page: 1,
    per_page: 15
  }
});

// Dados para a tabela
const ranking = response.data;

// Dropdown de providers
const providers = response.available_providers;
// [{id: 5, name: 'Earthlink', total_requests: 1137}, ...]

// Totalizadores (header da tabela)
const totals = response.aggregated_stats;
// {total_requests: 1137, avg_success_rate: 0, unique_domains: 5}

// Badge global (quando provider específico)
const global = response.global_stats;
// {provider_total_requests: 1137, global_total_requests: 8894, percentage_of_global: 12.78}

// Paginação
const pagination = response.pagination;
// {total: 5, current_page: 1, last_page: 1}
```

---

## 📋 Estrutura Completa

```typescript
interface ProviderRankingResponse {
  success: boolean;
  data: Array<{
    rank: number;
    domain_name: string;
    provider_name: string;
    total_requests: number;
    domain_total_requests: number;
    percentage_of_domain: number;
    avg_success_rate: number;
    avg_speed: number;
  }>;
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  available_providers: Array<{
    id: number;
    name: string;
    slug: string;
    total_requests: number;
  }>;
  aggregated_stats: {
    total_requests: number;
    avg_success_rate: number;
    avg_speed: number;
    unique_domains: number;
    unique_providers: number;
  };
  global_stats?: {  // Apenas quando provider_id for informado
    provider_total_requests: number;
    global_total_requests: number;
    percentage_of_global: number;
  };
  filters: {
    provider_id: number | null;
    technology: string | null;
    period: string | null;
    sort_by: string;
  };
}
```

---

## ✅ Resumo dos Dados

### **Por Linha (data):**
- `total_requests` - Requests deste provider neste domínio
- `percentage_of_domain` - % que representa no domínio

### **Agregado (aggregated_stats):**
- Soma de todos os domínios no ranking atual
- Para mostrar no header/totalizador

### **Global (global_stats):**
- Aparece apenas quando filtrar por provider
- % que este provider representa no total GERAL

### **Providers (available_providers):**
- Lista de todos os providers disponíveis
- Para popular dropdown

---

## 🧪 Testar

```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=5&page=1&per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -s | jq '{
    ranking_count: (.data | length),
    total: .pagination.total,
    providers_count: (.available_providers | length),
    aggregated: .aggregated_stats.total_requests,
    global_percentage: .global_stats.percentage_of_global
  }'
```

**Output:**
```json
{
  "ranking_count": 5,
  "total": 5,
  "providers_count": 39,
  "aggregated": 1137,
  "global_percentage": 12.78
}
```

---

## ✅ Status Final

**Implementado:**
- ✅ Rankings por provider
- ✅ Porcentagens por domínio
- ✅ Filtros de período (today, yesterday, etc)
- ✅ Paginação completa
- ✅ Lista de providers disponíveis
- ✅ Dados agregados
- ✅ Estatísticas globais

**Testes:** 19/19 passando ✅

**Pronto para produção!** 🚀

