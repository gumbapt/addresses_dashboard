# 🏆 API Provider Ranking - Documentação Completa

## 📡 Endpoint

```
GET /api/admin/reports/global/provider-ranking
Authorization: Bearer {token}
```

---

## 🔧 Query Parameters

| Parâmetro | Tipo | Descrição | Default | Exemplo |
|-----------|------|-----------|---------|---------|
| `provider_id` | int | ID do provider | null | `5` |
| `technology` | string | Filtrar por tecnologia | null | `Fiber` |
| `period` | string | Período predefinido | null | `last_month` |
| `date_from` | date | Data inicial (manual) | null | `2025-11-01` |
| `date_to` | date | Data final (manual) | null | `2025-11-30` |
| `sort_by` | string | Ordenar por | `total_requests` | `success_rate` |
| `page` | int | Página | 1 | `2` |
| `per_page` | int | Itens por página | 15 | `20` |
| `limit` | int | Limite (deprecated) | null | `10` |

---

## 📊 Modo 1: Com Paginação (Recomendado)

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?provider_id=5&page=1&per_page=15
Authorization: Bearer {token}
```

### **Response:**
```json
{
  "success": true,
  "data": [
    {
      "rank": 1,
      "domain_id": 3,
      "domain_name": "smarterhome.ai",
      "domain_slug": "smarterhome-ai",
      "provider_id": 5,
      "provider_name": "Earthlink",
      "technology": "Unknown",
      "total_requests": 416,
      "domain_total_requests": 2236,
      "percentage_of_domain": 18.60,
      "avg_success_rate": 85.5,
      "avg_speed": 1158,
      "total_reports": 3,
      "period_start": "2025-11-10",
      "period_end": "2025-11-10",
      "days_covered": 1
    }
  ],
  "pagination": {
    "total": 50,
    "per_page": 15,
    "current_page": 1,
    "last_page": 4,
    "from": 1,
    "to": 15
  },
  "filters": {
    "provider_id": 5,
    "technology": null,
    "period": null,
    "date_from": null,
    "date_to": null,
    "sort_by": "total_requests"
  }
}
```

---

## 📊 Modo 2: Sem Paginação (Limit - Backward Compatible)

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?provider_id=5&limit=10
Authorization: Bearer {token}
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "ranking": [...],
    "total_entries": 10,
    "filters": {...}
  }
}
```

**Nota:** Se `page` ou `per_page` for informado, usa paginação. Caso contrário, usa `limit`.

---

## 🎯 Exemplos Práticos

### **1. Top 10 Earthlink - Última Semana (Com Paginação)**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=5&period=last_week&page=1&per_page=10" \
  -H "Authorization: Bearer $TOKEN"
```

---

### **2. Top 20 Fiber - Hoje (Página 2)**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?technology=Fiber&period=today&page=2&per_page=20" \
  -H "Authorization: Bearer $TOKEN"
```

---

### **3. Top 50 - All Time (Sem Paginação - Old Style)**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?limit=50" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🎨 Uso no Nuxt

### **Com Paginação:**

```javascript
const currentPage = ref(1);
const perPage = ref(15);
const ranking = ref([]);
const pagination = ref(null);

const loadRanking = async () => {
  const response = await $fetch('/api/admin/reports/global/provider-ranking', {
    headers: { 'Authorization': `Bearer ${token}` },
    params: {
      provider_id: 5,
      period: 'last_month',
      page: currentPage.value,
      per_page: perPage.value
    }
  });
  
  ranking.value = response.data;
  pagination.value = response.pagination;
};

// Quando mudar de página
const goToPage = (page) => {
  currentPage.value = page;
  loadRanking();
};
```

---

### **Renderizar Paginação:**

