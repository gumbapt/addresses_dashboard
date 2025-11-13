# 🏆 Provider Ranking - Exemplos de Uso

## ✅ Endpoint Implementado

```
GET /api/admin/reports/global/provider-ranking
```

**Status:** ✅ Implementado e testado (8 testes passando)

---

## 📡 Query Parameters

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `provider_id` | int | Filtrar por provider específico | `?provider_id=1` |
| `technology` | string | Filtrar por tecnologia | `?technology=Fiber` |
| `date_from` | date | Data inicial | `?date_from=2025-11-01` |
| `date_to` | date | Data final | `?date_to=2025-11-30` |
| `sort_by` | string | Ordenar por | `?sort_by=success_rate` |
| `limit` | int | Limitar resultados | `?limit=10` |

**sort_by options:** `total_requests`, `success_rate`, `avg_speed`, `total_reports`

---

## 🎯 Exemplos Práticos

### **1. Top 10 Domains x Providers (Geral)**

```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

**Response:**
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
        "total_requests": 500,
        "avg_success_rate": 88.5,
        "avg_speed": 1200,
        "total_reports": 10,
        "period_start": "2025-11-01",
        "period_end": "2025-11-10",
        "days_covered": 10
      }
    ],
    "total_entries": 10,
    "filters": {
      "provider_id": null,
      "technology": null,
      "sort_by": "total_requests",
      "limit": 10
    }
  }
}
```

---

### **2. Top Domains usando Spectrum**

```bash
# Top domains que mais consultam Spectrum
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=15&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

**Use Case:**
- Mostrar quais sites mais pesquisam Spectrum
- Dashboard: "Top Spectrum Users"

---

### **3. Top Domains por AT&T**

```bash
# Assumindo que AT&T tem provider_id = 2
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=2&sort_by=success_rate" \
  -H "Authorization: Bearer $TOKEN"
```

**Use Case:**
- Ranking de qualidade para AT&T
- Ordenado por success_rate

---

### **4. Top Fiber Providers**

```bash
# Apenas tecnologia Fiber
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?technology=Fiber&limit=20" \
  -H "Authorization: Bearer $TOKEN"
```

**Use Case:**
- Mostrar apenas providers com Fiber
- Dashboard: "Fiber Provider Rankings"

---

### **5. Top Cable Providers com Alta Velocidade**

```bash
# Cable ordenado por velocidade
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?technology=Cable&sort_by=avg_speed&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

**Use Case:**
- Ranking de Cable providers por velocidade
- Dashboard: "Fastest Cable Providers"

---

### **6. Ranking do Último Mês**

```bash
# Novembro 2025
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?date_from=2025-11-01&date_to=2025-11-30&limit=20" \
  -H "Authorization: Bearer $TOKEN"
```

**Use Case:**
- Relatório mensal
- Comparar performance mês a mês

---

## 📊 Frontend - Componentes Sugeridos

### **A. Top Providers Dashboard**

