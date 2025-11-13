# 🏆 API - Provider Ranking com Filtros de Período

## 📡 Endpoint

```
GET /api/admin/reports/global/provider-ranking
Authorization: Bearer {token}
```

---

## 🔧 Query Parameters

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `provider_id` | int | ID do provider | `?provider_id=5` |
| `period` | string | **Período predefinido** | `?period=today` |
| `technology` | string | Filtrar por tech | `?technology=Fiber` |
| `sort_by` | string | Ordenar por | `?sort_by=total_requests` |
| `limit` | int | Limitar resultados | `?limit=10` |
| `date_from` | date | Data inicial (manual) | `?date_from=2025-11-01` |
| `date_to` | date | Data final (manual) | `?date_to=2025-11-30` |

---

## 🆕 Parâmetro `period` (Novo)

### **Valores Aceitos:**
- `today` - Apenas hoje
- `yesterday` - Apenas ontem
- `last_week` - Últimos 7 dias
- `last_month` - Últimos 30 dias
- `last_year` - Últimos 365 dias
- `all_time` - Sem filtro de data

### **Prioridade:**
- Se `period` for informado, ele **sobrescreve** `date_from` e `date_to`
- Se `period` não for informado, usa `date_from` e `date_to` (modo manual)

---

## 📊 Exemplos de Uso

### **1. Top Earthlink - Hoje**
```bash
GET /api/admin/reports/global/provider-ranking?provider_id=5&period=today&limit=10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "ranking": [...],
    "total_entries": 3,
    "filters": {
      "provider_id": 5,
      "period": "today",
      "date_from": "2025-11-10",
      "date_to": "2025-11-10",
      "limit": 10
    }
  }
}
```

---

### **2. Top Spectrum - Última Semana**
```bash
GET /api/admin/reports/global/provider-ranking?provider_id=15&period=last_week&limit=10
```

**Retorna:** Top 10 domínios com Spectrum nos últimos 7 dias

---

### **3. Top AT&T - Último Mês**
```bash
GET /api/admin/reports/global/provider-ranking?provider_id=6&period=last_month&limit=10
```

**Retorna:** Top 10 domínios com AT&T nos últimos 30 dias

---

### **4. Top Cable - All Time**
```bash
GET /api/admin/reports/global/provider-ranking?technology=Cable&period=all_time&limit=20
```

**Retorna:** Top 20 domain+provider (Cable) de todos os tempos

---

### **5. Período Manual (Ainda Funciona)**
```bash
GET /api/admin/reports/global/provider-ranking?date_from=2025-11-01&date_to=2025-11-30&limit=10
```

**Retorna:** Novembro 2025 (modo manual)

---

## 🎯 Uso no Nuxt

### **Simples - Com Período:**
```javascript
// Top Earthlink - Último Mês
const ranking = await $fetch('/api/admin/reports/global/provider-ranking', {
  headers: { 'Authorization': `Bearer ${token}` },
  params: {
    provider_id: 5,
    period: 'last_month',
    limit: 10
  }
});
```

---

### **Dropdown de Períodos:**
```vue
<select v-model="selectedPeriod">
  <option value="today">Today</option>
  <option value="yesterday">Yesterday</option>
  <option value="last_week">Last Week</option>
  <option value="last_month">Last Month</option>
  <option value="last_year">Last Year</option>
  <option value="all_time">All Time</option>
</select>
```

```javascript
const selectedPeriod = ref('last_month');

watch(selectedPeriod, async (period) => {
  const ranking = await $fetch('/api/admin/reports/global/provider-ranking', {
    params: {
      provider_id: selectedProviderId.value,
      period: period,
      limit: 10
    }
  });
});
```

---

## ⚠️ Erros Possíveis

### **400 - Período Inválido:**
```json
{
  "success": false,
  "message": "Invalid period parameter. Must be one of: today, yesterday, last_week, last_month, last_year, all_time"
}
```

**Causa:** Passou `period=last_2_weeks` (não existe)

---

## 📋 Todas as Combinações

### **Filtros Simultâneos:**
```bash
# Top Spectrum + Fiber + Último Mês + Top 10
GET /api/admin/reports/global/provider-ranking?provider_id=15&technology=Fiber&period=last_month&limit=10
```

**Retorna:** Top 10 domínios com Spectrum Fiber no último mês

---

### **Sem Filtros:**
```bash
# Tudo, sem limite
GET /api/admin/reports/global/provider-ranking
```

**Retorna:** Todos os domain+provider combinations, sem filtro de período

---

## 🧪 Testar

### **Today:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?period=today&limit=5" \
  -H "Authorization: Bearer $TOKEN"
```

### **Last Week:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=5&period=last_week&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

### **All Time:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?period=all_time&limit=20" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📊 Response Format (Atualizado)

```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain_name": "smarterhome.ai",
        "provider_name": "Earthlink",
        "total_requests": 416,
        "domain_total_requests": 2236,
        "percentage_of_domain": 18.60,
        "avg_success_rate": 85.5,
        "avg_speed": 1200,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30"
      }
    ],
    "total_entries": 10,
    "filters": {
      "provider_id": 5,
      "technology": null,
      "period": "last_month",
      "date_from": "2025-10-10",
      "date_to": "2025-11-10",
      "sort_by": "total_requests",
      "limit": 10
    }
  }
}
```

**Novos campos em `filters`:**
- ✅ `period` - O período selecionado
- ✅ `date_from` e `date_to` - Datas calculadas automaticamente

---

## ✅ Períodos Disponíveis

| Period | Descrição | Intervalo |
|--------|-----------|-----------|
| `today` | Hoje | Hoje → Hoje |
| `yesterday` | Ontem | Ontem → Ontem |
| `last_week` | Última semana | Hoje - 7 dias → Hoje |
| `last_month` | Último mês | Hoje - 30 dias → Hoje |
| `last_year` | Último ano | Hoje - 365 dias → Hoje |
| `all_time` | Todo o histórico | null → null |

---

## 🎯 Casos de Uso

### **Dashboard "Top Hoje"**
```
GET /api/admin/reports/global/provider-ranking?period=today&limit=10
```
Mostra atividade do dia atual

---

### **Comparar Semana vs Mês**
```javascript
// Última semana
const lastWeek = await $fetch('/api/admin/reports/global/provider-ranking', {
  params: { provider_id: 5, period: 'last_week', limit: 10 }
});

// Último mês
const lastMonth = await $fetch('/api/admin/reports/global/provider-ranking', {
  params: { provider_id: 5, period: 'last_month', limit: 10 }
});

// Comparar rankings
```

---

### **Histórico Completo**
```
GET /api/admin/reports/global/provider-ranking?period=all_time&limit=100
```
Todo o histórico disponível

---

## ✅ Testes

```
✅ can_filter_by_today
✅ can_filter_by_last_week
✅ can_filter_by_last_month
✅ can_filter_by_all_time
✅ validation_error_for_invalid_period
✅ period_overrides_manual_dates

Total: 6 novos testes (100% passando)
```

---

## 🎉 Resumo

**Novo Parâmetro:** `period`  
**Valores:** today, yesterday, last_week, last_month, last_year, all_time  
**Benefício:** Frontend não precisa calcular datas  
**Retrocompatível:** ✅ Sim (date_from/date_to ainda funcionam)

---

**Status:** ✅ Implementado e testado  
**Testes:** 14/14 passando (8 originais + 6 novos)