```vue
<template>
  <div>
    <!-- Tabela -->
    <table>
      <tr v-for="item in ranking" :key="item.rank">
        <td>#{{ item.rank }}</td>
        <td>{{ item.domain_name }}</td>
        <td>{{ item.total_requests }}</td>
        <td>{{ item.percentage_of_domain.toFixed(1) }}%</td>
      </tr>
    </table>
    
    <!-- Paginação -->
    <div class="pagination" v-if="pagination">
      <button 
        @click="goToPage(pagination.current_page - 1)" 
        :disabled="pagination.current_page === 1"
      >
        ← Previous
      </button>
      
      <span>
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
        ({{ pagination.total }} total)
      </span>
      
      <button 
        @click="goToPage(pagination.current_page + 1)" 
        :disabled="pagination.current_page === pagination.last_page"
      >
        Next →
      </button>
    </div>
  </div>
</template>
```

---

## 📋 Campos de Paginação

```json
{
  "pagination": {
    "total": 50,           // Total de registros
    "per_page": 15,        // Itens por página
    "current_page": 1,     // Página atual
    "last_page": 4,        // Última página
    "from": 1,             // Índice do primeiro item
    "to": 15               // Índice do último item
  }
}
```

---

## ⚙️ Comportamento

### **Modo Paginação (Novo):**
```
?page=1&per_page=15

Response: { data: [...], pagination: {...} }
```

### **Modo Limit (Antigo):**
```
?limit=10

Response: { data: { ranking: [...], total_entries: 10 } }
```

### **Prioridade:**
- Se `page` OU `per_page` estiver presente → Usa **paginação**
- Se não → Usa **limit** (backward compatible)

---

## 🔢 Limites

- `per_page`: Mínimo 1, Máximo 100
- `page`: Mínimo 1
- Se `page` > `last_page`, retorna última página

---

## 🎯 Casos de Uso

### **1. Tabela Paginada - 15 por Página**
```javascript
// Página 1
GET /api/admin/reports/global/provider-ranking?provider_id=5&page=1&per_page=15

// Página 2
GET /api/admin/reports/global/provider-ranking?provider_id=5&page=2&per_page=15
```

---

### **2. Tabela Paginada com Filtros**
```javascript
GET /api/admin/reports/global/provider-ranking?provider_id=5&technology=Fiber&period=last_month&page=1&per_page=20
```

---

### **3. Ver Todos (Sem Limite)**
```javascript
GET /api/admin/reports/global/provider-ranking?per_page=100&page=1
```

---

## ✅ Testes

```
Pagination Tests:
✅ can_paginate_provider_ranking
✅ can_get_second_page
✅ can_change_per_page
✅ backward_compatible_with_limit
✅ pagination_works_with_filters

Total: 5 testes (100% passando)
```

---

## 📊 Resumo Final

### **Parâmetros Disponíveis:**
```
Filtros:
  provider_id   → Filtrar por provider
  technology    → Filtrar por tecnologia
  period        → Período predefinido
  date_from/to  → Datas manuais
  sort_by       → Ordenação

Paginação:
  page          → Número da página
  per_page      → Itens por página
  
Legacy:
  limit         → Limite simples (sem paginação)
```

### **Períodos Disponíveis:**
```
today, yesterday, last_week, last_month, last_year, all_time
```

### **Campos Retornados:**
```
Absolutos:
  total_requests         → Requests do provider neste domínio
  domain_total_requests  → Total do domínio (todos providers)
  
Relativos:
  percentage_of_domain   → % que o provider representa
```

---

## 🚀 Quick Examples

```bash
# Paginado - Página 1
GET /api/admin/reports/global/provider-ranking?provider_id=5&page=1&per_page=15

# Paginado - Última semana
GET /api/admin/reports/global/provider-ranking?period=last_week&page=1&per_page=20

# Legacy - Top 10
GET /api/admin/reports/global/provider-ranking?limit=10
```

---

**Status:** ✅ Implementado e testado  
**Testes:** 19/19 passando (8 + 6 + 5)  
**Retrocompatível:** ✅ Sim (limit ainda funciona)

