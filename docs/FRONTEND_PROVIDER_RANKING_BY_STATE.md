# Provider Ranking by State - Documentação Frontend

## Endpoints

### 1. Listar Estados (para popular dropdown/select)

```
GET /api/states
```

**Autenticação:** Não requer (rota pública)

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "AL",
      "name": "Alabama",
      "timezone": "America/Chicago",
      "latitude": 32.806671,
      "longitude": -86.791130,
      "is_active": true
    },
    {
      "id": 5,
      "code": "CA",
      "name": "California",
      "timezone": "America/Los_Angeles",
      "latitude": 36.116203,
      "longitude": -119.681564,
      "is_active": true
    }
    // ... todos os 51 estados (50 estados + DC)
  ]
}
```

### 2. Provider Ranking by State

```
GET /api/admin/reports/global/provider-ranking-by-state
```

## Autenticação

Requer autenticação de admin via Sanctum. O token deve ser enviado no header:
```
Authorization: Bearer {token}
```

**Nota:** Use primeiro `/api/states` para obter a lista de estados e seus IDs, depois use o `state_id` na rota de ranking.

## Parâmetros de Query

| Parâmetro | Tipo | Obrigatório | Descrição | Exemplo |
|-----------|------|-------------|-----------|---------|
| `state_id` | integer | ✅ Sim | ID do estado para filtrar | `1` |
| `provider_id` | integer | ❌ Não | ID do provider para filtrar (opcional) | `5` |
| `period` | string | ❌ Não | Período pré-definido. Para períodos diferentes de `all_time`, sobrescreve `date_from` e `date_to`. Para `all_time`, permite usar `date_from`/`date_to` para limitar o período. | `today`, `yesterday`, `last_week`, `last_month`, `last_year`, `all_time` |
| `date_from` | string | ❌ Não | Data inicial (YYYY-MM-DD). Usado quando `period` não é fornecido ou quando `period=all_time`. Requer `date_to` | `2025-11-01` |
| `date_to` | string | ❌ Não | Data final (YYYY-MM-DD). Usado quando `period` não é fornecido ou quando `period=all_time`. Requer `date_from` | `2025-11-30` |
| `sort_by` | string | ❌ Não | Critério de ordenação. Padrão: `total_requests` | `total_requests`, `success_rate`, `avg_speed`, `total_reports` |
| `aggregate_by_provider` | boolean | ❌ Não | Se `true`, agrega todos os domínios por provider (ranking apenas por provider). Padrão: `false` | `true`, `false` |

### Valores Aceitos para `sort_by`:
- `total_requests` - Ordena por total de requisições (padrão)
- `success_rate` - Ordena por taxa de sucesso média
- `avg_speed` - Ordena por velocidade média
- `total_reports` - Ordena por total de relatórios

### Valores Aceitos para `period`:
- `today` - Apenas hoje
- `yesterday` - Apenas ontem
- `last_week` - Últimos 7 dias
- `last_month` - Últimos 30 dias
- `last_year` - Últimos 365 dias
- `all_time` - Todos os dados disponíveis

### ⚙️ Comportamento de Períodos:

**Forma Recomendada:**
- **Período pré-definido:** Use apenas `period` (ex: `period=last_month`)
- **Período customizado:** Use apenas `date_from` e `date_to` (sem `period`)

**Prioridade:**
1. Se `period` for fornecido (exceto `all_time`) → Usa o período pré-definido (calcula datas automaticamente, ignora `date_from`/`date_to` se fornecidos)
2. Se `period` não for fornecido E `date_from`/`date_to` forem fornecidos → Usa as datas customizadas (forma recomendada para período customizado)
3. Se `period=all_time` E `date_from`/`date_to` forem fornecidos → Usa as datas customizadas (funciona, mas não é necessário - prefira usar apenas date_from/date_to)
4. Se nenhum for fornecido → Sem filtro de data (all time)

**Período Customizado:**
- Quando não usar `period`, você pode fornecer `date_from` e `date_to` para selecionar um intervalo de datas específico
- Ambos `date_from` e `date_to` devem ser fornecidos juntos (não é possível fornecer apenas um)
- Formato: `YYYY-MM-DD` (ex: `2025-11-01`)
- `date_from` deve ser anterior ou igual a `date_to`

**Exemplo de uso no frontend:**
```vue
<!-- Dropdown de período -->
<select v-model="selectedPeriod" @change="handlePeriodChange">
  <option value="">Selecione um período</option>
  <option value="today">Hoje</option>
  <option value="yesterday">Ontem</option>
  <option value="last_week">Últimos 7 dias</option>
  <option value="last_month">Últimos 30 dias</option>
  <option value="last_year">Últimos 365 dias</option>
  <option value="all_time">Todo o histórico</option>
  <option value="custom">Período Customizado</option>