```tsx
export function TopProvidersDashboard() {
  const [rankings, setRankings] = useState([]);
  const [filters, setFilters] = useState({
    provider_id: null,
    technology: null,
    sort_by: 'total_requests',
    limit: 10
  });
  
  useEffect(() => {
    loadRankings();
  }, [filters]);
  
  const loadRankings = async () => {
    const params = new URLSearchParams();
    if (filters.provider_id) params.append('provider_id', filters.provider_id);
    if (filters.technology) params.append('technology', filters.technology);
    params.append('sort_by', filters.sort_by);
    params.append('limit', filters.limit);
    
    const response = await fetch(
      `/api/admin/reports/global/provider-ranking?${params}`,
      { headers: { 'Authorization': `Bearer ${token}` }}
    );
    
    const data = await response.json();
    setRankings(data.data.ranking);
  };
  
  return (
    <div className="provider-rankings">
      <h2>🏆 Provider Rankings</h2>
      
      {/* Filtros */}
      <div className="filters">
        <select onChange={(e) => setFilters({...filters, technology: e.target.value || null})}>
          <option value="">All Technologies</option>
          <option value="Fiber">Fiber</option>
          <option value="Cable">Cable</option>
          <option value="DSL">DSL</option>
          <option value="Mobile">Mobile</option>
        </select>
        
        <select onChange={(e) => setFilters({...filters, sort_by: e.target.value})}>
          <option value="total_requests">Most Requests</option>
          <option value="success_rate">Best Success Rate</option>
          <option value="avg_speed">Fastest Speed</option>
          <option value="total_reports">Most Reports</option>
        </select>
        
        <input 
          type="number" 
          value={filters.limit}
          onChange={(e) => setFilters({...filters, limit: parseInt(e.target.value)})}
          min="5"
          max="50"
        />
      </div>
      
      {/* Tabela */}
      <table className="ranking-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Domain</th>
            <th>Provider</th>
            <th>Tech</th>
            <th>Requests</th>
            <th>Success Rate</th>
            <th>Avg Speed</th>
            <th>Reports</th>
          </tr>
        </thead>
        <tbody>
          {rankings.map(item => (
            <tr key={`${item.domain_id}-${item.provider_id}-${item.technology}`}>
              <td className="rank">
                {item.rank <= 3 && <span className="medal">🏅</span>}
                #{item.rank}
              </td>
              <td><strong>{item.domain_name}</strong></td>
              <td>{item.provider_name}</td>
              <td>
                <span className={`badge tech-${item.technology?.toLowerCase()}`}>
                  {item.technology}
                </span>
              </td>
              <td>{item.total_requests.toLocaleString()}</td>
              <td>{item.avg_success_rate.toFixed(1)}%</td>
              <td>{item.avg_speed.toFixed(0)} ms</td>
              <td>{item.total_reports}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

---

### **B. Provider Comparison Widget**

```tsx
export function ProviderComparisonWidget({ providerNames }) {
  const [comparison, setComparison] = useState([]);
  
  useEffect(() => {
    loadComparison();
  }, []);
  
  const loadComparison = async () => {
    // Buscar ID dos providers pelo nome
    const providers = await fetch('/api/admin/providers').then(r => r.json());
    
    const results = await Promise.all(
      providerNames.map(async (name) => {
        const provider = providers.data.find(p => p.name === name);
        if (!provider) return null;
        
        const response = await fetch(
          `/api/admin/reports/global/provider-ranking?provider_id=${provider.id}&limit=1`,
          { headers: { 'Authorization': `Bearer ${token}` }}
        );
        
        const data = await response.json();
        return {
          provider_name: name,
          top_domain: data.data.ranking[0]
        };
      })
    );
    
    setComparison(results.filter(Boolean));
  };
  
  return (
    <div className="provider-comparison">
      <h3>Provider Comparison</h3>
      <div className="comparison-grid">
        {comparison.map(item => (
          <div key={item.provider_name} className="provider-card">
            <h4>{item.provider_name}</h4>
            {item.top_domain && (
              <>
                <div className="top-domain">
                  Top: {item.top_domain.domain_name}
                </div>
                <div className="stats">
                  <span>{item.top_domain.total_requests.toLocaleString()} requests</span>
                  <span>{item.top_domain.avg_success_rate.toFixed(1)}% success</span>
                </div>
              </>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

// Uso:
<ProviderComparisonWidget providerNames={['Spectrum', 'AT&T', 'Verizon', 'Comcast']} />
```

---

## 📊 Dashboards Sugeridos

### **1. "Top Spectrum" - Dashboard Dedicado**

```jsx
export function TopSpectrumDashboard() {
  const [spectrumId, setSpectrumId] = useState(null);
  const [ranking, setRanking] = useState([]);
  
  useEffect(() => {
    // Buscar ID do Spectrum
    fetch('/api/admin/providers')
      .then(r => r.json())
      .then(data => {
        const spectrum = data.data.find(p => p.name === 'Spectrum');
        if (spectrum) {
          setSpectrumId(spectrum.id);
        }
      });
  }, []);
  
  useEffect(() => {
    if (spectrumId) {
      fetch(
        `/api/admin/reports/global/provider-ranking?provider_id=${spectrumId}&limit=20`,
        { headers: { 'Authorization': `Bearer ${token}` }}
      )
      .then(r => r.json())
      .then(data => setRanking(data.data.ranking));
    }
  }, [spectrumId]);
  
  return (
    <div className="top-spectrum">
      <h1>🏆 Top Spectrum Rankings</h1>
      <p>Domains with most Spectrum queries</p>
      
      <div className="ranking-cards">
        {ranking.map(item => (
          <div key={item.rank} className="rank-card">
            <div className="rank-badge">#{item.rank}</div>
            <div className="domain-name">{item.domain_name}</div>
            <div className="stats">
              <span>{item.total_requests.toLocaleString()} requests</span>
              <span>{item.technology}</span>
              <span>{item.avg_success_rate.toFixed(1)}%</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

### **2. "Technology Breakdown" - Por Tecnologia**

```jsx
export function TechnologyBreakdown() {
  const [techRankings, setTechRankings] = useState({});
  
  useEffect(() => {
    loadAllTechnologies();
  }, []);
  
  const loadAllTechnologies = async () => {
    const technologies = ['Fiber', 'Cable', 'DSL', 'Mobile'];
    
    const results = {};
    for (const tech of technologies) {
      const response = await fetch(
        `/api/admin/reports/global/provider-ranking?technology=${tech}&limit=5`,
        { headers: { 'Authorization': `Bearer ${token}` }}
      );
      const data = await response.json();
      results[tech] = data.data.ranking;
    }
    
    setTechRankings(results);
  };
  
  return (
    <div className="tech-breakdown">
      <h2>📡 Rankings by Technology</h2>
      
      <div className="tech-grid">
        {Object.entries(techRankings).map(([tech, ranking]) => (
          <div key={tech} className="tech-section">
            <h3>{tech}</h3>
            <ol>
              {ranking.map(item => (
                <li key={item.rank}>
                  {item.domain_name} + {item.provider_name}
                  <br/>
                  <small>{item.total_requests.toLocaleString()} requests</small>
                </li>
              ))}
            </ol>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## 🎨 CSS Sugerido

```css
.ranking-table {
  width: 100%;
  border-collapse: collapse;
}

.ranking-table th {
  background: #f5f5f5;
  padding: 12px;
  text-align: left;
  border-bottom: 2px solid #e0e0e0;
}

.ranking-table td {
  padding: 12px;
  border-bottom: 1px solid #e0e0e0;
}

.ranking-table tr:hover {
  background: #fafafa;
}

.rank {
  font-weight: bold;
  color: #1976d2;
}

.medal {
  margin-right: 4px;
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.tech-fiber { background: #4caf50; color: white; }
.tech-cable { background: #2196f3; color: white; }
.tech-dsl { background: #ff9800; color: white; }
.tech-mobile { background: #9c27b0; color: white; }

.rank-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 12px;
}

.rank-badge {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1976d2;
  color: white;
  border-radius: 50%;
  font-weight: bold;
  margin-right: 16px;
}

.rank-card:nth-child(1) .rank-badge { background: #ffd700; } /* Gold */
.rank-card:nth-child(2) .rank-badge { background: #c0c0c0; } /* Silver */
.rank-card:nth-child(3) .rank-badge { background: #cd7f32; } /* Bronze */
```

---

## 🔍 Descobrir ID dos Providers

```bash
# Listar todos os providers
docker-compose exec app php artisan tinker --execute="
\$providers = App\Models\Provider::all();
foreach (\$providers as \$p) {
    echo \$p->id . ': ' . \$p->name . PHP_EOL;
}
"

# Buscar provider específico
docker-compose exec app php artisan tinker --execute="
\$spectrum = App\Models\Provider::where('name', 'Spectrum')->first();
echo 'Spectrum ID: ' . \$spectrum->id . PHP_EOL;
"
```

---

## 💡 Casos de Uso Avançados

### **1. Comparar Spectrum vs AT&T vs Verizon**

```jsx
const compareTopProviders = async () => {
  const providers = ['Spectrum', 'AT&T', 'Verizon'];
  const results = [];
  
  for (const providerName of providers) {
    // Buscar ID do provider
    const providersData = await fetch('/api/admin/providers').then(r => r.json());
    const provider = providersData.data.find(p => p.name === providerName);
    
    if (provider) {
      // Buscar top domain para este provider
      const response = await fetch(
        `/api/admin/reports/global/provider-ranking?provider_id=${provider.id}&limit=1`,
        { headers: { 'Authorization': `Bearer ${token}` }}
      );
      const data = await response.json();
      
      results.push({
        provider_name: providerName,
        top_domain: data.data.ranking[0]
      });
    }
  }
  
  return results;
};
```

---

### **2. Heatmap de Providers por Domain**

```jsx
const generateProviderHeatmap = async () => {
  // Buscar todos os rankings
  const response = await fetch(
    '/api/admin/reports/global/provider-ranking?limit=100',
    { headers: { 'Authorization': `Bearer ${token}` }}
  );
  
  const data = await response.json();
  const rankings = data.data.ranking;
  
  // Agrupar por domain
  const heatmap = {};
  rankings.forEach(item => {
    if (!heatmap[item.domain_name]) {
      heatmap[item.domain_name] = {};
    }
    heatmap[item.domain_name][item.provider_name] = {
      requests: item.total_requests,
      success_rate: item.avg_success_rate
    };
  });
  
  return heatmap;
  
  /* Output:
  {
    "zip.50g.io": {
      "Spectrum": { requests: 500, success_rate: 88.5 },
      "AT&T": { requests: 300, success_rate: 92.0 }
    },
    "smarterhome.ai": {
      "Verizon": { requests: 400, success_rate: 85.0 }
    }
  }
  */
};
```

---

### **3. Alertas de Performance**

```jsx
const checkProviderPerformance = async () => {
  // Buscar providers com baixa success rate
  const response = await fetch(
    '/api/admin/reports/global/provider-ranking?sort_by=success_rate',
    { headers: { 'Authorization': `Bearer ${token}` }}
  );
  
  const data = await response.json();
  const rankings = data.data.ranking;
  
  // Alertar se success rate < 80%
  const lowPerformance = rankings.filter(item => item.avg_success_rate < 80);
  
  if (lowPerformance.length > 0) {
    alert(
      `⚠️ Warning! ${lowPerformance.length} provider(s) with low performance:\n\n` +
      lowPerformance.map(item => 
        `${item.domain_name} + ${item.provider_name}: ${item.avg_success_rate.toFixed(1)}%`
      ).join('\n')
    );
  }
};
```

---

## 📈 Gráficos Sugeridos

### **1. Bar Chart - Top 10 Providers**

```jsx
import { BarChart, Bar, XAxis, YAxis, Tooltip, Legend } from 'recharts';

export function ProviderBarChart() {
  const [chartData, setChartData] = useState([]);
  
  useEffect(() => {
    loadChartData();
  }, []);
  
  const loadChartData = async () => {
    const response = await fetch(
      '/api/admin/reports/global/provider-ranking?limit=10',
      { headers: { 'Authorization': `Bearer ${token}` }}
    );
    
    const data = await response.json();
    
    // Transformar para formato do Recharts
    const chartData = data.data.ranking.map(item => ({
      name: `${item.domain_name} + ${item.provider_name}`,
      requests: item.total_requests,
      success_rate: item.avg_success_rate
    }));
    
    setChartData(chartData);
  };
  
  return (
    <BarChart width={800} height={400} data={chartData}>
      <XAxis dataKey="name" />
      <YAxis />
      <Tooltip />
      <Legend />
      <Bar dataKey="requests" fill="#2196f3" />
      <Bar dataKey="success_rate" fill="#4caf50" />
    </BarChart>
  );
}
```

---

## ✅ Testes Implementados

```
✅ can_get_provider_ranking
✅ can_filter_by_specific_provider
✅ can_filter_by_technology
✅ can_sort_by_different_criteria
✅ can_limit_results
✅ can_filter_by_date_range
✅ validation_error_for_invalid_sort_by
✅ aggregates_multiple_reports_for_same_domain_provider_combination

Total: 8 testes - 100% passando
```

---

## 🚀 Quick Start

```bash
# 1. Ver top 10 geral
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?limit=10" \
  -H "Authorization: Bearer $TOKEN"

# 2. Top Spectrum
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=15" \
  -H "Authorization: Bearer $TOKEN"

# 3. Top Fiber por velocidade
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?technology=Fiber&sort_by=avg_speed" \
  -H "Authorization: Bearer $TOKEN"
```

---

**Endpoint:** ✅ Pronto  
**Use Case:** ✅ Implementado  
**Testes:** ✅ 8/8 passando  
**Docs:** ✅ Completa

