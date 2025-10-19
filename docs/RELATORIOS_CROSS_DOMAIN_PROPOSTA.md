# 📊 Proposta: Relatórios Cross-Domain

## 🎯 Objetivo

Criar endpoints que agregam dados de **TODOS os domínios**, permitindo análises comparativas, rankings e insights globais.

---

## 🚀 Relatórios Propostos (Ordem de Prioridade)

### **1. Ranking de Domínios** 🏆 (PRIORIDADE ALTA)

**Endpoint:** `GET /api/admin/reports/global/domain-ranking`

**O que faz:**
- Rankeia todos os domínios por diferentes métricas
- Permite comparação direta entre domínios
- Identifica líderes e underperformers

**Métricas de Ranking:**
- Por volume total de requisições
- Por taxa de sucesso
- Por velocidade média
- Por score combinado (volume × success_rate)

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain": {
          "id": 2,
          "name": "smarterhome.ai"
        },
        "metrics": {
          "total_requests": 3620,
          "success_rate": 96.0,
          "avg_speed": 1743,
          "score": 3475.2,
          "total_reports": 40,
          "period": "93 days"
        }
      },
      {
        "rank": 2,
        "domain": {
          "id": 4,
          "name": "broadbandcheck.io"
        },
        "metrics": {
          "total_requests": 2714,
          "success_rate": 94.6,
          "avg_speed": 2014,
          "score": 2567.4,
          "total_reports": 40,
          "period": "93 days"
        }
      }
    ],
    "sort_by": "score",
    "total_domains": 4
  }
}
```

**Filtros sugeridos:**
- `?sort_by=volume|success_rate|avg_speed|score` (default: score)
- `?date_from=2025-06-01` (filtrar por período)
- `?date_to=2025-09-30`
- `?min_reports=30` (domínios com pelo menos X relatórios)

---

### **2. Comparação Direta entre Domínios** 🔄 (PRIORIDADE ALTA)

**Endpoint:** `GET /api/admin/reports/global/comparison`

**O que faz:**
- Compara métricas específicas entre domínios selecionados
- Mostra diferenças percentuais
- Identifica pontos fortes/fracos de cada domínio

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "domains": [
      {
        "id": 1,
        "name": "zip.50g.io",
        "metrics": {
          "total_requests": 1490,
          "success_rate": 92.4,
          "avg_speed": 1503,
          "top_state": "CA",
          "top_provider": "Viasat"
        }
      },
      {
        "id": 2,
        "name": "smarterhome.ai",
        "metrics": {
          "total_requests": 3620,
          "success_rate": 96.0,
          "avg_speed": 1743,
          "top_state": "CA",
          "top_provider": "Verizon"
        },
        "vs_domain_1": {
          "requests_diff": "+143%",
          "success_diff": "+3.6%",
          "speed_diff": "+16%"
        }
      }
    ],
    "comparison_type": "all_metrics"
  }
}
```

**Filtros sugeridos:**
- `?domains=1,2,3` (selecionar domínios específicos)
- `?metric=volume|success|speed` (comparar métrica específica)

---

### **3. Métricas Globais Agregadas** 🌐 (PRIORIDADE MÉDIA)

**Endpoint:** `GET /api/admin/reports/global/metrics`

**O que faz:**
- Agrega todas as métricas de todos os domínios
- Fornece visão geral da plataforma
- Estatísticas consolidadas

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_domains": 4,
      "active_domains": 4,
      "total_reports": 160,
      "total_requests": 8722,
      "global_success_rate": 91.85,
      "global_avg_speed": 1545.5
    },
    "top_providers_global": [
      {
        "name": "Viasat Carrier Services Inc",
        "total_requests": 8231,
        "domain_count": 4,
        "avg_success_rate": 92.3
      }
    ],
    "top_states_global": [
      {
        "code": "CA",
        "name": "California",
        "total_requests": 2061,
        "domain_count": 4,
        "avg_speed": 1650.5
      }
    ],
    "technology_distribution": [
      {
        "technology": "Mobile",
        "total_requests": 22928,
        "percentage": 36.3,
        "domain_count": 4
      }
    ]
  }
}
```

---

### **4. Análise de Tecnologias Cross-Domain** 🔧 (PRIORIDADE MÉDIA)

**Endpoint:** `GET /api/admin/reports/global/technologies`

**O que faz:**
- Analisa distribuição de tecnologias entre todos os domínios
- Identifica preferências por domínio
- Compara performance por tecnologia

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "technologies": [
      {
        "technology": "Mobile",
        "global_metrics": {
          "total_requests": 22928,
          "percentage": 36.3,
          "avg_success_rate": 93.2,
          "avg_speed": 1820.5
        },
        "by_domain": [
          {
            "domain_id": 2,
            "domain_name": "smarterhome.ai",
            "requests": 8500,
            "percentage": 37.1
          }
        ]
      }
    ],
    "insights": [
      "Mobile é a tecnologia mais usada (36.3%)",
      "smarterhome.ai tem a maior preferência por Fiber",
      "ispfinder.net é focado em Mobile"
    ]
  }
}
```