</select>

<!-- Seletores de data customizada (mostrar apenas quando custom) -->
<div v-if="selectedPeriod === 'custom'">
  <label>Data Inicial:</label>
  <input type="date" v-model="customDateFrom" @change="loadRanking" />
  
  <label>Data Final:</label>
  <input type="date" v-model="customDateTo" @change="loadRanking" />
</div>

<script setup>
// Quando 'custom' for selecionado, enviar apenas date_from e date_to (sem period)
const loadRanking = () => {
  const params = {
    state_id: selectedStateId.value,
    sort_by: sortBy.value
  }
  
  if (selectedPeriod.value === 'custom') {
    // Seletor customizado: usar apenas date_from e date_to
    params.date_from = customDateFrom.value
    params.date_to = customDateTo.value
    // Não incluir 'period'
  } else if (selectedPeriod.value) {
    // Período pré-definido: usar apenas period
    params.period = selectedPeriod.value
    // Não incluir date_from/date_to
  }
  
  // Fazer requisição...
}
</script>
```

## Resposta de Sucesso (200)

### Quando `aggregate_by_provider=false` (padrão - ranking por domínio+provider)

```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "domain_slug": "zip-50g-io",
        "provider_id": 5,
        "provider_name": "Spectrum",
        "state_id": 5,
        "state_code": "CA",
        "state_name": "California",
        "total_requests": 1250,
        "avg_success_rate": 95.5,
        "avg_speed": 150.25,
        "total_reports": 10,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30",
                "days_covered": 30,
                "domain_total_requests": 5000,
                "percentage_of_domain": 25.0,
                "provider_total_requests": 3250,
                "percentage_of_provider_in_state": 38.46
      },
      {
        "rank": 2,
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "domain_slug": "zip-50g-io",
        "provider_id": 8,
        "provider_name": "AT&T",
        "state_id": 5,
        "state_code": "CA",
        "state_name": "California",
        "total_requests": 980,
        "avg_success_rate": 92.3,
        "avg_speed": 145.80,
        "total_reports": 10,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30",
                "days_covered": 30,
                "domain_total_requests": 5000,
                "percentage_of_domain": 19.6,
                "provider_total_requests": 2800,
                "percentage_of_provider_in_state": 35.0
      }
    ],
    "total_entries": 2,
    "filters": {
      "state_id": 5,
      "provider_id": null,
      "period": "last_month",
      "date_from": "2025-11-01",
      "date_to": "2025-11-30",
      "sort_by": "total_requests",
      "aggregate_by_provider": false
    }
  },
  "note": "Data is precise (from report_state_providers table), not approximated"
}
```

### Quando `aggregate_by_provider=true` (ranking apenas por provider, agregando todos os domínios)

```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "provider_id": 5,
        "provider_name": "Spectrum",
        "state_id": 5,
        "state_code": "CA",
        "state_name": "California",
        "total_requests": 3250,
        "avg_success_rate": 94.8,
        "avg_speed": 148.50,
        "total_reports": 25,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30",
        "days_covered": 30,
        "domains": "1:zip.50g.io, 2:example.com",
        "domains_count": 2,
        "provider_total_requests": 3250,
        "percentage_of_state": 15.2
      },
      {
        "rank": 2,
        "provider_id": 8,
        "provider_name": "AT&T",
        "state_id": 5,
        "state_code": "CA",
        "state_name": "California",
        "total_requests": 2800,
        "avg_success_rate": 91.5,
        "avg_speed": 142.30,
        "total_reports": 22,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30",
        "days_covered": 30,
        "domains": "1:zip.50g.io, 3:another.com",
        "domains_count": 2,
        "provider_total_requests": 2800,
        "percentage_of_state": 13.1
      }
    ],
    "total_entries": 2,
    "filters": {
      "state_id": 5,
      "provider_id": null,
      "period": "last_month",
      "date_from": "2025-11-01",
      "date_to": "2025-11-30",
      "sort_by": "total_requests",
      "aggregate_by_provider": true
    }
  },
  "note": "Data is precise (from report_state_providers table), not approximated"
}
```

**Diferenças importantes:**
- Quando `aggregate_by_provider=false`: Cada entrada representa um provider em um domínio específico. Inclui:
  - `domain_id`, `domain_name`, `domain_slug`, `domain_total_requests`
  - `percentage_of_domain`: Quanto o provider representa do total do domínio no estado (%)
  - `provider_total_requests`: Total de requests do provider no estado (soma de todos os domínios)
  - `percentage_of_provider_in_state`: Quanto esta row (domínio+provider) representa do total do provider no estado (%)
- Quando `aggregate_by_provider=true`: Cada entrada representa um provider agregado (soma de todos os domínios). Inclui:
  - `domains` (lista de domínios), `domains_count`
  - `provider_total_requests`: Total de requests do provider no estado
  - `percentage_of_state`: Quanto o provider representa do total do estado (%)

### Campos do Ranking

#### Campos Comuns (sempre presentes)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `rank` | integer | Posição no ranking (1, 2, 3...) |
| `provider_id` | integer | ID do provider |
| `provider_name` | string | Nome do provider |
| `state_id` | integer | ID do estado |
| `state_code` | string | Código do estado (ex: "CA", "NY") |
| `state_name` | string | Nome do estado |
| `total_requests` | integer | Total de requisições do provider neste estado |
| `avg_success_rate` | float | Taxa de sucesso média (%) |
| `avg_speed` | float | Velocidade média (Mbps) |
| `total_reports` | integer | Total de relatórios considerados |
| `period_start` | string | Data inicial do período (YYYY-MM-DD) |
| `period_end` | string | Data final do período (YYYY-MM-DD) |
| `days_covered` | integer | Número de dias cobertos |

#### Campos quando `aggregate_by_provider=false` (padrão)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `domain_id` | integer | ID do domínio |
| `domain_name` | string | Nome do domínio |
| `domain_slug` | string | Slug do domínio |
| `domain_total_requests` | integer | Total de requisições do domínio neste estado |
| `percentage_of_domain` | float | Porcentagem que este provider representa do total do domínio neste estado (%) |
| `provider_total_requests` | integer | Total de requisições do provider no estado (soma de todos os domínios) |
| `percentage_of_provider_in_state` | float | Porcentagem que esta row (domínio+provider) representa do total do provider no estado (%) |

#### Campos quando `aggregate_by_provider=true`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `domains` | string | Lista de domínios agregados no formato "id:nome, id:nome" |
| `domains_count` | integer | Quantidade de domínios agregados |
| `provider_total_requests` | integer | Total de requisições do provider (soma de todos os domínios) |
| `percentage_of_state` | float | Porcentagem que este provider representa do total do estado (%) |

## 📊 Entendendo o Success Rate (Taxa de Sucesso)

O campo `avg_success_rate` no ranking representa a **taxa de sucesso média das validações de endereço** realizadas pelo provider no estado específico.

### O que significa?

**Success Rate = Porcentagem de requisições bem-sucedidas**

- **Valores:** 0 a 100 (em porcentagem)
- **Cálculo:** `(requisições bem-sucedidas / total de requisições) × 100`
- **No ranking:** Média aritmética dos `success_rate` de todos os relatórios do provider no estado

### Exemplo prático:

Se um provider (ex: Spectrum) teve:
- **Relatório 1:** 100 requisições, 95 bem-sucedidas → `success_rate = 95%`
- **Relatório 2:** 200 requisições, 180 bem-sucedidas → `success_rate = 90%`
- **Relatório 3:** 150 requisições, 142 bem-sucedidas → `success_rate = 94.67%`

**No ranking:** `avg_success_rate = (95 + 90 + 94.67) / 3 = 93.22%`

### O que é uma "requisição bem-sucedida"?

No contexto de validação de endereços, uma requisição é considerada **bem-sucedida** quando:
- ✅ O endereço foi validado corretamente
- ✅ Os dados foram retornados sem erros
- ✅ A resposta da API foi válida e completa

Uma requisição **falha** quando:
- ❌ Erro na validação do endereço
- ❌ Timeout ou erro de conexão
- ❌ Dados inválidos ou incompletos retornados
- ❌ Erro na API de validação

### Interpretação dos valores:

| Success Rate | Qualidade | Significado |
|--------------|-----------|-------------|
| 95-100% | Excelente | Provider muito confiável, quase todas as requisições são bem-sucedidas |
| 85-94% | Boa | Provider confiável, maioria das requisições são bem-sucedidas |
| 75-84% | Média | Provider aceitável, mas com algumas falhas |
| 50-74% | Baixa | Provider com muitas falhas, pode precisar de atenção |
| 0-49% | Muito Baixa | Provider com problemas significativos |

### Importante:

- O `avg_success_rate` no ranking é uma **média ponderada** considerando todos os relatórios do período selecionado
- Valores mais altos indicam providers mais confiáveis para validação de endereços
- Este é um dos critérios de ordenação disponíveis (`sort_by=success_rate`)

## Respostas de Erro

### 400 - Bad Request (state_id ausente)
```json
{
  "success": false,
  "message": "state_id parameter is required"
}
```

### 400 - Bad Request (sort_by inválido)
```json
{
  "success": false,
  "message": "Invalid sort_by parameter. Must be one of: total_requests, success_rate, avg_speed, total_reports"
}
```

### 400 - Bad Request (period inválido)
```json
{
  "success": false,
  "message": "Invalid period parameter. Must be one of: today, yesterday, last_week, last_month, last_year, all_time"
}
```

### 500 - Internal Server Error
```json
{
  "success": false,
  "message": "Error getting provider ranking by state",
  "error": "Error message (only in debug mode)"
}
```

## Exemplos de Uso

### Exemplo 0: Buscar lista de estados (primeiro passo)
```javascript
// Vue.js / Nuxt
// 1. Buscar lista de estados
const statesResponse = await $fetch('/api/states', {
  method: 'GET'
  // Não precisa de autenticação
})

