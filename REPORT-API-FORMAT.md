# 📋 Report API Format Guide

## Endpoint
```
POST /api/reports/submit
Authorization: Bearer {API_KEY}
Content-Type: application/json
```

---

## ✅ Formato Correto dos Campos

### 🔴 CAMPOS OBRIGATÓRIOS

#### 1. **source** (obrigatório)
```json
{
  "source": {
    "domain": "zip.50g.io",        // string, max:255
    "site_id": "wp-prod-001",      // string, max:255
    "site_name": "My Site"         // string, max:255
  }
}
```

#### 2. **metadata** (obrigatório)
```json
{
  "metadata": {
    "report_date": "2025-06-27",                    // formato: Y-m-d
    "report_period": {
      "start": "2025-06-27 00:00:00",               // formato: Y-m-d H:i:s
      "end": "2025-06-27 23:59:59"                  // formato: Y-m-d H:i:s
    },
    "generated_at": "2025-11-12 22:28:16",          // formato: Y-m-d H:i:s
    "total_processing_time": 120,                   // integer (segundos)
    "data_version": "2.0.0"                         // string, max:20
  }
}
```

#### 3. **summary** (obrigatório)
```json
{
  "summary": {
    "total_requests": 114,              // integer
    "success_rate": 90.35,              // numeric, 0-100
    "failed_requests": 11,              // integer
    "avg_requests_per_hour": 4.75,     // numeric
    "unique_providers": 84,             // integer
    "unique_states": 20,                // integer
    "unique_zip_codes": 70              // integer
  }
}
```

---

### 🟢 CAMPOS OPCIONAIS

#### 4. **geographic** (opcional)

⚠️ **ATENÇÃO**: Use **array de objetos**, NÃO objeto de chave-valor!

##### ❌ ERRADO:
```json
{
  "geographic": {
    "states": {
      "CA": 32,
      "NY": 14
    }
  }
}
```

##### ✅ CORRETO:
```json
{
  "geographic": {
    "states": [
      {
        "code": "CA",              // obrigatório, string, exatamente 2 caracteres
        "name": "California",      // obrigatório, string, max:100
        "request_count": 32,       // obrigatório, integer >= 0
        "success_rate": 90.5,      // opcional, numeric, 0-100
        "avg_speed": 1500.0        // opcional, numeric >= 0
      },
      {
        "code": "NY",
        "name": "New York",
        "request_count": 14,
        "success_rate": 85.0,
        "avg_speed": 1200.0
      }
    ],
    "top_cities": [
      {
        "name": "New York",        // obrigatório, string, max:255
        "request_count": 9,        // obrigatório, integer >= 0
        "zip_codes": ["10001", "10038"]  // opcional, array
      }
    ],
    "top_zip_codes": [
      {
        "zip_code": "10600",       // obrigatório, string/integer
        "request_count": 8,        // obrigatório, integer >= 0
        "percentage": 7.02         // opcional, numeric, 0-100
      }
    ]
  }
}
```

---

#### 5. **providers** (opcional)

##### ❌ ERRADO:
```json
{
  "providers": {
    "available": {
      "AT&T": 86,
      "Spectrum": 54
    }
  }
}
```

##### ✅ CORRETO:
```json
{
  "providers": {
    "top_providers": [
      {
        "name": "AT&T",            // obrigatório, string, max:255
        "total_count": 86,         // obrigatório, integer >= 0
        "technology": "Fiber",     // opcional, string, max:50
        "success_rate": 95.0,      // opcional, numeric, 0-100
        "avg_speed": 2000.0        // opcional, numeric >= 0
      },
      {
        "name": "Spectrum",
        "total_count": 54,
        "technology": "Cable",
        "success_rate": 88.0,
        "avg_speed": 1500.0
      }
    ],
    "by_state": {
      "CA": ["AT&T", "Spectrum"],
      "NY": ["Verizon", "Optimum"]
    }
  }
}
```

---

#### 6. **technology_metrics** (opcional)

✅ Para `distribution`, pode usar objeto de chave-valor:
```json
{
  "technology_metrics": {
    "distribution": {
      "Mobile Wireless": 1416,
      "Fiber": 560,
      "DSL": 453,
      "Cable": 320
    },
    "by_state": {
      "CA": {"Fiber": 200, "Cable": 100},
      "NY": {"Fiber": 150, "DSL": 80}
    }
  }
}
```

