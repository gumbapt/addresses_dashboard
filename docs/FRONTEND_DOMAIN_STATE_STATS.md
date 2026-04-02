# Domain State Statistics - Documentação Frontend

## 📡 Endpoint

```
GET /api/admin/reports/domain/{domainId}/state-stats
```

---

## 📦 Dependências (para gráficos)

Se você planeja usar o gráfico de barras das cidades, instale o Chart.js:

```bash
npm install chart.js
# ou
yarn add chart.js
```

---

## 📡 Endpoint

## Autenticação

Requer autenticação de admin via Sanctum. O token deve ser enviado no header:
```
Authorization: Bearer {token}
```

**Nota:** O endpoint também verifica se o admin tem acesso ao domínio através do middleware `check.domain.access`.

---

## Parâmetros de Query

| Parâmetro | Tipo | Obrigatório | Descrição | Exemplo |
|-----------|------|-------------|-----------|---------|
| `state_id` | integer | ✅ Sim | ID do estado para filtrar | `5` |
| `period` | string | ❌ Não | Período pré-definido. Para períodos diferentes de `all_time`, sobrescreve `date_from` e `date_to`. Para `all_time`, permite usar `date_from`/`date_to` para limitar o período. | `today`, `yesterday`, `last_week`, `last_month`, `last_year`, `all_time` |
| `date_from` | string | ❌ Não | Data inicial (YYYY-MM-DD). Usado quando `period` não é fornecido ou quando `period=all_time`. Requer `date_to` | `2025-11-01` |
| `date_to` | string | ❌ Não | Data final (YYYY-MM-DD). Usado quando `period` não é fornecido ou quando `period=all_time`. Requer `date_from` | `2025-11-30` |
| `sort_by` | string | ❌ Não | Critério de ordenação para providers. Padrão: `total_count` | `total_count`, `success_rate`, `avg_speed` |
| `cities_limit` | integer | ❌ Não | Número de cidades principais a retornar. Padrão: `10`. Máximo: `100`. Controla quantas cidades aparecem em `top_cities`, `cities_chart_data` e `cities_detailed_charts` | `10`, `20`, `50` |

### Valores Aceitos para `sort_by`:
- `total_count` / `total_requests` - Ordena por total de requisições (padrão)
- `success_rate` - Ordena por taxa de sucesso média
- `avg_speed` - Ordena por velocidade média

### Valores Aceitos para `period`:
- `today` - Apenas hoje
- `yesterday` - Apenas ontem
- `last_week` - Últimos 7 dias
- `last_month` - Últimos 30 dias
- `last_year` - Últimos 365 dias
- `all_time` - Todos os dados disponíveis

---

## ⚙️ Comportamento de Períodos:

**Forma Recomendada:**
- **Período pré-definido:** Use apenas `period` (ex: `period=last_month`)
- **Período customizado:** Use apenas `date_from` e `date_to` (sem `period`)

**Prioridade:**
1. Se `period` for fornecido (exceto `all_time`) → Usa o período pré-definido (calcula datas automaticamente, ignora `date_from`/`date_to` se fornecidos)
2. Se `period` não for fornecido E `date_from`/`date_to` forem fornecidos → Usa as datas customizadas (forma recomendada para período customizado)
3. Se `period=all_time` E `date_from`/`date_to` forem fornecidos → Usa as datas customizadas (funciona, mas não é necessário - prefira usar apenas date_from/date_to)
4. Se nenhum for fornecido → Sem filtro de data (all time)

---

## 📊 Resposta de Sucesso (200)