**Filtros sugeridos:**
- `?technology=Mobile|Fiber|Cable|Satellite|DSL` (filtrar tecnologia específica)
- `?min_requests=100` (tecnologias com pelo menos X requisições)

---

### **5. Análise Geográfica Cross-Domain** 🗺️ (PRIORIDADE MÉDIA)

**Endpoint:** `GET /api/admin/reports/global/geographic`

**O que faz:**
- Mostra distribuição geográfica global
- Identifica hotspots de cada domínio
- Compara cobertura geográfica

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "total_requests": 2061,
        "avg_speed": 1650.5,
        "by_domain": [
          {
            "domain_id": 2,
            "domain_name": "smarterhome.ai",
            "requests": 1200,
            "percentage_of_state": 58.2,
            "is_hotspot": true
          },
          {
            "domain_id": 1,
            "domain_name": "zip.50g.io",
            "requests": 239,
            "percentage_of_state": 11.6,
            "is_hotspot": false
          }
        ]
      }
    ],
    "insights": [
      "CA é o estado mais ativo (2,061 requests)",
      "smarterhome.ai domina em CA (58.2% do tráfego)",
      "ispfinder.net é forte em FL, GA, NC"
    ]
  }
}
```

---

### **6. Trends Temporais Cross-Domain** 📈 (PRIORIDADE BAIXA)

**Endpoint:** `GET /api/admin/reports/global/trends`

**O que faz:**
- Mostra evolução ao longo do tempo
- Compara crescimento entre domínios
- Identifica padrões sazonais

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "daily_trends": [
      {
        "date": "2025-06-27",
        "by_domain": [
          {
            "domain_id": 1,
            "domain_name": "zip.50g.io",
            "requests": 114,
            "success_rate": 90.35
          },
          {
            "domain_id": 2,
            "domain_name": "smarterhome.ai",
            "requests": 268,
            "success_rate": 95.52
          }
        ],
        "global_total": 650
      }
    ],
    "growth_rates": [
      {
        "domain_id": 2,
        "domain_name": "smarterhome.ai",
        "growth_percentage": 15.2,
        "trend": "growing"
      }
    ]
  }
}
```

---

### **7. Performance Benchmark** ⚡ (PRIORIDADE BAIXA)

**Endpoint:** `GET /api/admin/reports/global/benchmark`

**O que faz:**
- Estabelece benchmarks baseados nos melhores performers
- Compara cada domínio com o benchmark
- Identifica áreas de melhoria

**Exemplo de Resposta:**
```json
{
  "success": true,
  "data": {
    "benchmarks": {
      "best_success_rate": {
        "value": 96.0,
        "domain": "smarterhome.ai"
      },
      "best_avg_speed": {
        "value": 2014,
        "domain": "broadbandcheck.io"
      },
      "highest_volume": {
        "value": 3620,
        "domain": "smarterhome.ai"
      }
    },
    "domain_scores": [
      {
        "domain_id": 2,
        "domain_name": "smarterhome.ai",
        "benchmark_score": 95.5,
        "strengths": ["success_rate", "volume"],
        "weaknesses": ["avg_speed"]
      }
    ]
  }
}
```

---

## 🎯 Recomendação de Implementação

### **FASE 1 - Essencial** (Implementar primeiro)

1. **Ranking de Domínios** 🏆
   - Use Case: `GetGlobalDomainRankingUseCase`
   - Controller method: `ReportController::globalRanking()`
   - Testes: `GlobalDomainRankingTest.php`

