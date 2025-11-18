# 📊 Guia Completo: Formato de Submit Report

## 🎯 **Visão Geral**

Este documento explica **exatamente** como enviar reports para que **TODOS** os gráficos e métricas funcionem corretamente no dashboard.

---

## 📋 **Índice**

1. [Endpoints Disponíveis](#endpoints-disponíveis)
2. [Formato Completo Recomendado](#formato-completo-recomendado)
3. [Campos por Gráfico/Métrica](#campos-por-gráficométrica)
4. [Formatos Alternativos Aceitos](#formatos-alternativos-aceitos)
5. [Por Que o Seeder Funciona](#por-que-o-seeder-funciona)
6. [Conversões Automáticas](#conversões-automáticas)
7. [Exemplos Práticos](#exemplos-práticos)

---

## 🔌 **Endpoints Disponíveis**

### 1. `/api/reports/submit` (Recomendado)
- **UseCase**: `CreateReportUseCase`
- **Formato**: Novo formato padronizado
- **Validação**: `SubmitReportRequest`
- **Conversão**: Normaliza formatos antigos automaticamente

### 2. `/api/reports/submit-daily` (Legado)
- **UseCase**: `CreateDailyReportUseCase`
- **Formato**: Formato WordPress antigo
- **Validação**: `SubmitDailyReportRequest`
- **Conversão**: Converte formato diário para formato do sistema

---

## ✅ **Formato Completo Recomendado**

### **Estrutura Base (Obrigatória)**

```json
{
  "source": {
    "domain": "zip.50g.io",
    "site_id": "wp-prod-001",
    "site_name": "SmarterHome.ai"
  },
  "metadata": {
    "report_date": "2025-11-14",
    "report_period": {
      "start": "2025-11-14 00:00:00",
      "end": "2025-11-14 23:59:59"
    },
    "generated_at": "2025-11-14 23:59:59",
    "total_processing_time": 120,
    "data_version": "2.0.0"
  },
  "summary": {
    "total_requests": 1000,
    "success_rate": 85.5,
    "failed_requests": 145,
    "avg_requests_per_hour": 41.67,
    "unique_providers": 45,
    "unique_states": 15,
    "unique_zip_codes": 75
  }
}
```

---

## 📊 **Campos por Gráfico/Métrica**

### 1. **Gráfico de Distribuição de Tecnologia** 🔴 CRÍTICO

**Campo necessário**: `technology_metrics.distribution`

```json
{
  "technology_metrics": {
    "distribution": {
      "Fiber": 560,
      "Cable": 450,
      "DSL": 320,
      "Fixed Wireless": 280,
      "Mobile Wireless": 1416,
      "Satellite": 150
    }
  }
}
```

**⚠️ IMPORTANTE**: Se não enviar este campo, o sistema tenta calcular automaticamente a partir de `providers.top_providers[].technology`, mas é **recomendado** enviar explicitamente.

**Por quê?**: O cálculo automático pode não ser 100% preciso se houver providers sem tecnologia definida.

---

### 2. **Gráfico de Distribuição de Providers**

**Campo necessário**: `providers.top_providers`

```json
{
  "providers": {
    "top_providers": [
      {
        "name": "AT&T",
        "total_count": 86,
        "technology": "Fiber",
        "success_rate": 95.0,
        "avg_speed": 2000.0
      },
      {
        "name": "Spectrum",
        "total_count": 54,
        "technology": "Cable",
        "success_rate": 88.0,
        "avg_speed": 1500.0
      }
    ]
  }
}
```

**Campos obrigatórios**:
- `name` (string)
- `total_count` (integer)

**Campos opcionais**:
- `technology` (string) - Usado para calcular technology_metrics se não enviado
- `success_rate` (numeric, 0-100)
- `avg_speed` (numeric, Mbps)

---

### 3. **Gráfico de Estados (Top States)**

**Campo necessário**: `geographic.states`

```json
{
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "success_rate": 90.5,
        "avg_speed": 1500.0
      },
      {
        "code": "NY",
        "name": "New York",
        "request_count": 14,
        "success_rate": 85.0,
        "avg_speed": 1200.0
      }
    ]
  }
}
```

**Campos obrigatórios**:
- `code` (string, exatamente 2 caracteres)
- `name` (string)
- `request_count` (integer)

**Campos opcionais**:
- `success_rate` (numeric, 0-100)
- `avg_speed` (numeric, Mbps)

---

### 4. **Gráfico de Cidades (Top Cities)**

**Campo necessário**: `geographic.top_cities`

```json
{
  "geographic": {
    "top_cities": [
      {
        "name": "New York",
        "request_count": 9,
        "zip_codes": ["10001", "10038", "10600"]
      },
      {
        "name": "Los Angeles",
        "request_count": 6,
        "zip_codes": ["90001", "90012"]
      }
    ]
  }
}
```

**Campos obrigatórios**:
- `name` (string)
- `request_count` (integer)

**Campos opcionais**:
- `zip_codes` (array de strings)

---

### 5. **Gráfico de ZIP Codes (Top ZIP Codes)**

**Campo necessário**: `geographic.top_zip_codes`

```json
{
  "geographic": {
    "top_zip_codes": [
      {
        "zip_code": "10600",
        "request_count": 8,
        "percentage": 7.02
      },
      {
        "zip_code": "10038",
        "request_count": 6,
        "percentage": 5.26
      }
    ]
  }
}
```

**Campos obrigatórios**:
- `zip_code` (string ou integer)
- `request_count` (integer)

**Campos opcionais**:
- `percentage` (numeric, 0-100) - Calculado automaticamente se não enviado

---

### 6. **Gráfico de Distribuição Horária**

**Campo necessário**: `performance.hourly_distribution`

```json
{
  "performance": {
    "hourly_distribution": {
      "0": 5,
      "1": 3,
      "8": 15,
      "12": 20,
      "14": 18,
      "18": 25,
      "23": 8
    }
  }
}
```

**Formato**: Objeto chave-valor onde:
- **Chave**: Hora do dia (0-23)
- **Valor**: Número de requests naquela hora

---

### 7. **Métricas de Velocidade**

**Campo necessário**: `speed_metrics`

```json
{
  "speed_metrics": {
    "overall": {
      "avg": 1502.89,
      "max": 219000,
      "min": 10,
      "median": 1000
    },
    "by_state": {
      "CA": {
        "avg": 1800,
        "max": 5000,
        "min": 50
      }
    },
    "by_provider": {
      "AT&T": {
        "avg": 2000,
        "max": 5000,
        "min": 100
      }
    }
  }
}
```

---

### 8. **Métricas de Exclusão**

**Campo necessário**: `exclusion_metrics`

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

## 🔄 **Formatos Alternativos Aceitos**

O sistema aceita **múltiplos formatos** e converte automaticamente:

### **Formato 1: Novo (Recomendado)**
```json
{
  "technology_metrics": {
    "distribution": {"Fiber": 560, "Cable": 450}
  }
}
```
✅ **Usado direto** - Não precisa conversão

---

### **Formato 2: WordPress Antigo (daily_reports)**
```json
{
  "data": {
    "technologies": {"Fiber": 560, "Cable": 450}
  }
}
```
✅ **Convertido automaticamente** para `technology_metrics.distribution`

---

### **Formato 3: Top-Level**
```json
{
  "technologies": {"Fiber": 560, "Cable": 450}
}
```
✅ **Convertido automaticamente** para `technology_metrics.distribution`

---

### **Formato 4: Calculado a partir de Providers**
```json
{
  "providers": {
    "top_providers": [
      {"name": "AT&T", "technology": "Fiber", "total_count": 86},
      {"name": "Verizon", "technology": "Fiber", "total_count": 42}
    ]
  }
}
```
✅ **Calculado automaticamente**: Agrega `total_count` por `technology`

**Resultado calculado**:
```json
{
  "technology_metrics": {
    "distribution": {
      "Fiber": 128  // 86 + 42
    }
  }
}
```

---

## 🎯 **Por Que o Seeder Funciona**

### **Fluxo do Seeder:**

1. **Lê arquivo**: `docs/daily_reports/2025-06-28.json`
   ```json
   {
     "data": {
       "technologies": {"Fiber": 560, "Cable": 450}
     }
   }
   ```

2. **Usa**: `CreateDailyReportUseCase`
   - Função: `convertDailyToSystemFormat()`
   - Converte `data.technologies` → `technology_metrics.distribution`
   - Salva no `raw_data` com formato convertido

3. **Resultado no banco**:
   ```json
   {
     "technology_metrics": {
       "distribution": {"Fiber": 560, "Cable": 450}
     }
   }
   ```

4. **Dashboard lê**: `GetDashboardDataUseCase.getTechnologyDistribution()`
   - Busca `raw_data['technology_metrics']['distribution']`
   - ✅ **Encontra e exibe corretamente!**

---

## 🔧 **Conversões Automáticas**

### **CreateReportUseCase** (API `/api/reports/submit`)

**Função**: `normalizeTechnologyMetrics()`

**Ordem de verificação**:
1. ✅ `technology_metrics` → Usa direto
2. ✅ `data.technologies` → Converte para `technology_metrics.distribution`
3. ✅ `technologies` (top-level) → Converte para `technology_metrics.distribution`
4. ✅ `providers.top_providers[].technology` → **Calcula** agregando por tecnologia

---

### **CreateDailyReportUseCase** (Seeder)

**Função**: `convertTechnologyMetrics()`

**Ordem de verificação**:
1. ✅ `technology_metrics` → Usa direto
2. ✅ `data.technologies` → Converte para `technology_metrics.distribution`
3. ✅ `technologies` (top-level) → Converte para `technology_metrics.distribution`

---

## 📝 **Exemplos Práticos**

### **Exemplo 1: Formato Mínimo (Funciona)**

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
      },
      {
        "name": "Spectrum",
        "total_count": 30,
        "technology": "Cable"
      }
    ]
  }
}
```

**Resultado**: 
- ✅ `technology_metrics.distribution` será **calculado automaticamente**:
  - Fiber: 50
  - Cable: 30

---

### **Exemplo 2: Formato Completo (Recomendado)**

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
    "total_processing_time": 120,
    "data_version": "2.0.0"
  },
  "summary": {
    "total_requests": 1000,
    "success_rate": 85.5,
    "failed_requests": 145,
    "avg_requests_per_hour": 41.67,
    "unique_providers": 45,
    "unique_states": 15,
    "unique_zip_codes": 75
  },
  "technology_metrics": {
    "distribution": {
      "Fiber": 560,
      "Cable": 450,
      "DSL": 320,
      "Mobile Wireless": 1416,
      "Fixed Wireless": 280,
      "Satellite": 150
    }
  },
  "providers": {
    "top_providers": [
      {
        "name": "AT&T",
        "total_count": 86,
        "technology": "Fiber",
        "success_rate": 95.0,
        "avg_speed": 2000.0
      },
      {
        "name": "Spectrum",
        "total_count": 54,
        "technology": "Cable",
        "success_rate": 88.0,
        "avg_speed": 1500.0
      }
    ]
  },
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "success_rate": 90.5,
        "avg_speed": 1500.0
      }
    ],
    "top_cities": [
      {
        "name": "New York",
        "request_count": 9,
        "zip_codes": ["10001", "10038"]
      }
    ],
    "top_zip_codes": [
      {
        "zip_code": "10600",
        "request_count": 8,
        "percentage": 7.02
      }
    ]
  },
  "performance": {
    "hourly_distribution": {
      "0": 5,
      "12": 20,
      "18": 25
    }
  },
  "speed_metrics": {
    "overall": {
      "avg": 1502.89,
      "max": 219000,
      "min": 10
    }
  },
  "exclusion_metrics": {
    "by_provider": {
      "GeoLinks": 22,
      "Viasat": 18
    }
  }
}
```

---

## 🎯 **Resumo: O Que É Necessário para Cada Gráfico**

| Gráfico/Métrica | Campo Necessário | Obrigatório? |
|-----------------|------------------|--------------|
| **Distribuição de Tecnologia** | `technology_metrics.distribution` | ⚠️ Calculado se não enviado |
| **Distribuição de Providers** | `providers.top_providers` | ✅ Sim |
| **Top States** | `geographic.states` | ⚠️ Opcional |
| **Top Cities** | `geographic.top_cities` | ⚠️ Opcional |
| **Top ZIP Codes** | `geographic.top_zip_codes` | ⚠️ Opcional |
| **Distribuição Horária** | `performance.hourly_distribution` | ⚠️ Opcional |
| **Métricas de Velocidade** | `speed_metrics` | ⚠️ Opcional |
| **Métricas de Exclusão** | `exclusion_metrics` | ⚠️ Opcional |
| **KPIs Gerais** | `summary.*` | ✅ Sim |

---

## ⚠️ **Campos Críticos**

### **Para o Gráfico de Tecnologia Funcionar:**

**Opção 1** (Recomendado): Enviar explicitamente
```json
{
  "technology_metrics": {
    "distribution": {"Fiber": 560, "Cable": 450}
  }
}
```

**Opção 2**: Enviar `providers.top_providers` com `technology` em cada provider
```json
{
  "providers": {
    "top_providers": [
      {"name": "AT&T", "technology": "Fiber", "total_count": 86}
    ]
  }
}
```
⚠️ Será calculado automaticamente, mas pode não ser 100% preciso.

---

## 🔍 **Por Que o Seeder Funciona e a API Não Funcionava?**

### **Seeder:**
1. Lê arquivo com `data.technologies` ✅
2. `CreateDailyReportUseCase` converte para `technology_metrics.distribution` ✅
3. Salva no `raw_data` com formato correto ✅
4. Dashboard encontra e exibe ✅

### **API (Antes da Correção):**
1. WordPress envia `providers.top_providers[].technology` ✅
2. `CreateReportUseCase` **NÃO calculava** `technology_metrics` ❌
3. Salva no `raw_data` **SEM** `technology_metrics` ❌
4. Dashboard não encontra → mostra "Unknown" ❌

### **API (Depois da Correção):**
1. WordPress envia `providers.top_providers[].technology` ✅
2. `CreateReportUseCase` **CALCULA** `technology_metrics` automaticamente ✅
3. Salva no `raw_data` **COM** `technology_metrics` ✅
4. Dashboard encontra e exibe ✅

---

## 📞 **Suporte**

### **Verificar se o report foi salvo corretamente:**

```bash
cd /home/address3/addresses_dashboard
php artisan tinker --execute="
  \$report = \App\Models\Report::where('domain_id', 1)->orderBy('id', 'desc')->first();
  \$raw = \$report->raw_data;
  echo 'Tem technology_metrics? ' . (isset(\$raw['technology_metrics']) ? 'SIM ✅' : 'NÃO ❌') . PHP_EOL;
  if (isset(\$raw['technology_metrics']['distribution'])) {
    echo 'Tecnologias: ' . count(\$raw['technology_metrics']['distribution']) . PHP_EOL;
  }
"
```

### **Ver logs de processamento:**

```bash
pm2 logs addresses-dashboard-backend
pm2 logs queue-worker-reports
```

---

**Última atualização**: 2025-11-14
**Versão do documento**: 2.0
**Status**: ✅ Todas as conversões implementadas e funcionando

