# 📊 Relatórios Globais - Rankings por Provider

## 🎯 Rotas Disponíveis

```
GET /api/admin/reports/global/domain-ranking    → Ranking geral de domínios
GET /api/admin/reports/global/comparison        → Comparar domínios específicos
```

---

## 📈 1. Ranking Geral de Domínios

### **Endpoint:**
```
GET /api/admin/reports/global/domain-ranking
```

### **Query Parameters:**
- `sort_by` - Critério de ordenação (score, volume, success, speed) - Default: score
- `date_from` - Data inicial (YYYY-MM-DD) - Opcional
- `date_to` - Data final (YYYY-MM-DD) - Opcional
- `min_reports` - Mínimo de reports necessários - Opcional

### **Exemplo:**
```bash
curl "http://localhost:8007/api/admin/reports/global/domain-ranking?sort_by=volume&date_from=2025-11-01" \
  -H "Authorization: Bearer $TOKEN"
```

### **Response:**
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
        "total_requests": 5000,
        "success_rate": 85.5,
        "avg_speed": 1200,
        "score": 42.5,
        "total_reports": 30,
        "unique_providers": 45,
        "unique_states": 12,
        "period_start": "2025-11-01",
        "period_end": "2025-11-30",
        "days_covered": 30
      },
      {
        "rank": 2,
        "domain_id": 2,
        "domain_name": "fiberfinder.com",
        ...
      }
    ],
    "sort_by": "volume",
    "total_domains": 5,
    "filters": {
      "date_from": "2025-11-01",
      "date_to": null,
      "min_reports": null
    }
  }
}
```

---

## 🏆 2. Ranking por Provider (Top Spectrum, Top AT&T, etc)

### **Como Funciona:**

Os dados de providers estão na tabela `report_providers` com:
- `provider_id` - ID do provider
- `total_count` - Total de ocorrências
- `success_rate` - Taxa de sucesso
- `avg_speed` - Velocidade média
- `technology` - Tecnologia (Fiber, Cable, etc)

### **Opção A: Criar Novo Endpoint (Recomendado)**

Criar rota:
```
GET /api/admin/reports/global/provider-ranking
```

```php
// No ReportController.php, adicionar:

public function providerRanking(Request $request): JsonResponse
{
    try {
        $providerId = $request->query('provider_id'); // Opcional - filtrar por provider
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $technology = $request->query('technology'); // Fiber, Cable, DSL, etc
        $sortBy = $request->query('sort_by', 'total_requests'); // total_requests, success_rate, avg_speed
        
        // Query base
        $query = DB::table('report_providers')
            ->join('providers', 'report_providers.provider_id', '=', 'providers.id')
            ->join('reports', 'report_providers.report_id', '=', 'reports.id')
            ->join('domains', 'reports.domain_id', '=', 'domains.id')
            ->where('reports.status', 'processed')
            ->where('domains.is_active', true);
        
        // Filtros
        if ($providerId) {
            $query->where('report_providers.provider_id', $providerId);
        }
        
        if ($technology) {
            $query->where('report_providers.technology', $technology);
        }
        
        if ($dateFrom) {
            $query->where('reports.report_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('reports.report_date', '<=', $dateTo);
        }
        
        // Filtrar por domínios acessíveis
        $admin = $request->user();
        $accessibleDomains = $admin->getAccessibleDomains();
        if (!empty($accessibleDomains)) {
            $query->whereIn('domains.id', $accessibleDomains);
        }
        
        // Agregar por domínio e provider
        $rankings = $query
            ->select(
                'domains.id as domain_id',
                'domains.name as domain_name',
                'providers.id as provider_id',
                'providers.name as provider_name',
                'report_providers.technology',
                DB::raw('SUM(report_providers.total_count) as total_requests'),
                DB::raw('AVG(report_providers.success_rate) as avg_success_rate'),
                DB::raw('AVG(report_providers.avg_speed) as avg_speed'),
                DB::raw('COUNT(DISTINCT reports.id) as total_reports')
            )
            ->groupBy('domains.id', 'domains.name', 'providers.id', 'providers.name', 'report_providers.technology')
            ->orderByRaw(match($sortBy) {
                'success_rate' => 'avg_success_rate DESC',
                'avg_speed' => 'avg_speed DESC',
                default => 'total_requests DESC',
            })
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'ranking' => $rankings,
                'total_entries' => $rankings->count(),
                'filters' => [
                    'provider_id' => $providerId,
                    'technology' => $technology,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'sort_by' => $sortBy,
                ],
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error getting provider ranking',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
        ], 500);
    }
}
```

**Registrar rota em `routes/api.php`:**
```php
Route::prefix('global')->group(function () {
    Route::get('/domain-ranking', [ReportController::class, 'globalRanking']);
    Route::get('/comparison', [ReportController::class, 'compareDomains']);
    Route::get('/provider-ranking', [ReportController::class, 'providerRanking']); // NOVO
});
```

---

### **Opção B: Usar SQL Direto (Rápido para Testes)**

```php
// Top 10 Domains por Spectrum
$spectrumRanking = DB::table('report_providers')
    ->join('providers', 'report_providers.provider_id', '=', 'providers.id')
    ->join('reports', 'report_providers.report_id', '=', 'reports.id')
    ->join('domains', 'reports.domain_id', '=', 'domains.id')
    ->where('providers.name', 'Spectrum')
    ->where('reports.status', 'processed')
    ->select(
        'domains.id',
        'domains.name as domain_name',
        DB::raw('SUM(report_providers.total_count) as total_requests'),
        DB::raw('AVG(report_providers.success_rate) as avg_success_rate'),
        DB::raw('AVG(report_providers.avg_speed) as avg_speed')
    )
    ->groupBy('domains.id', 'domains.name')
    ->orderBy('total_requests', 'desc')
    ->limit(10)
    ->get();
