# 📊 Guia: Adicionar Dados de Velocidade ao Report do WordPress

## 🔍 **Problema Identificado**

O gráfico de velocidade por estado não aparece para reports enviados diretamente pelo WordPress, mas funciona para reports gerados pelo seeder.

### **Diferença Entre os Formatos:**

| Campo | Seeder (Funciona) | WordPress (Não Funciona) |
|-------|-------------------|---------------------------|
| `summary.avg_speed_mbps` | ✅ Presente | ❌ Ausente |
| `summary.max_speed_mbps` | ✅ Presente | ❌ Ausente |
| `summary.min_speed_mbps` | ✅ Presente | ❌ Ausente |
| `geographic.states[].avg_speed` | ✅ Presente | ❌ Ausente |
| `speed_metrics.by_state` | ✅ Gerado pelo seeder | ❌ Ausente |

---

## ✅ **Solução: O Que o WordPress Precisa Enviar**

### **Opção 1: Adicionar Velocidade em `geographic.states` (Recomendado)**

O WordPress deve adicionar o campo `avg_speed` em cada estado:

```json
{
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "success_rate": 90.5,
        "avg_speed": 1500.0  // ✅ ADICIONAR ESTE CAMPO
      },
      {
        "code": "NY",
        "name": "New York",
        "request_count": 14,
        "success_rate": 85.0,
        "avg_speed": 1200.0  // ✅ ADICIONAR ESTE CAMPO
      }
    ]
  }
}
```

**Formato atual do WordPress:**
```json
{
  "geographic": {
    "states": [
      {
        "code": "TX",
        "name": "Texas",
        "request_count": 13
        // ❌ FALTA avg_speed
      }
    ]
  }
}
```

---

### **Opção 2: Adicionar `speed_metrics.by_state`**

Alternativamente, o WordPress pode enviar dados de velocidade em `speed_metrics.by_state`:

```json
{
  "speed_metrics": {
    "overall": {
      "avg": 1502.89,
      "max": 219000,
      "min": 10
    },
    "by_state": {
      "CA": {
        "avg": 1500.0,
        "max": 5000.0,
        "min": 50.0
      },
      "NY": {
        "avg": 1200.0,
        "max": 4000.0,
        "min": 40.0
      }
    },
    "by_provider": {
      "AT&T": {
        "avg": 2000.0,
        "max": 5000.0,
        "min": 100.0
      }
    }
  }
}
```

---

### **Opção 3: Adicionar Velocidade no `summary` (Para Compatibilidade)**

O WordPress também pode adicionar velocidade média no `summary` (usado como fallback):

```json
{
  "summary": {
    "total_requests": 1000,
    "success_rate": 85.5,
    "failed_requests": 145,
    "avg_speed_mbps": 1502.89,  // ✅ ADICIONAR ESTE CAMPO
    "max_speed_mbps": 219000,    // ✅ ADICIONAR ESTE CAMPO (opcional)
    "min_speed_mbps": 10,        // ✅ ADICIONAR ESTE CAMPO (opcional)
    "unique_providers": 45,
    "unique_states": 15,
    "unique_zip_codes": 75
  }
}
```

---

## 📋 **Comparação Completa: Seeder vs WordPress**

### **JSON do Seeder (daily_reports/2025-06-28.json):**

```json
{
  "api_version": "1.0",
  "report_type": "daily",
  "timestamp": "2025-10-16T21:24:25Z",
  "source": {...},
  "data": {
    "date": "2025-06-28",
    "summary": {
      "total_requests": 47,
      "avg_speed_mbps": 651.95,  // ✅ TEM
      "max_speed_mbps": 219000,   // ✅ TEM
      "min_speed_mbps": 10        // ✅ TEM
    },
    "geographic": {
      "states": {
        "CA": 6,  // Formato antigo: objeto chave-valor
        "NY": 4
      }
    }
  }
}
```

**O seeder então:**
1. Lê `data.summary.avg_speed_mbps`
2. Gera `speed_metrics.by_state` com velocidade para cada estado
3. O `CreateDailyReportUseCase` converte para `geographic.states[].avg_speed`

---

### **JSON do WordPress (submited_reports):**

```json
{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-zip-50g-io-prod",
    "site_name": "SmarterHome.ai"
  },
  "metadata": {...},
  "summary": {
    "total_requests": 100,
    "success_rate": 85,
    "failed_requests": 15,
    "unique_providers": 10,
    "unique_states": 5,
    "unique_zip_codes": 20
    // ❌ FALTA avg_speed_mbps, max_speed_mbps, min_speed_mbps
  },
  "geographic": {
    "states": [
      {
        "code": "TX",
        "name": "Texas",
        "request_count": 13
        // ❌ FALTA avg_speed, success_rate
      }
    ]
  }
  // ❌ FALTA speed_metrics
}
```

---

## 🎯 **Recomendação: Formato Completo para WordPress**

O WordPress deve enviar no seguinte formato:

