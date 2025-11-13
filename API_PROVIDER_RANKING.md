# 🏆 API - Provider Domain Rankings

## 🎯 Objetivo

**Selecionar um provider** (ex: Spectrum) e ver **ranking dos domínios** que mais têm ocorrências deste provider.

---

## 📡 Endpoint

```
GET /api/admin/reports/global/provider-ranking
Authorization: Bearer {token}
```

---

## 🔧 Query Parameters

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `provider_id` | int | **ID do provider** | `?provider_id=5` |
| `technology` | string | Filtrar por tech | `?technology=Fiber` |
| `sort_by` | string | Ordenar por | `?sort_by=total_requests` |
| `limit` | int | Limitar resultados | `?limit=10` |
| `date_from` | date | Data inicial | `?date_from=2025-11-01` |
| `date_to` | date | Data final | `?date_to=2025-11-30` |

**sort_by options:**
- `total_requests` (default) - Ordenar por volume
- `success_rate` - Ordenar por taxa de sucesso
- `avg_speed` - Ordenar por velocidade
- `total_reports` - Ordenar por quantidade de reports

---

## 📊 Exemplo 1: Top Domínios usando Spectrum

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?provider_id=15&limit=10
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain_id": 3,
        "domain_name": "smarterhome.ai",
        "domain_slug": "smarterhome-ai",
        "provider_id": 15,
        "provider_name": "Spectrum",
        "technology": "Cable",
        "total_requests": 450,
        "avg_success_rate": 88.5,
        "avg_speed": 1200,
        "total_reports": 15,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30",
        "days_covered": 30
      },
      {
        "rank": 2,
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "domain_slug": "zip-50g-io",
        "provider_id": 15,
        "provider_name": "Spectrum",
        "technology": "Cable",
        "total_requests": 320,
        "avg_success_rate": 92.0,
        "avg_speed": 1150,
        "total_reports": 12,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30",
        "days_covered": 30
      }
    ],
    "total_entries": 2,
    "filters": {
      "provider_id": 15,
      "technology": null,
      "date_from": null,
      "date_to": null,
      "sort_by": "total_requests",
      "limit": 10
    }
  }
}
```

**Interpretação:**
- smarterhome.ai tem **450 requests** de Spectrum (1º lugar)
- zip.50g.io tem **320 requests** de Spectrum (2º lugar)

---

## 📊 Exemplo 2: Top Domínios usando AT&T

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?provider_id=6&limit=10
Authorization: Bearer {token}
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain_id": 2,
        "domain_name": "fiberfinder.com",
        "provider_id": 6,
        "provider_name": "AT&T",
        "technology": "Fiber",
        "total_requests": 580,
        "avg_success_rate": 95.2,
        "avg_speed": 980,
        "total_reports": 20
      }
    ],
    "total_entries": 1
  }
}
```

**Interpretação:**
- fiberfinder.com é o domínio com mais consultas de AT&T (580 requests)

---

