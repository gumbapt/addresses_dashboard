# ⏰ Filtros de Período - Provider Ranking

## ✅ Implementado

Agora a API aceita períodos predefinidos para facilitar o frontend.

---

## 🆕 Novo Parâmetro: `period`

### **Valores:**
```
today       → Hoje
yesterday   → Ontem
last_week   → Últimos 7 dias
last_month  → Últimos 30 dias
last_year   → Últimos 365 dias
all_time    → Todo o histórico
```

---

## 📡 Exemplos de Uso

### **1. Top Earthlink - Hoje**
```bash
GET /api/admin/reports/global/provider-ranking?provider_id=5&period=today&limit=10
```

---

### **2. Top Spectrum - Última Semana**
```bash
GET /api/admin/reports/global/provider-ranking?provider_id=15&period=last_week&limit=10
```

---

### **3. Top Providers - Último Mês**
```bash
GET /api/admin/reports/global/provider-ranking?period=last_month&limit=20
```

---

### **4. Todo o Histórico**
```bash
GET /api/admin/reports/global/provider-ranking?period=all_time&limit=50
```

---

## 🎯 Uso no Nuxt

```javascript
// Dropdown de períodos
const periods = [
  { value: 'today', label: 'Today' },
  { value: 'yesterday', label: 'Yesterday' },
  { value: 'last_week', label: 'Last Week' },
  { value: 'last_month', label: 'Last Month' },
  { value: 'last_year', label: 'Last Year' },
  { value: 'all_time', label: 'All Time' }
];

const selectedPeriod = ref('last_month');

// Buscar ranking
const ranking = await $fetch('/api/admin/reports/global/provider-ranking', {
  params: {
    provider_id: selectedProviderId.value,
    period: selectedPeriod.value,
    limit: 10
  }
});
```

---

## 📊 Response

```json
{
  "success": true,
  "data": {
    "ranking": [...],
    "filters": {
      "provider_id": 5,
      "period": "last_month",
      "date_from": "2025-10-10",
      "date_to": "2025-11-10",
      "limit": 10
    }
  }
}
```

**Campos em `filters`:**
- `period` - O período selecionado
- `date_from` e `date_to` - Datas calculadas automaticamente

---

## ⚙️ Comportamento

### **Prioridade:**
1. Se `period` for informado → Usa period (calcula datas automaticamente)
2. Se `period` não for informado → Usa `date_from` e `date_to` (modo manual)
3. Se nenhum → Sem filtro de data (all time)

### **Exemplo:**
```bash
# period sobrescreve date_from/date_to
GET /api/admin/reports/global/provider-ranking?period=today&date_from=2020-01-01

Resultado: Usa "today", ignora "2020-01-01"
```

---

## ✅ Testes

```
✅ can_filter_by_today
✅ can_filter_by_last_week
✅ can_filter_by_last_month
✅ can_filter_by_all_time
✅ validation_error_for_invalid_period
✅ period_overrides_manual_dates

Total: 6 testes (100% passando)
```

---

## 🎉 Benefícios

✅ Frontend não precisa calcular datas  
✅ Simplicidade (passar apenas "last_month")  
✅ Retrocompatível (date_from/date_to ainda funcionam)  
✅ Consistência (todos usam mesma lógica de período)  

---

**Status:** ✅ Pronto  
**Testes:** 14/14 passando  
**Docs:** Atualizada