```json
{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-zip-50g-io-prod",
    "site_name": "SmarterHome.ai"
  },
  "metadata": {
    "report_date": "2025-11-14",
    "report_period": {
      "start": "2025-11-14 00:00:00",
      "end": "2025-11-14 23:59:59"
    },
    "generated_at": "2025-11-14 23:59:59",
    "data_version": "2.0.0"
  },
  "summary": {
    "total_requests": 1000,
    "success_rate": 85.5,
    "failed_requests": 145,
    "avg_speed_mbps": 1502.89,  // ✅ ADICIONAR
    "max_speed_mbps": 219000,   // ✅ ADICIONAR (opcional)
    "min_speed_mbps": 10,       // ✅ ADICIONAR (opcional)
    "unique_providers": 45,
    "unique_states": 15,
    "unique_zip_codes": 75
  },
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "success_rate": 90.5,
        "avg_speed": 1500.0  // ✅ ADICIONAR (CRÍTICO)
      },
      {
        "code": "NY",
        "name": "New York",
        "request_count": 14,
        "success_rate": 85.0,
        "avg_speed": 1200.0  // ✅ ADICIONAR (CRÍTICO)
      }
    ],
    "top_cities": [...],
    "top_zip_codes": [...]
  },
  "speed_metrics": {  // ✅ ADICIONAR (OPCIONAL mas recomendado)
    "overall": {
      "avg": 1502.89,
      "max": 219000,
      "min": 10
    },
    "by_state": {
      "CA": {
        "avg": 1500.0,
        "max": 5000.0,
        "min": 50.0
      },
      "NY": {
        "avg": 1200.0,
        "max": 4000.0,
        "min": 40.0
      }
    },
    "by_provider": {
      "AT&T": {
        "avg": 2000.0,
        "max": 5000.0,
        "min": 100.0
      }
    }
  }
}
```

---

## 🔧 **Como Implementar no WordPress**

### **1. Adicionar `avg_speed` em `geographic.states`:**

```php
// No código do plugin WordPress
foreach ($statesData as $stateCode => $stateInfo) {
    $states[] = [
        'code' => $stateCode,
        'name' => $stateInfo['name'],
        'request_count' => $stateInfo['count'],
        'success_rate' => $stateInfo['success_rate'] ?? 0,
        'avg_speed' => $stateInfo['avg_speed'] ?? 0,  // ✅ ADICIONAR
    ];
}
```

### **2. Adicionar `avg_speed_mbps` no `summary`:**

```php
$summary = [
    'total_requests' => $totalRequests,
    'success_rate' => $successRate,
    'failed_requests' => $failedRequests,
    'avg_speed_mbps' => $avgSpeed,  // ✅ ADICIONAR
    'max_speed_mbps' => $maxSpeed,  // ✅ ADICIONAR
    'min_speed_mbps' => $minSpeed,  // ✅ ADICIONAR
    'unique_providers' => $uniqueProviders,
    'unique_states' => $uniqueStates,
    'unique_zip_codes' => $uniqueZipCodes,
];
```

### **3. Adicionar `speed_metrics` (Opcional mas Recomendado):**

```php
$speedMetrics = [
    'overall' => [
        'avg' => $avgSpeed,
        'max' => $maxSpeed,
        'min' => $minSpeed,
    ],
    'by_state' => [],
    'by_provider' => [],
];

// Preencher by_state
foreach ($statesData as $stateCode => $stateInfo) {
    $speedMetrics['by_state'][$stateCode] = [
        'avg' => $stateInfo['avg_speed'] ?? 0,
        'max' => $stateInfo['max_speed'] ?? 0,
        'min' => $stateInfo['min_speed'] ?? 0,
    ];
}

// Preencher by_provider
foreach ($providersData as $providerName => $providerInfo) {
    $speedMetrics['by_provider'][$providerName] = [
        'avg' => $providerInfo['avg_speed'] ?? 0,
        'max' => $providerInfo['max_speed'] ?? 0,
        'min' => $providerInfo['min_speed'] ?? 0,
    ];
}
```

---

## 📊 **Prioridade de Implementação**

### **🔴 CRÍTICO (Faz o gráfico funcionar):**

1. ✅ Adicionar `avg_speed` em `geographic.states[]`
   - **Impacto**: Gráfico de velocidade por estado funciona
   - **Esforço**: Baixo
   - **Recomendado**: Implementar primeiro

### **🟡 IMPORTANTE (Melhora dados agregados):**

2. ✅ Adicionar `avg_speed_mbps` no `summary`
   - **Impacto**: Dados agregados têm velocidade média
   - **Esforço**: Baixo
   - **Recomendado**: Implementar em seguida

### **🟢 OPCIONAL (Melhora flexibilidade):**

3. ✅ Adicionar `speed_metrics.by_state` e `speed_metrics.by_provider`
   - **Impacto**: Dados mais detalhados e flexíveis
   - **Esforço**: Médio
   - **Recomendado**: Implementar se houver tempo

---

## ✅ **Checklist para WordPress**

