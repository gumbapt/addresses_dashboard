# 🏆 Frontend - Provider Rankings Implementation Guide

## 📋 O Que Implementar

Sistema de rankings de Providers para mostrar:
- **Top Providers** (Spectrum, AT&T, Verizon, etc)
- **Rankings por Tecnologia** (Fiber, Cable, DSL, Mobile)
- **Comparação de Providers**
- **Dashboards interativos**

**Backend:** ✅ 100% pronto e testado (8 testes passando)

---

## 🚀 API Disponível

### **Base URL:** `http://localhost:8007/api/admin/reports/global/provider-ranking`

### **Authentication:**
```
Authorization: Bearer {seu_token}
```

---

## 📡 Endpoint Principal

### **GET /api/admin/reports/global/provider-ranking**

Query parameters disponíveis:
- `provider_id` - Filtrar por provider específico (ex: 5 = Earthlink)
- `technology` - Filtrar por tecnologia (Fiber, Cable, DSL, Mobile)
- `date_from` - Data inicial (YYYY-MM-DD)
- `date_to` - Data final (YYYY-MM-DD)
- `sort_by` - Ordenar por (total_requests, success_rate, avg_speed, total_reports)
- `limit` - Limitar resultados (ex: 10)

---

## 📊 Response Format

```javascript
const fetchProviderRanking = async () => {
  const response = await fetch(
    '/api/admin/reports/global/provider-ranking?limit=10',
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  
  // Response Structure:
  {
    "success": true,
    "data": {
      "ranking": [
        {
          "rank": 1,
          "domain_id": 3,
          "domain_name": "smarterhome.ai",
          "domain_slug": "smarterhome-ai",
          "provider_id": 5,
          "provider_name": "Earthlink",
          "technology": "Cable",
          "total_requests": 1137,
          "avg_success_rate": 85.5,
          "avg_speed": 1200,
          "total_reports": 10,
          "period_start": "2025-11-01",
          "period_end": "2025-11-30",
          "days_covered": 30
        }
      ],
      "total_entries": 50,
      "filters": {
        "provider_id": null,
        "technology": null,
        "date_from": null,
        "date_to": null,
        "sort_by": "total_requests",
        "limit": 10
      }
    }
  }
  
  return data.data.ranking;
};
```

---

## 🎨 Componentes - Copy & Paste

### **1. ProviderRankingTable** - Tabela Principal

```jsx
import { useState, useEffect } from 'react';

export function ProviderRankingTable() {
  const [rankings, setRankings] = useState([]);
  const [filters, setFilters] = useState({
    technology: null,
    sort_by: 'total_requests',
    limit: 20
  });
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    loadRankings();
  }, [filters]);
  
  const loadRankings = async () => {
    setLoading(true);
    
    const params = new URLSearchParams();
    if (filters.technology) params.append('technology', filters.technology);
    params.append('sort_by', filters.sort_by);
    params.append('limit', filters.limit);
    
    try {
      const response = await fetch(
        `/api/admin/reports/global/provider-ranking?${params}`,
        { headers: { 'Authorization': `Bearer ${token}` }}
      );
      
      const data = await response.json();
      setRankings(data.data.ranking);
    } catch (error) {
      console.error('Error loading rankings:', error);
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="provider-ranking">
      <h2>🏆 Provider Rankings</h2>
      
      {/* Filtros */}
      <div className="filters">
        <select 
          value={filters.technology || ''}
          onChange={(e) => setFilters({...filters, technology: e.target.value || null})}
        >
          <option value="">All Technologies</option>
          <option value="Fiber">🔵 Fiber</option>
          <option value="Cable">🟢 Cable</option>
          <option value="DSL">🟡 DSL</option>
          <option value="Mobile">🟣 Mobile</option>
          <option value="Satellite">🔴 Satellite</option>
        </select>
        
        <select
          value={filters.sort_by}
          onChange={(e) => setFilters({...filters, sort_by: e.target.value})}
        >
          <option value="total_requests">📊 Most Requests</option>
          <option value="success_rate">✅ Best Success Rate</option>
          <option value="avg_speed">⚡ Fastest Speed</option>
          <option value="total_reports">📈 Most Reports</option>
        </select>
        
        <select
          value={filters.limit}
          onChange={(e) => setFilters({...filters, limit: parseInt(e.target.value)})}
        >
          <option value="10">Top 10</option>
          <option value="20">Top 20</option>
          <option value="50">Top 50</option>
          <option value="100">Top 100</option>
        </select>
      </div>
      
      {/* Tabela */}
      {loading ? (
        <div className="loading">Loading...</div>
      ) : (
        <table className="ranking-table">
          <thead>
            <tr>
              <th>Rank</th>
              <th>Domain</th>
              <th>Provider</th>
              <th>Tech</th>
              <th>Requests</th>
              <th>Success Rate</th>
              <th>Avg Speed</th>
              <th>Period</th>
            </tr>
          </thead>
          <tbody>
            {rankings.map(item => (
              <tr key={`${item.domain_id}-${item.provider_id}-${item.technology}`}>
                <td className="rank">
                  {item.rank <= 3 && <span className="medal">
                    {item.rank === 1 ? '🥇' : item.rank === 2 ? '🥈' : '🥉'}
                  </span>}
                  #{item.rank}
                </td>
                <td className="domain-name">
                  <strong>{item.domain_name}</strong>
                </td>
                <td className="provider-name">{item.provider_name}</td>
                <td>
                  <span className={`tech-badge tech-${item.technology?.toLowerCase() || 'unknown'}`}>
                    {item.technology || 'Unknown'}
                  </span>
                </td>
                <td className="requests">{item.total_requests.toLocaleString()}</td>
                <td className="success-rate">
                  <span className={`rate-badge ${item.avg_success_rate >= 90 ? 'high' : item.avg_success_rate >= 70 ? 'medium' : 'low'}`}>
                    {item.avg_success_rate.toFixed(1)}%
                  </span>
                </td>
                <td className="speed">{item.avg_speed.toFixed(0)} ms</td>
                <td className="period">
                  <small>{item.days_covered} days</small>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
```