---

#### 7. **performance** (opcional)

✅ `hourly_distribution` usa objeto de chave-valor:
```json
{
  "performance": {
    "hourly_distribution": {
      "0": 5,
      "1": 3,
      "12": 4,
      "13": 2,
      "23": 8
    },
    "avg_response_time": 0.5,     // numeric >= 0 (segundos)
    "min_response_time": 0.1,     // numeric >= 0
    "max_response_time": 2.5,     // numeric >= 0
    "search_types": {
      "address": 50,
      "zipcode": 30,
      "coordinates": 20
    }
  }
}
```

---

#### 8. **speed_metrics** (opcional)
```json
{
  "speed_metrics": {
    "overall": {
      "avg": 1502.89,
      "max": 219000,
      "min": 10
    },
    "by_state": {
      "CA": {"avg": 1800, "max": 5000},
      "NY": {"avg": 1200, "max": 3000}
    },
    "by_provider": {
      "AT&T": {"avg": 2000, "max": 5000},
      "Spectrum": {"avg": 1500, "max": 3000}
    }
  }
}
```

---

#### 9. **exclusion_metrics** (opcional)

✅ Pode usar objetos de chave-valor:
```json
{
  "exclusion_metrics": {
    "total_exclusions": 40,
    "exclusion_rate": 35.1,
    "by_state": {
      "CA": 15,
      "NY": 10,
      "TX": 15
    },
    "by_provider": {
      "GeoLinks": 22,
      "Viasat": 18
    }
  }
}
```

---

#### 10. **health** (opcional)
```json
{
  "health": {
    "status": "healthy",              // string, max:20
    "uptime_percentage": 99.9,        // numeric, 0-100
    "avg_cpu_usage": 45.5,            // numeric, 0-100
    "avg_memory_usage": 2048.5,       // numeric >= 0 (MB)
    "disk_usage": 75.2,               // numeric >= 0 (%)
    "last_cron_run": "2025-11-12 22:00:00"  // formato: Y-m-d H:i:s
  }
}
```

---

## 📝 Resumo das Diferenças

| Campo | Formato Esperado |
|-------|-----------------|
| `geographic.states` | ✅ **Array de objetos** |
| `geographic.top_cities` | ✅ **Array de objetos** |
| `geographic.top_zip_codes` | ✅ **Array de objetos** |
| `providers.top_providers` | ✅ **Array de objetos** |
| `technology_metrics.distribution` | ✅ Objeto chave-valor |
| `performance.hourly_distribution` | ✅ Objeto chave-valor |
| `exclusion_metrics.by_provider` | ✅ Objeto chave-valor |

---

## 🧪 Exemplo Completo Válido

Ver arquivo: `REPORT-FORMAT-EXAMPLE.json`

---

## 🔍 Como Testar

```bash
# Validar JSON
cat /path/to/your/report.json | jq .

# Enviar para API
curl -X POST https://dash3.50g.io/api/reports/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json" \
  -d @/path/to/your/report.json

# Ver resposta formatada
curl -s -X POST https://dash3.50g.io/api/reports/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json" \
  -d @/path/to/your/report.json | jq .
```

---

## 🚨 Erros Comuns

### Erro: "State code is required when states is provided"
**Causa**: Enviando `geographic.states` como objeto `{"CA": 32}` ao invés de array de objetos.

**Solução**: Converter para:
```json
"states": [
  {"code": "CA", "name": "California", "request_count": 32}
]
```

### Erro: "Provider name is required when top_providers is provided"
**Causa**: Enviando `providers.available` ao invés de `providers.top_providers`.

**Solução**: Renomear para `top_providers` e usar array de objetos.

### Erro: "Report date must be in Y-m-d format"
**Causa**: Formato de data incorreto.

**Solução**: 
- ✅ `"2025-06-27"` (correto)
- ❌ `"27/06/2025"` (errado)
- ❌ `"2025-06-27T00:00:00Z"` (errado para `report_date`)

---

## 📞 Suporte

Se precisar de ajuda:
1. Verifique os logs: `pm2 logs addresses-dashboard-backend`
2. Verifique jobs falhados: `php artisan queue:failed`
3. Teste validação: Use um JSON mínimo primeiro, depois adicione campos opcionais gradualmente