```

---

## 📊 3. Exemplos de Rankings

### **A. Top Domains por Provider Específico**

```bash
# Top domains que mais consultam Spectrum
GET /api/admin/reports/global/provider-ranking?provider_id=1&sort_by=total_requests

# Top domains com melhor success rate no AT&T
GET /api/admin/reports/global/provider-ranking?provider_id=2&sort_by=success_rate

# Top domains por velocidade no Verizon
GET /api/admin/reports/global/provider-ranking?provider_id=3&sort_by=avg_speed
```

**Response:**
```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "provider_id": 1,
        "provider_name": "Spectrum",
        "technology": "Cable",
        "total_requests": 1500,
        "avg_success_rate": 92.5,
        "avg_speed": 1200,
        "total_reports": 30
      }
    ]
  }
}
```

---

### **B. Top Providers por Domínio**

```sql
-- Top providers do domínio zip.50g.io
SELECT 
    p.name as provider_name,
    rp.technology,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate,
    AVG(rp.avg_speed) as avg_speed
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
JOIN reports r ON rp.report_id = r.id
WHERE r.domain_id = 1
  AND r.status = 'processed'
GROUP BY p.id, p.name, rp.technology
ORDER BY total_requests DESC
LIMIT 10;
```

---

### **C. Top Providers Globalmente**

```sql
-- Top providers em todos os domínios
SELECT 
    p.name as provider_name,
    COUNT(DISTINCT r.domain_id) as domains_count,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate,
    AVG(rp.avg_speed) as avg_speed
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
JOIN reports r ON rp.report_id = r.id
WHERE r.status = 'processed'
GROUP BY p.id, p.name
ORDER BY total_requests DESC
LIMIT 20;
```

---

## 🎨 4. Frontend - Como Usar

### **Dashboard de Rankings:**

```jsx
export function ProviderRankingDashboard() {
  const [rankings, setRankings] = useState([]);
  const [selectedProvider, setSelectedProvider] = useState(null);
  
  useEffect(() => {
    loadProviderRanking();
  }, [selectedProvider]);
  
  const loadProviderRanking = async () => {
    const params = new URLSearchParams();
    if (selectedProvider) {
      params.append('provider_id', selectedProvider);
    }
    params.append('sort_by', 'total_requests');
    
    const response = await fetch(
      `/api/admin/reports/global/provider-ranking?${params}`,
      { headers: { 'Authorization': `Bearer ${token}` }}
    );
    
    const data = await response.json();
    setRankings(data.data.ranking);
  };
  
  return (
    <div>
      <h2>🏆 Provider Rankings</h2>
      
      {/* Filtro de Provider */}
      <select onChange={(e) => setSelectedProvider(e.target.value || null)}>
        <option value="">All Providers</option>
        <option value="1">Spectrum</option>
        <option value="2">AT&T</option>
        <option value="3">Verizon</option>
      </select>
      
      {/* Tabela de Rankings */}
      <table>
        <thead>
          <tr>
            <th>Rank</th>
            <th>Domain</th>
            <th>Provider</th>
            <th>Requests</th>
            <th>Success Rate</th>
            <th>Avg Speed</th>
          </tr>
        </thead>
        <tbody>
          {rankings.map((item, index) => (
            <tr key={`${item.domain_id}-${item.provider_id}`}>
              <td>{index + 1}</td>
              <td>{item.domain_name}</td>
              <td>{item.provider_name} ({item.technology})</td>
              <td>{item.total_requests.toLocaleString()}</td>
              <td>{item.avg_success_rate.toFixed(2)}%</td>
              <td>{item.avg_speed.toFixed(0)} ms</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

---

## 🚀 5. Implementação Completa - Use Case

Se quiser criar um Use Case dedicado:

```php
<?php
// app/Application/UseCases/Report/Global/GetProviderRankingUseCase.php

namespace App\Application\UseCases\Report\Global;

use Illuminate\Support\Facades\DB;

class GetProviderRankingUseCase
{
    /**
     * Get provider ranking across all domains
     * 
     * @param int|null $providerId Filter by specific provider
     * @param string|null $technology Filter by technology
     * @param string|null $dateFrom Date range start
     * @param string|null $dateTo Date range end
     * @param string $sortBy Sort criteria
     * @param array|null $accessibleDomainIds Filter by accessible domains
     * @return array
     */
    public function execute(
        ?int $providerId = null,
        ?string $technology = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortBy = 'total_requests',
        ?array $accessibleDomainIds = null
    ): array {
        $query = DB::table('report_providers as rp')
            ->join('providers as p', 'rp.provider_id', '=', 'p.id')
            ->join('reports as r', 'rp.report_id', '=', 'r.id')
            ->join('domains as d', 'r.domain_id', '=', 'd.id')
            ->where('r.status', 'processed')
            ->where('d.is_active', true);
        
        // Filtros
        if ($providerId) {
            $query->where('rp.provider_id', $providerId);
        }
        
        if ($technology) {
            $query->where('rp.technology', $technology);
        }
        
        if ($dateFrom) {
            $query->where('r.report_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('r.report_date', '<=', $dateTo);
        }
        
        if ($accessibleDomainIds && !empty($accessibleDomainIds)) {
            $query->whereIn('d.id', $accessibleDomainIds);
        }
        
        // Agregar
        $rankings = $query
            ->select(
                'd.id as domain_id',
                'd.name as domain_name',
                'd.slug as domain_slug',
                'p.id as provider_id',
                'p.name as provider_name',
                'rp.technology',
                DB::raw('SUM(rp.total_count) as total_requests'),
                DB::raw('AVG(rp.success_rate) as avg_success_rate'),
                DB::raw('AVG(rp.avg_speed) as avg_speed'),
                DB::raw('COUNT(DISTINCT r.id) as total_reports'),
                DB::raw('MIN(r.report_date) as period_start'),
                DB::raw('MAX(r.report_date) as period_end')
            )
            ->groupBy('d.id', 'd.name', 'd.slug', 'p.id', 'p.name', 'rp.technology')
            ->orderByRaw($this->getOrderByClause($sortBy))
            ->get()
            ->toArray();
        
        // Adicionar rank
        return array_map(function($item, $index) {
            return array_merge((array)$item, ['rank' => $index + 1]);
        }, $rankings, array_keys($rankings));
    }
    
    private function getOrderByClause(string $sortBy): string
    {
        return match($sortBy) {
            'success_rate' => 'avg_success_rate DESC',
            'avg_speed' => 'avg_speed DESC',
            'total_reports' => 'total_reports DESC',
            default => 'total_requests DESC',
        };
    }
}
```

---

## 📊 6. Exemplos de Queries Úteis

### **Top 10 Providers Globalmente:**
```sql
SELECT 
    p.name as provider_name,
    COUNT(DISTINCT d.id) as domains_using,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate,
    AVG(rp.avg_speed) as avg_speed
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
JOIN reports r ON rp.report_id = r.id
JOIN domains d ON r.domain_id = d.id
WHERE r.status = 'processed'
GROUP BY p.id, p.name
ORDER BY total_requests DESC
LIMIT 10;
```

### **Top Domains por Spectrum:**
```sql
SELECT 
    d.name as domain_name,
    SUM(rp.total_count) as spectrum_requests,
    AVG(rp.success_rate) as spectrum_success_rate,
    AVG(rp.avg_speed) as spectrum_speed
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
JOIN reports r ON rp.report_id = r.id
JOIN domains d ON r.domain_id = d.id
WHERE p.name = 'Spectrum'
  AND r.status = 'processed'
GROUP BY d.id, d.name
ORDER BY spectrum_requests DESC
LIMIT 10;
```

### **Top Technologies por Provider:**
```sql
SELECT 
    p.name as provider_name,
    rp.technology,
    COUNT(DISTINCT d.id) as domains_count,
    SUM(rp.total_count) as total_requests,
    AVG(rp.success_rate) as avg_success_rate
FROM report_providers rp
JOIN providers p ON rp.provider_id = p.id
JOIN reports r ON rp.report_id = r.id
JOIN domains d ON r.domain_id = d.id
WHERE r.status = 'processed'
GROUP BY p.id, p.name, rp.technology
ORDER BY total_requests DESC;
```

---

## 🎯 7. Casos de Uso Práticos

### **A. Dashboard "Top Providers"**

```jsx
export function TopProvidersDashboard() {
  const [topProviders, setTopProviders] = useState([]);
  
  useEffect(() => {
    fetchTopProviders();
  }, []);
  
  const fetchTopProviders = async () => {
    // Query direto (até implementar o endpoint)
    const query = `
      SELECT 
        p.name,
        SUM(rp.total_count) as requests,
        AVG(rp.success_rate) as success_rate
      FROM report_providers rp
      JOIN providers p ON rp.provider_id = p.id
      JOIN reports r ON rp.report_id = r.id
      WHERE r.status = 'processed'
      GROUP BY p.id, p.name
      ORDER BY requests DESC
      LIMIT 10
    `;
    
    // Por enquanto, fazer via endpoint custom ou aggregação
  };
  
  return (
    <div className="top-providers">
      <h2>🏆 Top Providers</h2>
      {topProviders.map((provider, index) => (
        <div key={provider.name} className="provider-card">
          <span className="rank">#{index + 1}</span>
          <span className="name">{provider.name}</span>
          <span className="requests">{provider.requests.toLocaleString()} requests</span>
          <span className="success">{provider.success_rate.toFixed(1)}% success</span>
        </div>
      ))}
    </div>
  );
}
```

---

### **B. Comparação "Spectrum vs AT&T vs Verizon"**

```jsx
export function ProviderComparison({ providerIds = [1, 2, 3] }) {
  const [comparison, setComparison] = useState([]);
  
  const fetchComparison = async () => {
    const results = await Promise.all(
      providerIds.map(async (providerId) => {
        const response = await fetch(
          `/api/admin/reports/global/provider-ranking?provider_id=${providerId}`,
          { headers: { 'Authorization': `Bearer ${token}` }}
        );
        const data = await response.json();
        return data.data.ranking[0]; // Top domain for this provider
      })
    );
    
    setComparison(results);
  };
  
  return (
    <div className="provider-comparison">
      <h2>Provider Comparison</h2>
      <div className="comparison-grid">
        {comparison.map(item => (
          <div key={item.provider_id} className="provider-stats">
            <h3>{item.provider_name}</h3>
            <div className="stat">
              <span className="label">Top Domain:</span>
              <span className="value">{item.domain_name}</span>
            </div>
            <div className="stat">
              <span className="label">Requests:</span>
              <span className="value">{item.total_requests.toLocaleString()}</span>
            </div>
            <div className="stat">
              <span className="label">Success Rate:</span>
              <span className="value">{item.avg_success_rate.toFixed(1)}%</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## 🔍 8. Teste via Tinker

```bash
# Ver providers disponíveis
docker-compose exec app php artisan tinker --execute="
\$providers = App\Models\Provider::all();
foreach (\$providers as \$p) {
    echo \$p->id . ': ' . \$p->name . PHP_EOL;
}
"

# Top domains por provider
docker-compose exec app php artisan tinker --execute="
\$ranking = DB::table('report_providers')
    ->join('providers', 'report_providers.provider_id', '=', 'providers.id')
    ->join('reports', 'report_providers.report_id', '=', 'reports.id')
    ->join('domains', 'reports.domain_id', '=', 'domains.id')
    ->where('providers.name', 'Spectrum')
    ->where('reports.status', 'processed')
    ->select(
        'domains.name',
        DB::raw('SUM(report_providers.total_count) as requests')
    )
    ->groupBy('domains.id', 'domains.name')
    ->orderBy('requests', 'desc')
    ->limit(5)
    ->get();

foreach (\$ranking as \$r) {
    echo \$r->name . ': ' . \$r->requests . ' requests' . PHP_EOL;
}
"
```

---

## ✅ Resumo Rápido

**Para fazer rankings por provider:**

1. **Opção Rápida:** Usar SQL direto com `DB::table('report_providers')`
2. **Opção Correta:** Criar `GetProviderRankingUseCase` + endpoint
3. **Dados já estão lá:** Tabela `report_providers` tem tudo

**Estrutura:**
- `report_providers` tem dados por provider/domain/report
- Fazer JOIN com `providers`, `reports`, `domains`
- Agregar com SUM/AVG
- Ordenar por total_requests, success_rate ou avg_speed

**Exemplos prontos:**
- Top Spectrum → Filtrar `WHERE providers.name = 'Spectrum'`
- Top AT&T → Filtrar `WHERE providers.name = 'AT&T'`
- Global → Sem filtro de provider

---

**Próximo passo:** Implementar o endpoint `providerRanking` no ReportController? 🚀