2. **Comparação Direta** 🔄
   - Use Case: `CompareDomainsUseCase`
   - Controller method: `ReportController::compareDomains()`
   - Testes: `CompareDomains Test.php`

### **FASE 2 - Importante** (Implementar depois)

3. **Métricas Globais** 🌐
   - Use Case: `GetGlobalMetricsUseCase`
   - Controller method: `ReportController::globalMetrics()`
   - Testes: `GlobalMetricsTest.php`

4. **Análise de Tecnologias** 🔧
   - Use Case: `GetGlobalTechnologyAnalysisUseCase`
   - Controller method: `ReportController::globalTechnologies()`
   - Testes: `GlobalTechnologyAnalysisTest.php`

### **FASE 3 - Adicional** (Implementar se houver tempo)

5. **Análise Geográfica** 🗺️
6. **Trends Temporais** 📈
7. **Performance Benchmark** ⚡

---

## 🏗️ Estrutura de Arquivos Sugerida

```
app/Application/UseCases/Report/Global/
├── GetGlobalDomainRankingUseCase.php
├── CompareDomainsUseCase.php
├── GetGlobalMetricsUseCase.php
├── GetGlobalTechnologyAnalysisUseCase.php
├── GetGlobalGeographicAnalysisUseCase.php
└── GetGlobalTrendsUseCase.php

app/Application/DTOs/Report/Global/
├── DomainRankingDTO.php
├── GlobalMetricsDTO.php
├── TechnologyAnalysisDTO.php
└── DomainComparisonDTO.php

tests/Feature/Report/Global/
├── GlobalDomainRankingTest.php
├── CompareDomainsFunctionalTest.php
└── GlobalMetricsTest.php

tests/Unit/Application/UseCases/Report/Global/
├── GetGlobalDomainRankingUseCaseTest.php
├── CompareDomainsUseCaseTest.php
└── GetGlobalMetricsUseCaseTest.php
```

---

## 📋 Rotas Sugeridas

```php
// routes/api.php

Route::middleware(['auth:sanctum', 'admin.auth'])->prefix('admin/reports/global')->group(function () {
    // FASE 1 - Essencial
    Route::get('/domain-ranking', [ReportController::class, 'globalRanking'])
        ->name('admin.reports.global.ranking');
    
    Route::get('/comparison', [ReportController::class, 'compareDomains'])
        ->name('admin.reports.global.comparison');
    
    // FASE 2 - Importante
    Route::get('/metrics', [ReportController::class, 'globalMetrics'])
        ->name('admin.reports.global.metrics');
    
    Route::get('/technologies', [ReportController::class, 'globalTechnologies'])
        ->name('admin.reports.global.technologies');
    
    // FASE 3 - Adicional
    Route::get('/geographic', [ReportController::class, 'globalGeographic'])
        ->name('admin.reports.global.geographic');
    
    Route::get('/trends', [ReportController::class, 'globalTrends'])
        ->name('admin.reports.global.trends');
});
```

---

## 🧪 Testes Sugeridos

### **Feature Tests**

#### **GlobalDomainRankingTest.php**
```php
test_admin_can_get_global_domain_ranking()
test_ranking_can_be_sorted_by_volume()
test_ranking_can_be_sorted_by_success_rate()
test_ranking_can_be_sorted_by_speed()
test_ranking_can_be_filtered_by_date_range()
test_ranking_includes_all_active_domains()
test_ranking_excludes_inactive_domains()
test_unauthenticated_users_cannot_access()
```

#### **CompareDomainsFunctionalTest.php**
```php
test_admin_can_compare_two_domains()
test_admin_can_compare_multiple_domains()
test_comparison_shows_percentage_differences()
test_comparison_can_filter_by_metric()
test_comparison_requires_at_least_one_domain()
test_invalid_domain_ids_return_error()
```

#### **GlobalMetricsTest.php**
```php
test_admin_can_get_global_metrics()
test_global_metrics_aggregates_all_domains()
test_global_metrics_shows_top_providers()
test_global_metrics_shows_top_states()
test_global_metrics_shows_technology_distribution()
```

### **Unit Tests**