```json
{
  "success": true,
  "data": {
    "domain": {
      "id": 1,
      "name": "zip.50g.io"
    },
    "state": {
      "id": 5,
      "code": "CA",
      "name": "California"
    },
    "period": {
      "total_reports": 10,
      "first_report": "2025-11-01",
      "last_report": "2025-11-30",
      "days_covered": 30,
      "date_from": "2025-11-01",
      "date_to": "2025-11-30"
    },
    "kpis": {
      "total_requests": 12500,
      "success_rate": 95.5,
      "daily_average": 416,
      "unique_providers": 8
    },
    "provider_distribution": [
      {
        "provider_id": 5,
        "name": "Spectrum",
        "slug": "spectrum",
        "total_count": 3500,
        "percentage": 28.0,
        "avg_success_rate": 96.2,
        "avg_speed": 150.5
      },
      {
        "provider_id": 8,
        "name": "AT&T",
        "slug": "att",
        "total_count": 2800,
        "percentage": 22.4,
        "avg_success_rate": 94.8,
        "avg_speed": 145.3
      }
    ],
    "top_cities": [
      {
        "city_id": 123,
        "name": "Los Angeles",
        "total_requests": 4500,
        "report_count": 10
      },
      {
        "city_id": 124,
        "name": "San Francisco",
        "total_requests": 3200,
        "report_count": 10
      }
    ],
    "cities_chart_data": {
      "labels": ["Los Angeles", "San Francisco", "San Diego", "Sacramento", "Oakland"],
      "datasets": [
        {
          "label": "Requisições por Cidade",
          "data": [4500, 3200, 2800, 1500, 1200],
          "backgroundColor": ["#3B82F6", "#2563EB", "#1D4ED8", "#1E40AF", "#1E3A8A"],
          "borderColor": ["#2563EB", "#1D4ED8", "#1E40AF", "#1E3A8A", "#172554"],
          "borderWidth": 1
        }
      ],
      "percentages": [36.0, 25.6, 22.4, 12.0, 9.6],
      "total": 12500,
      "raw_data": [
        {
          "city_id": 123,
          "name": "Los Angeles",
          "total_requests": 4500,
          "percentage": 36.0,
          "report_count": 10
        },
        {
          "city_id": 124,
          "name": "San Francisco",
          "total_requests": 3200,
          "percentage": 25.6,
          "report_count": 10
        }
      ]
    },
    "cities_detailed_charts": [
      {
        "city_id": 123,
        "city_name": "Los Angeles",
        "total_requests": 4500,
        "providers_chart": {
          "labels": ["Spectrum", "AT&T", "Verizon"],
          "datasets": [{
            "label": "Requisições por Provider",
            "data": [2000, 1500, 1000],
            "backgroundColor": ["#3B82F6", "#2563EB", "#1D4ED8"],
            "borderColor": ["#2563EB", "#1D4ED8", "#1E40AF"],
            "borderWidth": 1
          }],
          "percentages": [44.4, 33.3, 22.2],
          "total": 4500,
          "raw_data": [
            {
              "provider_id": 5,
              "name": "Spectrum",
              "total_count": 2000,
              "percentage": 44.4
            }
          ]
        },
        "technologies_chart": {
          "labels": ["Fiber", "Cable", "DSL"],
          "datasets": [{
            "label": "Requisições por Tecnologia",
            "data": [2500, 1500, 500],
            "backgroundColor": ["#10B981", "#059669", "#047857"],
            "borderColor": ["#059669", "#047857", "#065F46"],
            "borderWidth": 1
          }],
          "percentages": [55.6, 33.3, 11.1],
          "total": 4500,
          "raw_data": [
            {
              "technology": "Fiber",
              "total_count": 2500,
              "percentage": 55.6
            }
          ]
        }
      }
    ],
    "top_zip_codes": [
      {
        "zip_code_id": 1001,
        "code": "90001",
        "total_requests": 850,
        "report_count": 10
      }
    ],
    "hourly_distribution": [
      {
        "hour": "00:00",
        "count": 120,
        "normalized": 0.3
      },
      {
        "hour": "01:00",
        "count": 95,
        "normalized": 0.24
      }
    ],
    "technology_distribution": [
      {
        "technology": "Fiber",
        "total_count": 6500,
        "percentage": 52.0
      },
      {
        "technology": "Cable",
        "total_count": 4200,
        "percentage": 33.6
      }
    ],
    "state_stats": {
      "total_requests": 12500,
      "avg_success_rate": 95.5,
      "avg_speed": 148.2
    },
    "filters": {
      "cities_limit": 10,
      "sort_by": "total_count"
    },
    "daily_trends": [
      {
        "date": "2025-11-01",
        "report_id": 101,
        "total_requests": 400,
        "success_rate": 95.2,
        "failed_requests": 19,
        "avg_requests_per_hour": 16.67
      },
      {
        "date": "2025-11-02",
        "report_id": 102,
        "total_requests": 420,
        "success_rate": 96.1,
        "failed_requests": 16,
        "avg_requests_per_hour": 17.5
      }
    ]
  }
}
```