## 📊 Exemplo 3: Top Domínios - Fiber Providers

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?technology=Fiber&sort_by=success_rate&limit=20
Authorization: Bearer {token}
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain_id": 2,
        "domain_name": "fiberfinder.com",
        "provider_id": 6,
        "provider_name": "AT&T",
        "technology": "Fiber",
        "total_requests": 580,
        "avg_success_rate": 95.2,
        "avg_speed": 980,
        "total_reports": 20
      },
      {
        "rank": 2,
        "domain_id": 3,
        "domain_name": "smarterhome.ai",
        "provider_id": 7,
        "provider_name": "Verizon",
        "technology": "Fiber",
        "total_requests": 400,
        "avg_success_rate": 92.8,
        "avg_speed": 1050,
        "total_reports": 18
      }
    ],
    "total_entries": 2
  }
}
```

**Interpretação:**
- Mostra todas as combinações domain+provider que usam Fiber
- Ordenado por success_rate (melhor primeiro)

---

## 🎯 Caso de Uso Principal: "Top Spectrum"

### **Fluxo:**
1. Usuário seleciona "Spectrum" no dropdown
2. Frontend busca `provider_id` do Spectrum
3. Faz request: `?provider_id=15&limit=10`
4. Mostra tabela com os 10 domínios que mais consultam Spectrum

### **Implementação Nuxt:**
```javascript
// 1. Buscar providers (fazer uma vez no mounted)
const providers = await $fetch('/api/admin/providers', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// 2. Usuário seleciona provider
const selectedProviderId = 15; // Spectrum

// 3. Buscar ranking
const ranking = await $fetch('/api/admin/reports/global/provider-ranking', {
  headers: { 'Authorization': `Bearer ${token}` },
  params: {
    provider_id: selectedProviderId,
    limit: 10
  }
});

// 4. Renderizar
ranking.data.ranking.forEach(item => {
  console.log(`#${item.rank} - ${item.domain_name}: ${item.total_requests} requests`);
});
```

---

## 📋 Lista de Providers Disponíveis

### **Request:**
```http
GET /api/admin/providers
Authorization: Bearer {token}
```

### **Response:**
```json
{
  "success": true,
  "data": [
    {"id": 5, "name": "Earthlink", "slug": "earthlink"},
    {"id": 6, "name": "AT&T", "slug": "att"},
    {"id": 7, "name": "Verizon", "slug": "verizon"},
    {"id": 15, "name": "Spectrum", "slug": "spectrum"}
  ]
}
```

**Uso:** Popular dropdown com estes providers

---

## 🔍 Descobrir Provider ID

### **Opção A: Via Tinker (Backend)**
```bash
docker-compose exec app php artisan tinker --execute="
echo 'Spectrum: ' . App\Models\Provider::where('name', 'Spectrum')->first()->id . PHP_EOL;
echo 'AT&T: ' . App\Models\Provider::where('name', 'AT&T')->first()->id . PHP_EOL;
echo 'Verizon: ' . App\Models\Provider::where('name', 'Verizon')->first()->id . PHP_EOL;
"
```

### **Opção B: Via API (Frontend)**
```javascript
// Buscar todos os providers
const response = await $fetch('/api/admin/providers');
const providers = response.data;

// Encontrar Spectrum
const spectrum = providers.find(p => p.name === 'Spectrum');
console.log('Spectrum ID:', spectrum.id);
```

---

## 📊 Exemplos de Uso

### **1. Top 10 Domínios - Earthlink (ID: 5)**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=5&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

**Retorna:** Top 10 domínios com mais requests de Earthlink

---

### **2. Top 10 Domínios - AT&T (ID: 6)**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=6&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

**Retorna:** Top 10 domínios com mais requests de AT&T

---

### **3. Top 20 Domínios - Qualquer Provider com Fiber**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?technology=Fiber&limit=20" \
  -H "Authorization: Bearer $TOKEN"
```

**Retorna:** Top 20 combinações domain+provider usando Fiber

---

### **4. Top Domínios - Spectrum, Ordenado por Success Rate**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=15&sort_by=success_rate&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

**Retorna:** Domínios com melhor taxa de sucesso usando Spectrum

---

## ⚠️ Erros Possíveis

### **400 - Sort By Inválido**
```json
{
  "success": false,
  "message": "Invalid sort_by parameter. Must be one of: total_requests, success_rate, avg_speed, total_reports"
}
```

### **401 - Não Autenticado**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

## 🎯 Interface Sugerida (Sem Código)

### **Tela 1: Seletor de Provider**
```
┌─────────────────────────────────────────────────────┐
│ 🏆 Provider Domain Rankings                         │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Select Provider: [▼ Spectrum                    ]  │
│ Sort By:        [▼ Most Requests                ]  │
│ Limit:          [▼ Top 10                       ]  │
│                                                     │
│ ─────────────────────────────────────────────────  │
│                                                     │
│ 📊 Top Domains using Spectrum:                     │
│                                                     │
│ #1  smarterhome.ai      450 requests  88.5% ✅     │
│ #2  zip.50g.io          320 requests  92.0% ✅     │
│ #3  fiberfinder.com     280 requests  85.2% ✅     │
│ #4  ispfinder.net       150 requests  78.5% ⚠️     │
│ #5  broadbandcheck.io   120 requests  82.0% ✅     │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

### **Tela 2: Grid de Top Providers**
```
┌─────────────────────────────────────────────────────┐
│ 📊 Top Providers Overview                           │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │
│ │ Earthlink   │  │ HughesNet   │  │ AT&T        │ │
│ │             │  │             │  │             │ │
│ │ Top Domain: │  │ Top Domain: │  │ Top Domain: │ │
│ │ domain1.com │  │ domain2.com │  │ domain3.com │ │
│ │             │  │             │  │             │ │
│ │ 1,137 req   │  │ 1,069 req   │  │ 908 req     │ │
│ └─────────────┘  └─────────────┘  └─────────────┘ │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 📋 Fluxo de Implementação

### **Passo 1: Buscar Lista de Providers**
```javascript
// Fazer uma vez ao montar a página
const response = await $fetch('/api/admin/providers', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const providers = response.data;
// [{id: 5, name: 'Earthlink'}, {id: 6, name: 'AT&T'}, ...]
```

---

### **Passo 2: Quando Usuário Selecionar Provider**
```javascript
// Exemplo: Usuário selecionou Spectrum (ID: 15)
const selectedProviderId = 15;

const ranking = await $fetch('/api/admin/reports/global/provider-ranking', {
  headers: { 'Authorization': `Bearer ${token}` },
  params: {
    provider_id: selectedProviderId,
    limit: 10,
    sort_by: 'total_requests'
  }
});

// ranking.data.ranking = array de domínios ordenados
```

---

### **Passo 3: Renderizar Resultados**
```javascript
ranking.data.ranking.forEach(item => {
  // item.rank           - Posição no ranking (1, 2, 3...)
  // item.domain_name    - Nome do domínio
  // item.total_requests - Quantidade de requests deste provider neste domínio
  // item.avg_success_rate - Taxa de sucesso média
  // item.avg_speed      - Velocidade média
  // item.technology     - Fiber, Cable, etc
});
```

---

## 🎯 Casos de Uso

### **Caso 1: "Top Spectrum"**
```javascript
// Request
GET /api/admin/reports/global/provider-ranking?provider_id=15&limit=10

// Mostra: Top 10 domínios com mais consultas de Spectrum
// Ordenado por: total_requests (maior primeiro)
```

---

### **Caso 2: "Top AT&T com Melhor Performance"**
```javascript
// Request
GET /api/admin/reports/global/provider-ranking?provider_id=6&sort_by=success_rate&limit=10

// Mostra: Top 10 domínios com melhor success rate usando AT&T
// Ordenado por: success_rate (maior primeiro)
```

---

### **Caso 3: "Top Verizon - Últimos 30 Dias"**
```javascript
// Request
GET /api/admin/reports/global/provider-ranking?provider_id=7&date_from=2025-11-01&date_to=2025-11-30&limit=10

// Mostra: Top 10 domínios com Verizon no período
// Período: Novembro 2025
```

---

### **Caso 4: "Top Fiber (Qualquer Provider)"**
```javascript
// Request
GET /api/admin/reports/global/provider-ranking?technology=Fiber&limit=20

// Mostra: Top 20 domain+provider combinations com Fiber
// Não filtra provider específico, mostra todos com Fiber
```

---

## 🔢 IDs dos Principais Providers

```
ID   5: Earthlink       - 1,137 total requests
ID   1: HughesNet       - 1,069 total requests
ID   6: AT&T            - 908 total requests
ID   8: GeoLinks        - 186 total requests
ID  12: Cox             - 149 total requests
ID  15: Frontier        - 111 total requests
ID  14: Cogent          - 81 total requests
ID  13: Astound         - 69 total requests
```

**Nota:** IDs podem variar entre ambientes. Use a API `/api/admin/providers` para obter IDs corretos.

---

## 📊 Estrutura do Response

```typescript
interface ProviderRankingResponse {
  success: boolean;
  data: {
    ranking: Array<{
      rank: number;                    // Posição (1, 2, 3...)
      domain_id: number;               // ID do domínio
      domain_name: string;             // Nome do domínio
      domain_slug: string;             // Slug do domínio
      provider_id: number;             // ID do provider
      provider_name: string;           // Nome do provider
      technology: string | null;       // Fiber, Cable, DSL, Mobile, Satellite
      total_requests: number;          // Total de requests
      avg_success_rate: number;        // Taxa de sucesso (0-100)
      avg_speed: number;               // Velocidade média em ms
      total_reports: number;           // Quantidade de reports
      period_start: string;            // Data inicial (YYYY-MM-DD)
      period_end: string;              // Data final (YYYY-MM-DD)
      days_covered: number;            // Dias cobertos
    }>;
    total_entries: number;
    filters: {
      provider_id: number | null;
      technology: string | null;
      date_from: string | null;
      date_to: string | null;
      sort_by: string;
      limit: number | null;
    };
  };
}
```

---

## 🧪 Testar via cURL

### **Top 10 Earthlink:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=5&limit=10" \
  -H "Authorization: Bearer seu_token_aqui" | jq
```

### **Top 10 HughesNet:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=1&limit=10" \
  -H "Authorization: Bearer seu_token_aqui" | jq
```

### **Top 20 Cable Providers:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?technology=Cable&limit=20" \
  -H "Authorization: Bearer seu_token_aqui" | jq
```

---

## 💡 Dicas de Implementação

### **1. Dropdown de Providers**
- Buscar `/api/admin/providers` uma vez
- Popular dropdown com `id` e `name`
- Ao selecionar, fazer request com `provider_id`

### **2. Filtros**
- Technology: Dropdown fixo (Fiber, Cable, DSL, Mobile, Satellite)
- Sort By: Dropdown fixo (total_requests, success_rate, avg_speed)
- Limit: Dropdown fixo (10, 20, 50, 100)

### **3. Renderização**
- Loop no array `ranking`
- Mostrar `rank`, `domain_name`, `total_requests`, `avg_success_rate`
- Aplicar badges/cores por technology
- Medals (🥇🥈🥉) para top 3

### **4. Performance**
- Cachear lista de providers
- Debounce ao mudar filtros
- Loading state durante fetch

---

## ✅ Resumo Rápido

**Endpoint:** `GET /api/admin/reports/global/provider-ranking`

**Principal Parâmetro:** `provider_id` - Filtra domínios por provider

**Exemplo "Top Spectrum":**
```
GET /api/admin/reports/global/provider-ranking?provider_id=15&limit=10

Retorna: Top 10 domínios com mais requests de Spectrum
```

**Fields Importantes:**
- `domain_name` - Nome do domínio
- `total_requests` - Quantidade de requests
- `avg_success_rate` - Taxa de sucesso (%)
- `technology` - Fiber, Cable, etc

---

**Status:** ✅ API pronta e testada  
**Testes:** 8/8 passando  
**Próximo:** Implementar no Nuxt

