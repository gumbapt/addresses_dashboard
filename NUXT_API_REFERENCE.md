# 🎯 Nuxt - API Reference para Provider Rankings

## 📡 Endpoint

```
GET /api/admin/reports/global/provider-ranking
```

---

## 🚀 Como Usar no Nuxt

### **1. Buscar Lista de Providers (Uma vez)**

```javascript
// composables/useProviders.js
export const useProviders = () => {
  const providers = ref([]);
  
  const loadProviders = async () => {
    const { data } = await $fetch('/api/admin/providers', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    providers.value = data;
  };
  
  return { providers, loadProviders };
};
```

---

### **2. Buscar Ranking por Provider**

```javascript
// composables/useProviderRanking.js
export const useProviderRanking = () => {
  const ranking = ref([]);
  const loading = ref(false);
  
  const loadRanking = async (providerId, options = {}) => {
    loading.value = true;
    
    try {
      const { data } = await $fetch('/api/admin/reports/global/provider-ranking', {
        headers: { 'Authorization': `Bearer ${token}` },
        params: {
          provider_id: providerId,
          limit: options.limit || 10,
          sort_by: options.sortBy || 'total_requests',
          technology: options.technology || null,
          date_from: options.dateFrom || null,
          date_to: options.dateTo || null
        }
      });
      
      ranking.value = data.ranking;
      return data;
    } finally {
      loading.value = false;
    }
  };
  
  return { ranking, loading, loadRanking };
};
```

---

### **3. Usar na Página**

```vue
<!-- pages/admin/reports/provider-rankings.vue -->
<script setup>
const { providers, loadProviders } = useProviders();
const { ranking, loading, loadRanking } = useProviderRanking();

const selectedProvider = ref(null);
const filters = ref({
  limit: 10,
  sortBy: 'total_requests'
});

onMounted(async () => {
  await loadProviders();
});

watch(selectedProvider, async (providerId) => {
  if (providerId) {
    await loadRanking(providerId, filters.value);
  }
});
</script>

<template>
  <div>
    <h1>🏆 Provider Domain Rankings</h1>
    
    <!-- Seletor de Provider -->
    <select v-model="selectedProvider">
      <option :value="null">Select a Provider</option>
      <option 
        v-for="provider in providers" 
        :key="provider.id" 
        :value="provider.id"
      >
        {{ provider.name }}
      </option>
    </select>
    
    <!-- Filtros -->
    <select v-model="filters.limit" @change="loadRanking(selectedProvider, filters)">
      <option :value="10">Top 10</option>
      <option :value="20">Top 20</option>
      <option :value="50">Top 50</option>
    </select>
    
    <!-- Tabela -->
    <table v-if="!loading && ranking.length">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Domain</th>
          <th>Requests</th>
          <th>% of Domain</th>
          <th>Success Rate</th>
          <th>Tech</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in ranking" :key="item.rank">
          <td>#{{ item.rank }}</td>
          <td>{{ item.domain_name }}</td>
          <td>{{ item.total_requests.toLocaleString() }}</td>
          <td>
            <strong>{{ item.percentage_of_domain.toFixed(1) }}%</strong>
            <small>({{ item.domain_total_requests.toLocaleString() }} total)</small>
          </td>
          <td>{{ item.avg_success_rate.toFixed(1) }}%</td>
          <td>{{ item.technology }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

---

## 📊 Response Completo

```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain_id": 3,
        "domain_name": "smarterhome.ai",
        "provider_id": 5,
        "provider_name": "Earthlink",
        "technology": "Unknown",
        "total_requests": 416,
        "domain_total_requests": 2236,
        "percentage_of_domain": 18.60,
        "avg_success_rate": 85.5,
        "avg_speed": 1200,
        "total_reports": 3
      }
    ],
    "total_entries": 5,
    "filters": {
      "provider_id": 5,
      "limit": 10
    }
  }
}
```

**Campos Importantes:**
- `total_requests` - Requests deste provider neste domínio **(416)**
- `domain_total_requests` - Total de requests do domínio **(2,236)**
- `percentage_of_domain` - **18.60%** = (416 / 2,236) × 100

---

## 🎯 Exemplos de Interpretação

### **Exemplo 1:**
```json
{
  "domain_name": "smarterhome.ai",
  "provider_name": "Earthlink",
  "total_requests": 416,
  "domain_total_requests": 2236,
  "percentage_of_domain": 18.60
}
```
**Significa:** Earthlink representa 18.60% de todas as consultas de smarterhome.ai

---

### **Exemplo 2:**
```json
{
  "domain_name": "zip.50g.io",
  "provider_name": "Spectrum",
  "total_requests": 500,
  "domain_total_requests": 1000,
  "percentage_of_domain": 50.0
}
```
**Significa:** Spectrum é METADE (50%) de todas as consultas de zip.50g.io

---

### **Exemplo 3:**
```json
{
  "domain_name": "example.com",
  "provider_name": "AT&T",
  "total_requests": 10,
  "domain_total_requests": 1000,
  "percentage_of_domain": 1.0
}
```
**Significa:** AT&T é apenas 1% das consultas de example.com (pouco relevante)

---

## 🧪 Teste Completo

```bash
# Top 5 Earthlink com porcentagens
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=5&limit=5" \
  -H "Authorization: Bearer $TOKEN" \
  -s | jq -r '.data.ranking[] | "\(.rank). \(.domain_name) - \(.total_requests) requests (\(.percentage_of_domain)% of \(.domain_total_requests) total)"'
```

**Output:**
```
1. smarterhome.ai - 416 requests (18.60% of 2236 total)
2. broadbandcheck.io - 197 requests (8.91% of 2211 total)
3. ispfinder.net - 190 requests (11.10% of 1711 total)
4. zip.50g.io - 167 requests (12.21% of 1368 total)
5. fiberfinder.com - 167 requests (12.21% of 1368 total)
```

---

## ✅ Pronto para Usar

**Response inclui:**
- ✅ Números absolutos (`total_requests`)
- ✅ Números relativos (`percentage_of_domain`)
- ✅ Total do domínio (`domain_total_requests`)
- ✅ Tudo no mesmo objeto (backward compatible)

**Nuxt pode renderizar como preferir!** 🚀