---

## 📡 Exemplos de Uso

### Exemplo 1: Buscar estatísticas com período pré-definido
```javascript
// Vue.js / Nuxt
const response = await $fetch('/api/admin/reports/domain/1/state-stats', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5, // California
    period: 'last_month',
    sort_by: 'success_rate'
  }
})

if (response.success) {
  const stats = response.data
  console.log('KPIs:', stats.kpis)
  console.log('Top Providers:', stats.provider_distribution)
}
```

### Exemplo 2: Buscar estatísticas com período customizado
```javascript
// Para período customizado, use APENAS date_from e date_to (sem period)
const response = await $fetch('/api/admin/reports/domain/1/state-stats', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  params: {
    state_id: 5,
    date_from: '2025-10-29',
    date_to: '2025-11-29',
    sort_by: 'avg_speed',
    cities_limit: 20 // Top 20 cidades (aumentar para ver mais cidades)
  }
})
```

### Exemplo 3: Composables Vue 3 (recomendado)
```javascript
// composables/useDomainStateStats.js
export const useDomainStateStats = () => {
  const stats = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchStats = async (domainId, stateId, filters = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const params = {
        state_id: stateId,
        cities_limit: filters.citiesLimit || 10, // Padrão: 10 cidades
        ...filters
      }
      
      const response = await $fetch(`/api/admin/reports/domain/${domainId}/state-stats`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${useAuth().token.value}`
        },
        params
      })
      
      if (response.success) {
        stats.value = response.data
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
    stats,
    loading,
    error,
    fetchStats
  }
}
```

### Exemplo 4: Uso no componente (completo)
```vue
<template>
  <div>
    <!-- Seleção de Estado -->
    <select v-model="selectedStateId" @change="loadStats" :disabled="statesLoading">
      <option value="">Selecione um estado</option>
      <option v-for="state in states" :key="state.id" :value="state.id">
        {{ state.name }} ({{ state.code }})
      </option>
    </select>

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

    <!-- Seletores de Data Customizada -->
    <div v-if="selectedPeriod === 'custom'">
      <label>Data Inicial:</label>
      <input type="date" v-model="customDateFrom" @change="loadStats" />
      
      <label>Data Final:</label>
      <input type="date" v-model="customDateTo" @change="loadStats" />
    </div>

    <!-- Ordenação -->
    <select v-model="sortBy" @change="loadStats">
      <option value="total_count">Total de Requisições</option>
      <option value="success_rate">Taxa de Sucesso</option>
      <option value="avg_speed">Velocidade Média</option>
    </select>

    <!-- Limite de Cidades -->
    <div>
      <label>Número de Cidades:</label>
      <select v-model="citiesLimit" @change="loadStats">
        <option :value="5">Top 5</option>
        <option :value="10">Top 10 (padrão)</option>
        <option :value="20">Top 20</option>
        <option :value="50">Top 50</option>
        <option :value="100">Top 100</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading">Carregando estatísticas...</div>
    
    <!-- Erro -->
    <div v-if="error" class="error">{{ error }}</div>

    <!-- KPIs -->
    <div v-if="stats && !loading">
      <div class="kpis">
        <div class="kpi">
          <h3>Total de Requisições</h3>
          <p>{{ stats.kpis.total_requests.toLocaleString() }}</p>
        </div>
        <div class="kpi">
          <h3>Taxa de Sucesso</h3>
          <p>{{ stats.kpis.success_rate }}%</p>
        </div>
        <div class="kpi">
          <h3>Média Diária</h3>
          <p>{{ stats.kpis.daily_average.toLocaleString() }}</p>
        </div>
        <div class="kpi">
          <h3>Providers Únicos</h3>
          <p>{{ stats.kpis.unique_providers }}</p>
        </div>
      </div>

      <!-- Distribuição de Providers -->
      <div class="providers">
        <h2>Top Providers no Estado</h2>
        <table>
          <thead>
            <tr>
              <th>Provider</th>
              <th>Requisições</th>
              <th>%</th>
              <th>Taxa de Sucesso</th>
              <th>Velocidade Média</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="provider in stats.provider_distribution" :key="provider.provider_id">
              <td>{{ provider.name }}</td>
              <td>{{ provider.total_count.toLocaleString() }}</td>
              <td>{{ provider.percentage }}%</td>
              <td>{{ provider.avg_success_rate }}%</td>
              <td>{{ provider.avg_speed }} Mbps</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Top Cidades -->
      <div class="cities">
        <h2>Top Cidades</h2>
        <table>
          <thead>
            <tr>
              <th>Cidade</th>
              <th>Requisições</th>
              <th>Relatórios</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="city in stats.top_cities" :key="city.city_id">
              <td>{{ city.name }}</td>
              <td>{{ city.total_requests.toLocaleString() }}</td>
              <td>{{ city.report_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Gráfico de Barras - Cidades (da mais comum para menos comum) -->
      <div class="cities-chart">
        <h2>Distribuição de Requisições por Cidade</h2>
        
        <!-- Opção 1: Usando Chart.js -->
        <div class="chart-container">
          <canvas ref="citiesChartCanvas"></canvas>
        </div>
        
        <!-- Opção 2: Gráfico de barras manual (CSS) -->
        <div class="cities-chart-manual" v-if="stats.cities_chart_data.raw_data.length > 0">
          <div 
            v-for="(city, index) in stats.cities_chart_data.raw_data" 
            :key="city.city_id"
            class="city-bar-item"
          >
            <div class="city-bar-label">
              <span class="city-rank">#{{ index + 1 }}</span>
              <span class="city-name">{{ city.name }}</span>
              <span class="city-stats">
                {{ city.total_requests.toLocaleString() }} ({{ city.percentage }}%)
              </span>
            </div>
            <div class="city-bar-container">
              <div 
                class="city-bar-fill" 
                :style="{ 
                  width: city.percentage + '%',
                  backgroundColor: stats.cities_chart_data.datasets[0].backgroundColor[index % stats.cities_chart_data.datasets[0].backgroundColor.length]
                }"
              ></div>
            </div>
          </div>
        </div>
        
        <!-- Lista de cidades abaixo do gráfico -->
        <div class="cities-list">
          <div 
            v-for="(city, index) in stats.cities_chart_data.raw_data" 
            :key="city.city_id"
            class="city-item"
          >
            <span class="city-rank">#{{ index + 1 }}</span>
            <span class="city-name">{{ city.name }}</span>
            <span class="city-requests">{{ city.total_requests.toLocaleString() }}</span>
            <span class="city-percentage">({{ city.percentage }}%)</span>
          </div>
        </div>
      </div>

      <!-- Gráficos Detalhados por Cidade -->
      <div class="cities-detailed-charts" v-if="stats.cities_detailed_charts && stats.cities_detailed_charts.length > 0">
        <h2>Análise Detalhada por Cidade</h2>
        
        <div 
          v-for="cityData in stats.cities_detailed_charts" 
          :key="cityData.city_id"
          class="city-detailed-chart"
        >
          <h3>{{ cityData.city_name }}</h3>
          <p class="city-total">Total: {{ cityData.total_requests.toLocaleString() }} requisições</p>
          
          <!-- Providers Chart -->
          <div class="chart-section">
            <h4>Providers</h4>
            <div class="chart-container-small">
              <canvas :ref="el => setCityProviderChartRef(el, cityData.city_id, cityData.providers_chart)"></canvas>
            </div>
          </div>
          
          <!-- Technologies Chart -->
          <div class="chart-section">
            <h4>Tecnologias</h4>
            <div class="chart-container-small">
              <canvas :ref="el => setCityTechChartRef(el, cityData.city_id, cityData.technologies_chart)"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Top CEPs -->
      <div class="zip-codes">
        <h2>Top CEPs</h2>
        <table>
          <thead>
            <tr>
              <th>CEP</th>
              <th>Requisições</th>
              <th>Relatórios</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="zip in stats.top_zip_codes" :key="zip.zip_code_id">
              <td>{{ zip.code }}</td>
              <td>{{ zip.total_requests.toLocaleString() }}</td>
              <td>{{ zip.report_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Distribuição de Tecnologias -->
      <div class="technologies">
        <h2>Distribuição de Tecnologias</h2>
        <div v-for="tech in stats.technology_distribution" :key="tech.technology">
          <div class="tech-bar">
            <span>{{ tech.technology }}</span>
            <div class="bar">
              <div class="fill" :style="{ width: tech.percentage + '%' }"></div>
            </div>
            <span>{{ tech.percentage }}% ({{ tech.total_count.toLocaleString() }})</span>
          </div>
        </div>
      </div>

      <!-- Tendências Diárias -->
      <div class="trends">
        <h2>Tendências Diárias</h2>
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Requisições</th>
              <th>Taxa de Sucesso</th>
              <th>Falhas</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="trend in stats.daily_trends" :key="trend.date">
              <td>{{ trend.date }}</td>
              <td>{{ trend.total_requests.toLocaleString() }}</td>
              <td>{{ trend.success_rate }}%</td>
              <td>{{ trend.failed_requests }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useStates } from '@/composables/useStates'
import { useDomainStateStats } from '@/composables/useDomainStateStats'
// Se estiver usando Chart.js
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

// Props
const props = defineProps({
  domainId: {
    type: Number,
    required: true
  }
})

// Estados
const { states, loading: statesLoading, fetchStates } = useStates()
const { stats, loading, error, fetchStats } = useDomainStateStats()

const selectedStateId = ref(null)
const sortBy = ref('total_count')
const selectedPeriod = ref('last_month')
const customDateFrom = ref('')
const customDateTo = ref('')
const citiesLimit = ref(10) // Número de cidades a mostrar
const citiesChartCanvas = ref(null)
let citiesChart = null
const cityCharts = ref({}) // Armazenar gráficos por cidade

const handlePeriodChange = () => {
  // Limpar datas customizadas quando mudar para período pré-definido
  if (selectedPeriod.value !== 'custom') {
    customDateFrom.value = ''
    customDateTo.value = ''
    loadStats()
  }
}

const loadStats = async () => {
  if (!selectedStateId.value) return
  
  const filters = {
    sort_by: sortBy.value,
    cities_limit: citiesLimit.value
  }
  
  // Se período customizado, usar apenas date_from e date_to (sem period)
  if (selectedPeriod.value === 'custom') {
    if (customDateFrom.value && customDateTo.value) {
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
  
  await fetchStats(props.domainId, selectedStateId.value, filters)
}

// Criar gráfico de barras quando os dados mudarem
watch(() => stats.value?.cities_chart_data, async (chartData) => {
  if (chartData && chartData.labels.length > 0) {
    await nextTick()
    createCitiesChart(chartData)
  }
}, { deep: true })

const createCitiesChart = (chartData) => {
  // Destruir gráfico anterior se existir
  if (citiesChart) {
    citiesChart.destroy()
  }

  if (!citiesChartCanvas.value) return

  const ctx = citiesChartCanvas.value.getContext('2d')
  
  citiesChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData.labels,
      datasets: chartData.datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const index = context.dataIndex
              const value = context.parsed.y
              const percentage = chartData.percentages[index]
              return `${value.toLocaleString()} requisições (${percentage}%)`
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return value.toLocaleString()
            }
          }
        },
        x: {
          ticks: {
            maxRotation: 45,
            minRotation: 45
          }
        }
      },
      indexAxis: 'y' // Barras horizontais (mais comum à esquerda)
    }
  })
}