---

### **2. TopProviderCard** - Card de Provider Específico

```jsx
export function TopProviderCard({ providerName, providerId }) {
  const [topDomain, setTopDomain] = useState(null);
  
  useEffect(() => {
    loadTopDomain();
  }, [providerId]);
  
  const loadTopDomain = async () => {
    const response = await fetch(
      `/api/admin/reports/global/provider-ranking?provider_id=${providerId}&limit=1`,
      { headers: { 'Authorization': `Bearer ${token}` }}
    );
    
    const data = await response.json();
    if (data.data.ranking.length > 0) {
      setTopDomain(data.data.ranking[0]);
    }
  };
  
  if (!topDomain) return <div>Loading...</div>;
  
  return (
    <div className="top-provider-card">
      <div className="provider-header">
        <h3>{providerName}</h3>
        <span className={`tech-badge tech-${topDomain.technology?.toLowerCase()}`}>
          {topDomain.technology}
        </span>
      </div>
      
      <div className="top-domain">
        <span className="label">🥇 Top Domain:</span>
        <span className="value">{topDomain.domain_name}</span>
      </div>
      
      <div className="stats-grid">
        <div className="stat">
          <span className="label">Requests</span>
          <span className="value">{topDomain.total_requests.toLocaleString()}</span>
        </div>
        <div className="stat">
          <span className="label">Success Rate</span>
          <span className="value">{topDomain.avg_success_rate.toFixed(1)}%</span>
        </div>
        <div className="stat">
          <span className="label">Avg Speed</span>
          <span className="value">{topDomain.avg_speed.toFixed(0)} ms</span>
        </div>
        <div className="stat">
          <span className="label">Reports</span>
          <span className="value">{topDomain.total_reports}</span>
        </div>
      </div>
    </div>
  );
}
```

---

### **3. ProviderComparisonGrid** - Comparar 4 Providers