#### **GetGlobalDomainRankingUseCaseTest.php**
```php
test_execute_returns_domains_sorted_by_score()
test_execute_can_sort_by_volume()
test_execute_can_sort_by_success_rate()
test_execute_filters_by_date_range()
test_execute_handles_no_domains()
test_execute_calculates_score_correctly()
```

---

## 💡 Insights Automáticos Sugeridos

Cada endpoint pode retornar insights automáticos baseados nos dados:

```json
{
  "insights": [
    "smarterhome.ai tem 143% mais requisições que zip.50g.io",
    "broadbandcheck.io tem a melhor velocidade média (2,014 Mbps)",
    "ispfinder.net tem a menor taxa de sucesso (84.4%)",
    "Mobile é a tecnologia mais usada (36.3% das requisições)",
    "California representa 23.6% de todo o tráfego",
    "Viasat e Verizon dominam em todos os domínios"
  ]
}
```

---

## 🎨 Visualizações Sugeridas no Frontend

### **1. Dashboard Global**
- Cards com métricas globais totais
- Gráfico de pizza: Distribuição de requests por domínio
- Tabela comparativa: Métricas lado a lado

### **2. Ranking de Domínios**
- Tabela ordenável por diferentes métricas
- Badges de "Top Performer", "Growing", "Needs Attention"
- Mini-sparklines de trends

### **3. Mapa Comparativo**
- Mapa dos EUA com layers por domínio
- Heatmap mostrando fortalezas de cada domínio por estado
- Toggle para alternar entre domínios

### **4. Análise de Tecnologias**
- Stacked bar chart: Tecnologias por domínio
- Tabela de performance por tecnologia
- Insights de preferências

---

## 🔧 Detalhes de Implementação

### **Cálculo do Score Combinado**

```php
$score = ($totalRequests / 1000) * ($successRate / 100) * (log($avgSpeed + 1) / 10);

// Normalizar para 0-100
$normalizedScore = min(100, $score);
```

### **Filtros de Data**

```php
if ($dateFrom && $dateTo) {
    $reports = Report::whereBetween('report_date', [$dateFrom, $dateTo])
        ->where('status', 'processed')
        ->get();
}
```

### **Agregação Eficiente**

```php
// Use DB queries para performance
$ranking = DB::table('report_summaries as rs')
    ->join('reports as r', 'r.id', '=', 'rs.report_id')
    ->join('domains as d', 'd.id', '=', 'r.domain_id')
    ->select(
        'd.id',
        'd.name',
        DB::raw('SUM(rs.total_requests) as total_requests'),
        DB::raw('AVG(rs.success_rate) as avg_success_rate'),
        DB::raw('COUNT(r.id) as report_count')
    )
    ->where('d.is_active', true)
    ->where('r.status', 'processed')
    ->groupBy('d.id', 'd.name')
    ->orderByDesc('total_requests')
    ->get();
```

---

## 🎯 Resumo da Proposta

| Relatório | Prioridade | Complexidade | Valor para Usuário |
|-----------|-----------|--------------|-------------------|
| Ranking de Domínios | 🔴 ALTA | Média | ⭐⭐⭐⭐⭐ |
| Comparação Direta | 🔴 ALTA | Baixa | ⭐⭐⭐⭐⭐ |
| Métricas Globais | 🟡 MÉDIA | Baixa | ⭐⭐⭐⭐ |
| Análise de Tecnologias | 🟡 MÉDIA | Média | ⭐⭐⭐⭐ |
| Análise Geográfica | 🟢 BAIXA | Alta | ⭐⭐⭐ |
| Trends Temporais | 🟢 BAIXA | Alta | ⭐⭐⭐ |
| Performance Benchmark | 🟢 BAIXA | Média | ⭐⭐⭐ |

---

## 🚀 Sugestão de Implementação

**Começar com FASE 1** (2 endpoints mais importantes):

1. **Ranking de Domínios** - Permite identificar líderes
2. **Comparação Direta** - Permite análise detalhada

Estes 2 endpoints cobrem 80% das necessidades de análise cross-domain e são relativamente simples de implementar.

---

## 📚 Próximos Passos

1. Revisar e aprovar esta proposta
2. Implementar FASE 1 (Ranking + Comparação)
3. Criar testes Feature e Unit
4. Documentar endpoints
5. Implementar FASE 2 se necessário

---

🎉 **Proposta completa de relatórios cross-domain pronta para implementação!**