onMounted(async () => {
  // Carregar lista de estados ao montar o componente
  await fetchStates()
})

// Funções para criar gráficos por cidade
const setCityProviderChartRef = (el, cityId, chartData) => {
  if (!el || !chartData || chartData.labels.length === 0) return
  
  nextTick(() => {
    const chartKey = `provider-${cityId}`
    if (cityCharts.value[chartKey]) {
      cityCharts.value[chartKey].destroy()
    }
    
    const ctx = el.getContext('2d')
    cityCharts.value[chartKey] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: chartData.labels,
        datasets: chartData.datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const index = context.dataIndex
                const value = context.parsed.y
                const percentage = chartData.percentages[index]
                return `${value.toLocaleString()} requisições (${percentage}%)`
              }
            }
          }
        },
        scales: {
          y: { beginAtZero: true },
          x: { ticks: { maxRotation: 45, minRotation: 45 } }
        }
      }
    })
  })
}

const setCityTechChartRef = (el, cityId, chartData) => {
  if (!el || !chartData || chartData.labels.length === 0) return
  
  nextTick(() => {
    const chartKey = `tech-${cityId}`
    if (cityCharts.value[chartKey]) {
      cityCharts.value[chartKey].destroy()
    }
    
    const ctx = el.getContext('2d')
    cityCharts.value[chartKey] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: chartData.labels,
        datasets: chartData.datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const index = context.dataIndex
                const value = context.parsed.y
                const percentage = chartData.percentages[index]
                return `${value.toLocaleString()} requisições (${percentage}%)`
              }
            }
          }
        },
        scales: {
          y: { beginAtZero: true },
          x: { ticks: { maxRotation: 45, minRotation: 45 } }
        }
      }
    })
  })
}