```jsx
export function ProviderComparisonGrid() {
  const [comparison, setComparison] = useState([]);
  
  // Principais providers para comparar
  const mainProviders = [
    { name: 'Spectrum', id: 15 },
    { name: 'AT&T', id: 6 },
    { name: 'Verizon', id: 7 },
    { name: 'Comcast', id: 8 }
  ];
  
  useEffect(() => {
    loadComparison();
  }, []);
  
  const loadComparison = async () => {
    const results = await Promise.all(
      mainProviders.map(async (provider) => {
        const response = await fetch(
          `/api/admin/reports/global/provider-ranking?provider_id=${provider.id}&limit=3`,
          { headers: { 'Authorization': `Bearer ${token}` }}
        );
        
        const data = await response.json();
        return {
          provider_name: provider.name,
          provider_id: provider.id,
          top_domains: data.data.ranking
        };
      })
    );
    
    setComparison(results);
  };
  
  return (
    <div className="provider-comparison-grid">
      <h2>📊 Provider Comparison</h2>
      
      <div className="grid">
        {comparison.map(item => (
          <div key={item.provider_id} className="provider-section">
            <h3>{item.provider_name}</h3>
            
            <div className="top-3-list">
              {item.top_domains.map(domain => (
                <div key={domain.rank} className="domain-item">
                  <span className="rank-badge">#{domain.rank}</span>
                  <div className="domain-info">
                    <div className="domain-name">{domain.domain_name}</div>
                    <div className="domain-stats">
                      {domain.total_requests.toLocaleString()} requests •
                      {domain.avg_success_rate.toFixed(1)}% success
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

### **4. TechnologyBreakdown** - Por Tecnologia

```jsx
export function TechnologyBreakdown() {
  const [techRankings, setTechRankings] = useState({});
  const technologies = ['Fiber', 'Cable', 'DSL', 'Mobile'];
  
  useEffect(() => {
    loadAllTechnologies();
  }, []);
  
  const loadAllTechnologies = async () => {
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
    <div className="technology-breakdown">
      <h2>📡 Rankings by Technology</h2>
      
      <div className="tech-grid">
        {technologies.map(tech => (
          <div key={tech} className="tech-column">
            <h3 className={`tech-header tech-${tech.toLowerCase()}`}>
              {tech}
            </h3>
            
            <div className="tech-rankings">
              {techRankings[tech]?.map(item => (
                <div key={item.rank} className="tech-item">
                  <div className="tech-rank">#{item.rank}</div>
                  <div className="tech-details">
                    <div className="domain">{item.domain_name}</div>
                    <div className="provider">{item.provider_name}</div>
                    <div className="stats">
                      {item.total_requests.toLocaleString()} req •
                      {item.avg_success_rate.toFixed(1)}%
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

### **5. ProviderSearchWidget** - Buscar Provider Específico

```jsx
export function ProviderSearchWidget() {
  const [providers, setProviders] = useState([]);
  const [selectedProvider, setSelectedProvider] = useState(null);
  const [ranking, setRanking] = useState([]);
  
  useEffect(() => {
    // Buscar lista de providers
    fetch('/api/admin/providers', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(r => r.json())
    .then(data => setProviders(data.data));
  }, []);
  
  useEffect(() => {
    if (selectedProvider) {
      loadProviderRanking();
    }
  }, [selectedProvider]);
  
  const loadProviderRanking = async () => {
    const response = await fetch(
      `/api/admin/reports/global/provider-ranking?provider_id=${selectedProvider}&limit=10`,
      { headers: { 'Authorization': `Bearer ${token}` }}
    );
    
    const data = await response.json();
    setRanking(data.data.ranking);
  };
  
  return (
    <div className="provider-search">
      <h3>Search Provider Rankings</h3>
      
      <select 
        value={selectedProvider || ''}
        onChange={(e) => setSelectedProvider(e.target.value || null)}
      >
        <option value="">Select a Provider</option>
        {providers.map(p => (
          <option key={p.id} value={p.id}>{p.name}</option>
        ))}
      </select>
      
      {selectedProvider && ranking.length > 0 && (
        <div className="results">
          <h4>Top Domains using {ranking[0].provider_name}</h4>
          <ul>
            {ranking.map(item => (
              <li key={item.rank}>
                <strong>#{item.rank}</strong> {item.domain_name}
                <span className="stats">
                  {item.total_requests.toLocaleString()} requests •
                  {item.avg_success_rate.toFixed(1)}% success
                </span>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
```

---

## 🎨 CSS - Copy & Paste

```css
/* Provider Ranking Table */
.ranking-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ranking-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.ranking-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.5px;
}

.ranking-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
}

.ranking-table tr:hover {
  background: #f9f9ff;
}

.rank {
  font-weight: bold;
  color: #667eea;
  font-size: 16px;
}

.medal {
  margin-right: 6px;
  font-size: 18px;
}

/* Technology Badges */
.tech-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tech-fiber { background: #e3f2fd; color: #1976d2; }
.tech-cable { background: #e8f5e9; color: #388e3c; }
.tech-dsl { background: #fff3e0; color: #f57c00; }
.tech-mobile { background: #f3e5f5; color: #7b1fa2; }
.tech-satellite { background: #ffebee; color: #c62828; }
.tech-unknown { background: #f5f5f5; color: #757575; }

/* Rate Badge */
.rate-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: 600;
}

.rate-badge.high { background: #4caf50; color: white; }
.rate-badge.medium { background: #ff9800; color: white; }
.rate-badge.low { background: #f44336; color: white; }

/* Provider Cards */
.provider-comparison-grid .grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.provider-section {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  border-top: 4px solid #667eea;
}

.provider-section h3 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 18px;
}

.domain-item {
  display: flex;
  align-items: center;
  padding: 12px;
  background: #f9f9ff;
  border-radius: 8px;
  margin-bottom: 8px;
}

.rank-badge {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #667eea;
  color: white;
  border-radius: 50%;
  font-weight: bold;
  font-size: 14px;
  margin-right: 12px;
  flex-shrink: 0;
}

.domain-info {
  flex: 1;
}

.domain-name {
  font-weight: 600;
  color: #333;
  margin-bottom: 4px;
}

.domain-stats {
  font-size: 12px;
  color: #666;
}

/* Technology Breakdown */
.tech-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
  margin-top: 20px;
}

.tech-column {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tech-header {
  padding: 16px;
  color: white;
  font-size: 16px;
  font-weight: 600;
  text-align: center;
}

.tech-header.tech-fiber { background: #2196f3; }
.tech-header.tech-cable { background: #4caf50; }
.tech-header.tech-dsl { background: #ff9800; }
.tech-header.tech-mobile { background: #9c27b0; }

.tech-rankings {
  padding: 16px;
}

.tech-item {
  display: flex;
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.tech-item:last-child {
  border-bottom: none;
}

.tech-rank {
  width: 32px;
  font-weight: bold;
  color: #667eea;
}

.tech-details {
  flex: 1;
}

.tech-details .domain {
  font-weight: 600;
  margin-bottom: 4px;
}

.tech-details .provider {
  font-size: 13px;
  color: #666;
  margin-bottom: 4px;
}

.tech-details .stats {
  font-size: 12px;
  color: #999;
}

/* Filters */
.filters {
  display: flex;
  gap: 12px;
  margin: 20px 0;
  flex-wrap: wrap;
}

.filters select {
  padding: 10px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background: white;
  font-size: 14px;
  cursor: pointer;
  transition: border-color 0.2s;
}

.filters select:hover {
  border-color: #667eea;
}

.filters select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
```

---

## 📊 Página Completa - Provider Rankings

```jsx
import { ProviderRankingTable } from './ProviderRankingTable';
import { TopProviderCard } from './TopProviderCard';
import { ProviderComparisonGrid } from './ProviderComparisonGrid';
import { TechnologyBreakdown } from './TechnologyBreakdown';

export default function ProviderRankingsPage() {
  const [activeTab, setActiveTab] = useState('all');
  
  return (
    <div className="container">
      <header className="page-header">
        <h1>🏆 Provider Rankings</h1>
        <p>Rankings de providers por domínio, tecnologia e performance</p>
      </header>
      
      {/* Tabs */}
      <div className="tabs">
        <button 
          className={activeTab === 'all' ? 'active' : ''}
          onClick={() => setActiveTab('all')}
        >
          All Rankings
        </button>
        <button 
          className={activeTab === 'comparison' ? 'active' : ''}
          onClick={() => setActiveTab('comparison')}
        >
          Provider Comparison
        </button>
        <button 
          className={activeTab === 'technology' ? 'active' : ''}
          onClick={() => setActiveTab('technology')}
        >
          By Technology
        </button>
      </div>
      
      {/* Content */}
      <div className="tab-content">
        {activeTab === 'all' && (
          <ProviderRankingTable />
        )}
        
        {activeTab === 'comparison' && (
          <ProviderComparisonGrid />
        )}
        
        {activeTab === 'technology' && (
          <TechnologyBreakdown />
        )}
      </div>
      
      {/* Quick Stats */}
      <div className="quick-stats">
        <h3>Top Providers Quick View</h3>
        <div className="cards-grid">
          <TopProviderCard providerName="Spectrum" providerId={15} />
          <TopProviderCard providerName="AT&T" providerId={6} />
          <TopProviderCard providerName="Verizon" providerId={7} />
          <TopProviderCard providerName="Comcast" providerId={8} />
        </div>
      </div>
    </div>
  );
}
```

---

## 🔍 Buscar Provider IDs Dinamicamente

```javascript
// Criar helper para mapear nomes → IDs
const providerCache = {};

export const getProviderId = async (providerName) => {
  // Usar cache
  if (providerCache[providerName]) {
    return providerCache[providerName];
  }
  
  // Buscar da API
  const response = await fetch('/api/admin/providers', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  const data = await response.json();
  const provider = data.data.find(p => p.name === providerName);
  
  if (provider) {
    providerCache[providerName] = provider.id;
    return provider.id;
  }
  
  return null;
};

// Uso:
const spectrumId = await getProviderId('Spectrum');
const rankingResponse = await fetch(
  `/api/admin/reports/global/provider-ranking?provider_id=${spectrumId}`
);
```

---

## 📈 Gráficos Sugeridos

### **A. Bar Chart - Top 10**

```jsx
import { BarChart, Bar, XAxis, YAxis, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export function ProviderRankingChart({ limit = 10 }) {
  const [data, setData] = useState([]);
  
  useEffect(() => {
    loadData();
  }, [limit]);
  
  const loadData = async () => {
    const response = await fetch(
      `/api/admin/reports/global/provider-ranking?limit=${limit}`,
      { headers: { 'Authorization': `Bearer ${token}` }}
    );
    
    const result = await response.json();
    
    // Transformar para formato do Recharts
    const chartData = result.data.ranking.map(item => ({
      name: `${item.domain_name}\n${item.provider_name}`,
      Requests: item.total_requests,
      'Success Rate': item.avg_success_rate,
      Speed: item.avg_speed / 10 // Escalar para visualização
    }));
    
    setData(chartData);
  };
  
  return (
    <ResponsiveContainer width="100%" height={400}>
      <BarChart data={data}>
        <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
        <YAxis />
        <Tooltip />
        <Legend />
        <Bar dataKey="Requests" fill="#2196f3" />
        <Bar dataKey="Success Rate" fill="#4caf50" />
        <Bar dataKey="Speed" fill="#ff9800" />
      </BarChart>
    </ResponsiveContainer>
  );
}
```

---

## ⚡ Performance Tips

### **Cache de Providers:**
```javascript
// Carregar providers uma vez e cachear
let cachedProviders = null;

const getProviders = async () => {
  if (cachedProviders) return cachedProviders;
  
  const response = await fetch('/api/admin/providers', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  const data = await response.json();
  cachedProviders = data.data;
  
  return cachedProviders;
};
```

### **Debounce de Filtros:**
```javascript
import { useState, useEffect } from 'react';
import { debounce } from 'lodash';

const [filters, setFilters] = useState({...});

const debouncedLoad = debounce(() => {
  loadRankings();
}, 300);

useEffect(() => {
  debouncedLoad();
}, [filters]);
```

---

## ✅ Checklist de Implementação

### **Páginas:**
- [ ] `/admin/reports/provider-rankings` - Página principal
- [ ] Tab: All Rankings (tabela completa)
- [ ] Tab: Provider Comparison (comparar 4 principais)
- [ ] Tab: By Technology (breakdown por tech)

### **Componentes:**
- [ ] `ProviderRankingTable` - Tabela principal
- [ ] `TopProviderCard` - Card de top domain por provider
- [ ] `ProviderComparisonGrid` - Grid de comparação
- [ ] `TechnologyBreakdown` - Breakdown por tecnologia
- [ ] `ProviderSearchWidget` - Busca por provider

### **Features:**
- [ ] Filtros (technology, sort_by, limit)
- [ ] Buscar provider específico
- [ ] Comparar múltiplos providers
- [ ] Badges por tecnologia
- [ ] Medals para top 3
- [ ] Gráficos (opcional)

---

## 🎯 Principais Providers Para Destacar

Com base nos dados atuais:

```
Top 5 por Volume:
1. Earthlink     - 1,137 requests
2. HughesNet     - 1,069 requests
3. AT&T          - 908 requests
4. GeoLinks      - 186 requests
5. Cox           - 149 requests
```

Criar cards especiais para estes 5 na home!

---

## 🚀 Quick Start

### **1. Criar página básica:**
```bash
# pages/ProviderRankings.tsx
import { ProviderRankingTable } from '@/components/rankings/ProviderRankingTable';

export default function ProviderRankingsPage() {
  return <ProviderRankingTable />;
}
```

### **2. Adicionar ao menu:**
```jsx
<NavLink to="/admin/reports/provider-rankings">
  🏆 Provider Rankings
</NavLink>
```

### **3. Testar:**
```bash
# Abrir http://localhost:3000/admin/reports/provider-rankings
# Deve mostrar tabela com rankings
```

---

## 📚 Documentação Completa

- **Guide Completo:** `PROVIDER_RANKING_EXAMPLES.md`
- **API Details:** `GLOBAL_RANKINGS_GUIDE.md`
- **Este Prompt:** `FRONTEND_PROVIDER_RANKING_PROMPT.md`

---

**Backend:** ✅ 100% Pronto (8 testes passando)  
**Tempo Estimado:** 3-4 horas  
**Complexidade:** Média  
**Prioridade:** Alta (feature de analytics importante)