if (statesResponse.success) {
  const states = statesResponse.data
  // states = [{ id: 1, code: "AL", name: "Alabama" }, ...]
  
  // Encontrar estado desejado
  const california = states.find(s => s.code === 'CA')
  const californiaId = california.id // Usar este ID na rota de ranking
}
```

### Exemplo 1: Ranking de todos os providers no estado CA (California)
```javascript
// Vue.js / Nuxt
const response = await $fetch('/api/admin/reports/global/provider-ranking-by-state', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5, // ID do estado CA
    period: 'last_month',
    sort_by: 'total_requests'
  }
})

if (response.success) {
  const ranking = response.data.ranking
  // ranking[0] = provider com mais requisições no estado CA
}
```

### Exemplo 2: Filtrar por provider específico
```javascript
const response = await $fetch('/api/admin/reports/global/provider-ranking-by-state', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5,
    provider_id: 8, // Apenas AT&T
    period: 'all_time',
    sort_by: 'avg_speed'
  }
})
```

### Exemplo 3: Usando range de datas customizado (sem `period`)
```javascript
// Quando não fornecer 'period', você pode usar date_from e date_to
const response = await $fetch('/api/admin/reports/global/provider-ranking-by-state', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5,
    // Não fornecer 'period' - usar datas customizadas
    date_from: '2025-11-01',
    date_to: '2025-11-30',
    sort_by: 'success_rate'
  }
})
```

### Exemplo 3b: Usando seletor de data customizado (recomendado)
```javascript
// Para período customizado, use APENAS date_from e date_to (sem period)
// Esta é a forma mais limpa e intuitiva
const response = await $fetch('/api/admin/reports/global/provider-ranking-by-state', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5,
    // Não incluir 'period' - apenas usar date_from e date_to
    date_from: '2025-10-29',
    date_to: '2025-11-29',
    sort_by: 'avg_speed'
  }
})