- [ ] Adicionar `avg_speed` em cada item de `geographic.states[]`
- [ ] Adicionar `avg_speed_mbps` no `summary`
- [ ] Adicionar `max_speed_mbps` no `summary` (opcional)
- [ ] Adicionar `min_speed_mbps` no `summary` (opcional)
- [ ] Adicionar `speed_metrics.by_state` (opcional)
- [ ] Adicionar `speed_metrics.by_provider` (opcional)
- [ ] Testar envio de report
- [ ] Verificar se gráfico aparece no dashboard

---

## 🧪 **Como Testar**

### **1. Enviar Report de Teste:**

```bash
curl -X POST https://dash3.50g.io/api/reports/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d @test-report-with-speed.json
```

### **2. Verificar no Dashboard:**

1. Acesse `/domains/{id}/dashboard`
2. Verifique se o gráfico "Speed by State" aparece
3. Verifique se os estados têm velocidade > 0

### **3. Verificar no Banco:**

```sql
SELECT rs.avg_speed, s.name 
FROM report_states rs 
JOIN states s ON s.id = rs.state_id 
WHERE rs.report_id = (SELECT MAX(id) FROM reports WHERE domain_id = 15)
  AND rs.avg_speed > 0;
```

---

## 📝 **Resumo**

**Problema**: WordPress não envia dados de velocidade  
**Solução**: Adicionar `avg_speed` em `geographic.states[]`  
**Prioridade**: 🔴 CRÍTICO  
**Esforço**: Baixo  
**Impacto**: Gráfico de velocidade funciona

---

---

## 📊 **Métricas de Tecnologia - Status e Recomendações**

### **Status Atual:**

O WordPress **já está enviando** `technology_metrics.distribution` corretamente:

```json
{
  "technology_metrics": {
    "distribution": {
      "Mobile": 882,
      "DSL": 301,
      "Fiber": 220,
      "Satellite": 177,
      "Cable": 169,
      "Fixed Wireless": 79,
      "Unknown": 16
    }
  }
}
```

**✅ Isso está funcionando perfeitamente!** O gráfico de tecnologia aparece corretamente para o Domain 15.

---

### **🟡 Melhoria Recomendada: Adicionar `technology` em `providers.top_providers[]`**

Embora não seja crítico (já que `technology_metrics.distribution` funciona), é recomendado adicionar `technology` em cada provider para:

1. **Consistência**: Domain 1 tem `providers[].technology`, Domain 15 também deveria
2. **Fallback**: Se `technology_metrics` não estiver presente, o sistema pode calcular
3. **Dados mais completos**: Permite análise de tecnologia por provider individual

**Formato Atual do WordPress:**
```json
{
  "providers": {
    "top_providers": [
      {
        "name": "HughesNet",
        "total_count": 61
        // ❌ FALTA technology
      }
    ]
  }
}
```

**Formato Recomendado:**
```json
{
  "providers": {
    "top_providers": [
      {
        "name": "HughesNet",
        "total_count": 61,
        "technology": "Satellite",  // ✅ ADICIONAR
        "success_rate": 87.14,      // ✅ ADICIONAR (opcional)
        "avg_speed": 500.0          // ✅ ADICIONAR (opcional)
      }
    ]
  }
}
```

**Código PHP:**
```php
foreach ($providersData as $providerName => $providerInfo) {
    $providers[] = [
        'name' => $providerName,
        'total_count' => $providerInfo['count'],
        'technology' => $providerInfo['technology'] ?? 'Unknown',  // ✅ ADICIONAR
        'success_rate' => $providerInfo['success_rate'] ?? 0,     // ✅ ADICIONAR (opcional)
        'avg_speed' => $providerInfo['avg_speed'] ?? 0,           // ✅ ADICIONAR (opcional)
    ];
}
```

---

### **📊 Comparação: Tecnologia**

| Item | Seeder (Domain 1) | WordPress (Domain 15) | Status |
|------|-------------------|----------------------|--------|
| `technology_metrics.distribution` | ❌ Não tem (gera de `data.technologies`) | ✅ **Tem e funciona** | ✅ WordPress melhor |
| `providers.top_providers[].technology` | ✅ Tem | ❌ Não tem | ⚠️ WordPress pode melhorar |
| Gráfico funciona? | ✅ Sim (fallback de providers) | ✅ Sim (direto de technology_metrics) | ✅ Ambos funcionam |

**Conclusão sobre Tecnologia:**
- ✅ WordPress já está enviando corretamente `technology_metrics.distribution`
- 🟡 Recomendado adicionar `technology` em `providers.top_providers[]` para consistência
- ✅ Gráfico de tecnologia funciona para ambos os domínios

---

**Última atualização**: November 15, 2025  
**Status**: ⚠️ Aguardando implementação no WordPress  
**Ação WordPress**: 
- ⚠️ **CRÍTICO** - Enviar dados de velocidade (`avg_speed` em estados)
- 🟡 **IMPORTANTE** - Adicionar `technology` em `providers.top_providers[]`

