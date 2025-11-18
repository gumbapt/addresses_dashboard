# 📋 Referência Rápida: Formato de Report

## 🎯 **Formato Mínimo para Funcionar**

```json
{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-001",
    "site_name": "My Site"
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
    "total_requests": 100,
    "success_rate": 85,
    "failed_requests": 15,
    "unique_providers": 10,
    "unique_states": 5,
    "unique_zip_codes": 20
  },
  "providers": {
    "top_providers": [
      {
        "name": "AT&T",
        "total_count": 50,
        "technology": "Fiber"
      }
    ]
  }
}
```

**✅ Isso já funciona!** O sistema calcula `technology_metrics` automaticamente.

---

## 🎯 **Formato Recomendado (Todos os Gráficos)**

```json
{
  "source": {...},
  "metadata": {...},
  "summary": {...},
  
  "technology_metrics": {
    "distribution": {
      "Fiber": 560,
      "Cable": 450,
      "DSL": 320
    }
  },
  
  "providers": {
    "top_providers": [
      {
        "name": "AT&T",
        "total_count": 86,
        "technology": "Fiber"
      }
    ]
  },
  
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32
      }
    ],
    "top_cities": [...],
    "top_zip_codes": [...]
  },
  
  "performance": {
    "hourly_distribution": {
      "12": 20,
      "18": 25
    }
  }
}
```

---

## 🔄 **Conversões Automáticas**

| Você Envia | Sistema Converte Para |
|------------|----------------------|
| `technology_metrics.distribution` | ✅ Usa direto |
| `data.technologies` | ✅ `technology_metrics.distribution` |
| `technologies` (top-level) | ✅ `technology_metrics.distribution` |
| `providers.top_providers[].technology` | ✅ Calcula `technology_metrics.distribution` |

---

## ❓ **Por Que o Seeder Funciona?**

**Seeder**:
1. Lê `docs/daily_reports/*.json` com `data.technologies`
2. `CreateDailyReportUseCase` converte para `technology_metrics.distribution`
3. Salva no banco com formato correto ✅

**API** (agora também funciona):
1. Recebe `providers.top_providers[].technology`
2. `CreateReportUseCase` **calcula** `technology_metrics.distribution`
3. Salva no banco com formato correto ✅

---

## 📊 **Campos por Gráfico**

| Gráfico | Campo | Obrigatório? |
|---------|-------|--------------|
| **Tecnologia** | `technology_metrics.distribution` | ⚠️ Calculado se não enviado |
| **Providers** | `providers.top_providers` | ✅ Sim |
| **Estados** | `geographic.states` | ⚠️ Opcional |
| **Cidades** | `geographic.top_cities` | ⚠️ Opcional |
| **ZIP Codes** | `geographic.top_zip_codes` | ⚠️ Opcional |
| **Horário** | `performance.hourly_distribution` | ⚠️ Opcional |

---

**Documentação completa**: `REPORT-SUBMIT-COMPLETE-GUIDE.md`