// ✅ Formato recomendado para período customizado: apenas date_from e date_to
```

### Exemplo 3c: Usando `period=all_time` com datas customizadas (alternativa)
```javascript
// Opcional: também funciona com period=all_time + datas customizadas
// Mas não é necessário - usar apenas date_from/date_to é mais simples
const response = await $fetch('/api/admin/reports/global/provider-ranking-by-state', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5,
    period: 'all_time',  // Opcional - datas customizadas têm prioridade
    date_from: '2025-10-29',
    date_to: '2025-11-29',
    sort_by: 'avg_speed'
  }
})

// ⚠️ Funciona, mas não é necessário. Prefira usar apenas date_from/date_to
```

### Exemplo 3d: Períodos pré-definidos (não permitem sobrescrever com datas)
```javascript
// ⚠️ Para períodos diferentes de all_time, date_from/date_to são ignorados
const response = await $fetch('/api/admin/reports/global/provider-ranking-by-state', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5,
    period: 'last_month',  // Este período será usado
    date_from: '2025-11-01',  // ⚠️ IGNORADO (só funciona com all_time)
    date_to: '2025-11-30'  // ⚠️ IGNORADO (só funciona com all_time)
  }
})
// Resultado: Usa last_month, ignora date_from/date_to
```

### Exemplo 4: Composables Vue 3 (com busca de estados)
```javascript
// composables/useStates.js
export const useStates = () => {
  const states = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchStates = async () => {
    loading.value = true
    error.value = null
    
    try {
      const response = await $fetch('/api/states', {
        method: 'GET'
      })
      
      if (response.success) {
        states.value = response.data
        return response.data
      } else {
        throw new Error(response.message)
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    states,
    loading,
    error,
    fetchStates
  }
}

// composables/useProviderRankingByState.js
```javascript
// composables/useProviderRankingByState.js
export const useProviderRankingByState = () => {
  const ranking = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchRanking = async (stateId, filters = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const params = {
        state_id: stateId,
        ...filters
      }
      
      const response = await $fetch('/api/admin/reports/global/provider-ranking-by-state', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${useAuth().token.value}`
        },
        params
      })
      
      if (response.success) {
        ranking.value = response.data.ranking
        return response.data
      } else {
        throw new Error(response.message)
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    ranking,
    loading,
    error,
    fetchRanking
  }
}
```

### Exemplo 5: Uso no componente (completo com período customizado)
```vue
<template>
  <div>
    <!-- Seleção de Estado -->
    <select v-model="selectedStateId" @change="loadRanking" :disabled="statesLoading">
      <option value="">Selecione um estado</option>
      <option v-for="state in states" :key="state.id" :value="state.id">
        {{ state.name }} ({{ state.code }})
      </option>
    </select>

    <!-- Loading states -->
    <div v-if="statesLoading">Carregando estados...</div>

    <!-- Seleção de Período -->
    <div>
      <label>Período:</label>
      <select v-model="selectedPeriod" @change="handlePeriodChange">
        <option value="">Selecione um período</option>
        <option value="today">Hoje</option>
        <option value="yesterday">Ontem</option>
        <option value="last_week">Últimos 7 dias</option>
        <option value="last_month">Últimos 30 dias</option>
        <option value="last_year">Últimos 365 dias</option>
        <option value="all_time">Todo o histórico</option>
        <option value="custom">Período Customizado</option>
      </select>
    </div>

    <!-- Seletores de Data Customizada (mostrar apenas quando custom) -->
    <div v-if="selectedPeriod === 'custom'">
      <label>Data Inicial:</label>
      <input type="date" v-model="customDateFrom" @change="loadRanking" />
      
      <label>Data Final:</label>
      <input type="date" v-model="customDateTo" @change="loadRanking" />
    </div>

    <select v-model="sortBy" @change="loadRanking">
      <option value="total_requests">Total de Requisições</option>
      <option value="success_rate">Taxa de Sucesso</option>
      <option value="avg_speed">Velocidade Média</option>
    </select>

    <table v-if="!loading && ranking.length > 0">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Domínio</th>
          <th>Provider</th>
          <th>Requisições</th>
          <th>% do Domínio</th>
          <th>Taxa de Sucesso</th>
          <th>Velocidade Média</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in ranking" :key="`${item.domain_id}-${item.provider_id}`">
          <td>{{ item.rank }}</td>
          <td>{{ item.domain_name }}</td>
          <td>{{ item.provider_name }}</td>
          <td>{{ item.total_requests.toLocaleString() }}</td>
          <td>{{ item.percentage_of_domain }}%</td>
          <td>{{ item.avg_success_rate }}%</td>
          <td>{{ item.avg_speed }} Mbps</td>
        </tr>
      </tbody>
    </table>

    <div v-if="loading">Carregando...</div>
    <div v-if="error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useStates } from '@/composables/useStates'