// Limpar gráficos ao desmontar
onBeforeUnmount(() => {
  if (citiesChart) {
    citiesChart.destroy()
  }
  Object.values(cityCharts.value).forEach(chart => {
    if (chart) chart.destroy()
  })
})
</script>

<style scoped>
.kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.kpi {
  padding: 1rem;
  background: #f5f5f5;
  border-radius: 8px;
}

.kpi h3 {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  color: #666;
}

.kpi p {
  margin: 0;
  font-size: 1.5rem;
  font-weight: bold;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 2rem;
}

table th,
table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

table th {
  background: #f5f5f5;
  font-weight: 600;
}

.tech-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.bar {
  flex: 1;
  height: 24px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.fill {
  height: 100%;
  background: #4CAF50;
  transition: width 0.3s;
}

.error {
  color: #d32f2f;
  padding: 1rem;
  background: #ffebee;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.chart-container {
  height: 500px;
  margin-bottom: 2rem;
}

.cities-list {
  margin-top: 1rem;
}

.city-item {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  background: #f9fafb;
  border-radius: 4px;
  gap: 1rem;
}

.city-rank {
  font-weight: bold;
  color: #666;
  min-width: 40px;
}

.city-name {
  flex: 1;
  font-weight: 500;
}

.city-requests {
  font-weight: 600;
  color: #2563EB;
}

.city-percentage {
  color: #666;
  font-size: 0.875rem;
}

/* Estilos para gráfico manual */
.cities-chart-manual {
  margin-top: 2rem;
}

.city-bar-item {
  margin-bottom: 1rem;
}

.city-bar-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.city-bar-container {
  width: 100%;
  height: 30px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  position: relative;
}

.city-bar-fill {
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 4px;
}

.city-stats {
  margin-left: auto;
  font-weight: 600;
  color: #2563EB;
}

/* Estilos para gráficos detalhados por cidade */
.cities-detailed-charts {
  margin-top: 3rem;
}

.city-detailed-chart {
  margin-bottom: 3rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.city-detailed-chart h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
}

.city-total {
  margin: 0 0 1.5rem 0;
  color: #6b7280;
  font-size: 0.875rem;
}

.chart-section {
  margin-bottom: 2rem;
}

.chart-section h4 {
  margin: 0 0 1rem 0;
  color: #4b5563;
  font-size: 1rem;
}

.chart-container-small {
  height: 300px;
}
</style>
```

---

## 🎯 Casos de Uso

### Caso 1: Dashboard de Estado Específico
Use este endpoint quando precisar mostrar estatísticas detalhadas de um domínio filtradas por estado específico, similar ao dashboard geral mas com foco em um estado.

### Caso 2: Comparação entre Estados
Você pode fazer múltiplas chamadas para diferentes estados e comparar os resultados no frontend.

### Caso 3: Análise de Performance por Estado
Use para analisar como o domínio performa em estados específicos, identificando problemas regionais ou oportunidades de melhoria.

---

## ⚠️ Notas Importantes

1. **Acesso ao Domínio**: O endpoint verifica automaticamente se o admin tem acesso ao domínio. Se não tiver, retornará erro 403.

2. **Gráfico de Cidades**: O campo `cities_chart_data` retorna dados formatados especificamente para gráficos de barras (compatível com Chart.js). Os dados estão ordenados da cidade mais comum (mais requisições) para a menos comum. O campo `raw_data` dentro de `cities_chart_data` contém os dados completos para uso adicional.

3. **Gráficos Detalhados por Cidade**: O campo `cities_detailed_charts` retorna dados de gráficos para cada uma das top 10 cidades, incluindo:
   - **Providers Chart**: Gráfico de barras mostrando a distribuição de providers na cidade
   - **Technologies Chart**: Gráfico de barras mostrando a distribuição de tecnologias na cidade
   - Cada cidade retorna dados formatados para Chart.js, prontos para uso
   - **Nota**: Os dados de providers por cidade são uma aproximação baseada nos reports que contêm essa cidade, pois não há uma tabela que cruza diretamente cidade + provider

4. **Filtro por Estado**: Os dados retornados são filtrados apenas para o estado especificado. Providers, cidades e CEPs mostrados são apenas aqueles relacionados ao estado.

5. **KPIs**: Os KPIs são calculados com base nos dados do estado específico, não do domínio inteiro.

6. **Ordenação**: A ordenação se aplica apenas à `provider_distribution`. Outros arrays mantêm suas ordenações padrão (por total_requests).

7. **Dados Precisos**: Este endpoint usa dados precisos das tabelas `report_states`, `report_state_providers`, etc., garantindo dados exatos para o estado.

8. **Performance**: Para estados com muitos dados, considere usar períodos menores para melhorar a performance.

9. **Limite de Cidades**: Use o parâmetro `cities_limit` para controlar quantas cidades são retornadas. O valor padrão é 10 (top 10 cidades principais). Você pode aumentar este valor para ver mais cidades. O limite máximo é 100. Este parâmetro afeta:
   - `top_cities` - Lista de cidades principais
   - `cities_chart_data` - Dados do gráfico de barras (ordenado da mais comum para menos comum)
   - `cities_detailed_charts` - Gráficos detalhados por cidade

---

## 🔄 Diferenças em Relação a Outros Endpoints

| Endpoint | Escopo | Foco |
|----------|--------|------|
| `/domain/{id}/dashboard` | Todo o domínio | Visão geral de todo o domínio |
| `/domain/{id}/aggregate` | Todo o domínio | Agregação completa |
| `/domain/{id}/state-stats` | Domínio + Estado | Estatísticas focadas em um estado específico |

---

## ✅ Testes Rápidos

```bash
# Com período pré-definido
curl "http://localhost:8000/api/admin/reports/domain/1/state-stats?state_id=5&period=last_month" \
  -H "Authorization: Bearer $TOKEN"

# Com período customizado
curl "http://localhost:8000/api/admin/reports/domain/1/state-stats?state_id=5&date_from=2025-11-01&date_to=2025-11-30&sort_by=success_rate" \
  -H "Authorization: Bearer $TOKEN"

# Com limite de cidades (top 5)
curl "http://localhost:8000/api/admin/reports/domain/1/state-stats?state_id=5&period=last_month&cities_limit=5" \
  -H "Authorization: Bearer $TOKEN"

# Com limite de cidades aumentado (top 50)
curl "http://localhost:8000/api/admin/reports/domain/1/state-stats?state_id=5&period=last_month&cities_limit=50" \
  -H "Authorization: Bearer $TOKEN"
```

---

**Status:** ✅ Pronto para uso  
**Versão API:** 1.0  
**Última Atualização:** 2025-11-25

