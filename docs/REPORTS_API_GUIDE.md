# 📊 Guia da API de Relatórios

## 🎯 Conceitos Importantes

### **Relatório Individual vs Agregação**

- **Relatório Individual** (`GET /reports/{id}`): Dados de **UM dia específico**
- **Agregação** (`GET /reports/domain/{id}/aggregate`): **MERGE de TODOS os dias** de um domínio

### **Unicidade por Data**

Cada domínio pode ter **apenas 1 relatório por data**:
- ✅ Submeter para uma data nova: **CRIA** novo relatório
- ✅ Submeter para uma data existente: **ATUALIZA** o relatório

---

## 📡 Endpoints

### **1. Submeter Relatório** 
```http
POST /api/reports/submit
Headers: X-API-Key: {domain_api_key}
```

**Comportamento:**
- Se a data **não existe**: Cria novo relatório
- Se a data **já existe**: Atualiza o relatório existente e **reprocessa**

**Exemplo:**
```bash
curl -X POST "http://localhost:8006/api/reports/submit" \
  -H "X-API-Key: test_fcb4eaac..." \
  -H "Content-Type: application/json" \
  -d @docs/newdata.json
```

**Resposta:**
```json
{
  "success": true,
  "message": "Report received and queued for processing",
  "data": {
    "id": 6,
    "domain_id": 1,
    "report_date": "2025-10-11",
    "status": "pending"
  }
}
```

---

### **2. Listar Todos os Relatórios** (Admin)
```http
GET /api/admin/reports?page=1&per_page=10
Headers: Authorization: Bearer {admin_token}
```

**Exemplo:**
```bash
curl -s "http://localhost:8006/api/admin/reports?per_page=10" \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 6,
      "domain_id": 1,
      "report_date": "2025-10-11",
      "status": "processed"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

---

### **3. Ver Relatório Individual** (Admin)
```http
GET /api/admin/reports/{id}
Headers: Authorization: Bearer {admin_token}
```

**O que retorna:**
- Dados de **UM dia específico**
- Summary processado
- Top providers do dia
- Estados, cidades, CEPs processados
- Raw data original

**Exemplo:**
```bash
curl -s "http://localhost:8006/api/admin/reports/6" \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 6,
    "domain": {
      "id": 1,
      "name": "zip.50g.io"
    },
    "report_date": "2025-10-11",
    "status": "processed",
    "summary": {
      "total_requests": 1502,
      "failed_requests": 223,
      "success_rate": 85.15,
      "avg_requests_per_hour": 1.56
    },
    "providers": [
      {
        "provider_id": 49,
        "name": "Earthlink",
        "technology": "Mobile",
        "total_count": 46,
        "rank": 1
      }
    ],
    "geographic": {
      "states": [...],
      "cities": [...],
      "zip_codes": [...]
    },
    "raw_data": {...}
  }
}
```

---

### **4. Agregação por Domínio** (Admin) 🆕
```http
GET /api/admin/reports/domain/{domain_id}/aggregate
Headers: Authorization: Bearer {admin_token}
```

**O que retorna:**
- **MERGE de TODOS os relatórios** do domínio
- Summary agregado (soma total_requests, média success_rate)
- Top providers agregados
- Top estados/cidades/CEPs agregados
- Trends diários (evolução ao longo do tempo)

**Exemplo:**
```bash
curl -s "http://localhost:8006/api/admin/reports/domain/1/aggregate" \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "domain": {
      "id": 1,
      "name": "zip.50g.io"
    },
    "period": {
      "total_reports": 5,
      "first_report": "2025-10-01",
      "last_report": "2025-10-05",
      "days_covered": 5
    },
    "summary": {
      "total_requests": 7510,
      "total_failed": 1115,
      "avg_success_rate": 85.15,
      "total_unique_providers": 8,
      "total_unique_states": 43,
      "total_unique_zip_codes": 100
    },
    "providers": [
      {
        "provider_id": 49,
        "name": "Earthlink",
        "total_count": 230,
        "avg_success_rate": 85.5,
        "report_count": 5
      }
    ],
    "geographic": {
      "states": [...],
      "cities": [...],
      "zip_codes": [...]
    },
    "trends": [
      {
        "date": "2025-10-01",
        "report_id": 1,
        "total_requests": 1502,
        "success_rate": 85.15
      },
      {
        "date": "2025-10-02",
        "report_id": 2,
        "total_requests": 1502,
        "success_rate": 85.15
      }
    ]
  }
}
```

---

## 🔍 Comparação: Individual vs Agregado

### **Cenário: 5 dias de relatórios**

| Métrica | Individual (1 dia) | Agregado (5 dias) |
|---------|-------------------|-------------------|
| **Total Requests** | 1,502 | 7,510 (soma) |
| **Success Rate** | 85.15% | 85.15% (média) |
| **Providers** | 8 do dia | 8 únicos total |
| **Período** | 1 dia | 5 dias |
| **Trends** | Não tem | ✅ Evolução diária |

---

## 🧪 Testes

### **Teste 1: Submeter novo relatório**
```bash
./submit-test-report.sh
# Resultado: Cria Report #1 para 2025-10-11
```

### **Teste 2: Submeter para a MESMA data**
```bash
./submit-test-report.sh
# Resultado: Atualiza Report #1 (não cria #2)
```

### **Teste 3: Ver agregação**
```bash
TOKEN=$(curl -s http://localhost:8006/api/admin/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

curl -s "http://localhost:8006/api/admin/reports/domain/1/aggregate" \
  -H "Authorization: Bearer $TOKEN" | jq '.'
```

---

## 📝 Exemplo Completo

```bash
#!/bin/bash

# 1. Login como admin
TOKEN=$(curl -s http://localhost:8006/api/admin/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

# 2. Ver todos os relatórios
curl -s "http://localhost:8006/api/admin/reports" \
  -H "Authorization: Bearer $TOKEN" | jq '.data'

# 3. Ver relatório específico (dia 2025-10-11)
curl -s "http://localhost:8006/api/admin/reports/6" \
  -H "Authorization: Bearer $TOKEN" | jq '{
    date: .data.report_date,
    requests: .data.summary.total_requests,
    top_provider: .data.providers[0].name
  }'

# 4. Ver agregação de todos os dias
curl -s "http://localhost:8006/api/admin/reports/domain/1/aggregate" \
  -H "Authorization: Bearer $TOKEN" | jq '{
    period: .data.period,
    total_requests: .data.summary.total_requests,
    days_covered: .data.period.days_covered
  }'