import { useProviderRankingByState } from '@/composables/useProviderRankingByState'

// Estados
const { states, loading: statesLoading, error: statesError, fetchStates } = useStates()

// Ranking
const { ranking, loading: rankingLoading, error: rankingError, fetchRanking } = useProviderRankingByState()

const selectedStateId = ref(null)
const sortBy = ref('total_requests')
const selectedPeriod = ref('last_month')
const customDateFrom = ref('')
const customDateTo = ref('')

const handlePeriodChange = () => {
  // Limpar datas customizadas quando mudar para período pré-definido
  if (selectedPeriod.value !== 'custom') {
    customDateFrom.value = ''
    customDateTo.value = ''
    loadRanking()
  }
}

const loadRanking = async () => {
  if (!selectedStateId.value) return
  
  const filters = {
    sort_by: sortBy.value
  }
  
  // Se período customizado, usar apenas date_from e date_to (sem period)
  if (selectedPeriod.value === 'custom') {
    if (customDateFrom.value && customDateTo.value) {
      // Usar apenas datas customizadas, sem incluir 'period'
      filters.date_from = customDateFrom.value
      filters.date_to = customDateTo.value
      // Não incluir 'period' - apenas date_from e date_to
    } else {
      // Se custom mas datas não preenchidas, não buscar ainda
      return
    }
  } else if (selectedPeriod.value) {
    // Usar período pré-definido (não incluir date_from/date_to)
    filters.period = selectedPeriod.value
  }
  
  await fetchRanking(selectedStateId.value, filters)
}

onMounted(async () => {
  // Carregar lista de estados ao montar o componente
  await fetchStates()
})
</script>
```

## Notas Importantes

1. **Dados Precisos**: Este endpoint usa dados precisos da tabela `report_state_providers`, não aproximações. Os dados são exatos para cada combinação domínio-provider-estado.

2. **Filtro por Domínios Acessíveis**: O endpoint automaticamente filtra apenas os domínios que o admin autenticado tem permissão para acessar.

3. **Porcentagem**: O campo `percentage_of_domain` mostra qual porcentagem do total de requisições do domínio naquele estado é representada por aquele provider específico.

4. **Technology**: O campo `technology` sempre será `null` neste endpoint, pois os dados vêm de `report_state_providers` que não armazena tecnologia separadamente.

5. **Ordenação**: A ordenação é feita no servidor. O campo `rank` reflete a posição após a ordenação.

6. **Períodos**: 
   - Use `period` para períodos pré-definidos (`today`, `yesterday`, `last_week`, `last_month`, `last_year`, `all_time`)
   - Use `date_from`/`date_to` (sem `period`) para períodos customizados
   - **Especial:** Quando `period=all_time` E `date_from`/`date_to` forem fornecidos, as datas customizadas têm prioridade (permite limitar o período mesmo com `all_time`)
   - Para outros períodos pré-definidos, `period` sobrescreve `date_from` e `date_to`
   - Ambos `date_from` e `date_to` devem ser fornecidos juntos quando usar período customizado
   - Formato de data: `YYYY-MM-DD` (ex: `2025-11-01`)
   - `date_from` deve ser anterior ou igual a `date_to`