```

---

## ⚙️ Processamento

### **Fluxo:**
1. **Submissão**: POST /reports/submit
2. **Job enfileirado**: `ProcessReportJob`
3. **Processamento**: Extrai dados, cria entities, popula tabelas
4. **Status**: pending → processing → processed

### **Dados Processados:**
- `report_summaries`: Estatísticas gerais
- `report_providers`: Top providers
- `report_states`: Dados por estado
- `report_cities`: Dados por cidade
- `report_zip_codes`: Dados por CEP

### **Entidades Mestres Criadas:**
- `states`: Estados únicos
- `cities`: Cidades únicas
- `zip_codes`: CEPs únicos
- `providers`: Provedores únicos

---

## 🚨 Importante

1. **Unicidade**: 1 relatório por data+domínio
2. **Atualização**: Submeter novamente **substitui** o anterior
3. **Reprocessamento**: Ao atualizar, dados processados são limpos e recriados
4. **Agregação**: Faz MERGE de todos os relatórios do domínio
5. **Individual**: Mostra dados de 1 dia específico

---

## 📚 Arquivos Relacionados

- **Controller**: `app/Http/Controllers/Api/ReportController.php`
- **Use Cases**: 
  - `GetReportWithStatsUseCase.php` (individual)
  - `GetAggregatedReportStatsUseCase.php` (agregado)
  - `CreateReportUseCase.php` (submissão com upsert)
- **Job**: `app/Jobs/ProcessReportJob.php`
- **Rotas**: `routes/api.php`
- **Scripts**: 
  - `submit-test-report.sh` (submeter)
  - `test-aggregate-endpoint.sh` (testar agregação)
  - `debug-report-flow.sh` (debug completo)

---

*Última atualização: Outubro 2024*  
*Status: ✅ Operacional*

