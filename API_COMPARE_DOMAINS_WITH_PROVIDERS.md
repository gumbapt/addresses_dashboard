# 🔄 API - Compare Domains (Com Dados de Providers)

## 📡 Endpoint

```
GET /api/admin/reports/global/comparison
Authorization: Bearer {token}
```

---

## 🔧 Query Parameters

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `domains` | string | **IDs dos domínios separados por vírgula** | `1,2,3` |
| `metric` | string | Métrica específica (opcional) | `providers` |
| `date_from` | date | Data inicial (opcional) | `2025-11-01` |
| `date_to` | date | Data final (opcional) | `2025-11-30` |

---

## 📊 Response Completo

```json
{
  "success": true,
  "data": {
    "domains": [
      {
        "domain_id": 1,
        "domain_name": "zip.50g.io",
        "metrics": {
          "total_requests": 192,
          "success_rate": 0.0,
          "total_failed": 0,
          "total_reports": 3,
          "avg_speed": 0.0,
          "top_states": [...],
          "top_providers": [
            {
              "name": "Viasat Carrier Services Inc",
              "technology": "Satellite",
              "requests": 58
            }
          ],
          "technology_distribution": [...]
        },
        "vs_base_domain": null
      },
      {
        "domain_id": 2,
        "domain_name": "fiberfinder.com",
        "metrics": {...},
        "vs_base_domain": {
          "requests_diff": 0.0,
          "requests_diff_label": "+0.0%",
          "success_diff": 0.0,
          "success_diff_label": "+0.0%"
        }
      }
    ],
    "total_compared": 2,
    "provider_data": {
      "all_providers": [
        {
          "provider_id": 2,
          "provider_name": "Viasat Carrier Services Inc",
          "technology": "Satellite",
          "total_requests": 116,
          "avg_success_rate": 0.0,
          "avg_speed": 969.5,
          "appearances": 2
        },
        {
          "provider_id": 1,
          "provider_name": "HughesNet",
          "technology": "Satellite",
          "total_requests": 116,
          "avg_success_rate": 0.0,
          "avg_speed": 968.5,
          "appearances": 2
        }
      ],
      "common_providers": [
        {
          "provider_id": 2,
          "provider_name": "Viasat Carrier Services Inc",
          "technology": "Satellite",
          "total_requests": 116,
          "avg_success_rate": 0.0,
          "avg_speed": 969.5,
          "appearances": 2
        }
      ],
      "unique_providers_count": 35
    },
    "filters": {
      "metric": null,
      "date_from": null,
      "date_to": null
    }
  }
}
```

---

## 🆕 Novo Campo: `provider_data`

### **Estrutura:**

```json
"provider_data": {
  "all_providers": [...],
  "common_providers": [...],
  "unique_providers_count": 35
}
```

---

### **1. `all_providers`** - Todos os Providers (Agregados)

Lista de TODOS os providers que aparecem nos domínios comparados (soma dos dados):

```json
"all_providers": [
  {
    "provider_id": 2,
    "provider_name": "Viasat Carrier Services Inc",
    "technology": "Satellite",
    "total_requests": 116,
    "avg_success_rate": 0.0,
    "avg_speed": 969.5,
    "appearances": 2
  }
]
```

**Campos:**
- `total_requests` - Soma de todos os requests deste provider nos domínios comparados
- `avg_success_rate` - Média de success rate
- `avg_speed` - Média de velocidade
- `appearances` - Em quantos reports este provider aparece

**Uso:** Mostrar "Top providers nos domínios comparados"

---

### **2. `common_providers`** - Providers Comuns

Providers que aparecem em **TODOS** os domínios comparados:

```json
"common_providers": [
  {
    "provider_id": 15,
    "provider_name": "Spectrum",
    "technology": "Cable",
    "total_requests": 250,
    "avg_success_rate": 88.5,
    "avg_speed": 1100
  }
]
```

**Uso:** Comparar "Como cada domínio performa com Spectrum?"

---

### **3. `unique_providers_count`**

Total de providers únicos nos domínios comparados:

```json
"unique_providers_count": 35
```

**Uso:** Métrica de diversidade

---

## 🎯 Exemplo: Comparar zip.50g.io vs fiberfinder.com

### **Request:**
```bash
GET /api/admin/reports/global/comparison?domains=1,2
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "domains": [
      {
        "domain_name": "zip.50g.io",
        "metrics": {
          "total_requests": 192,
          "success_rate": 0.0
        }
      },
      {
        "domain_name": "fiberfinder.com",
        "metrics": {
          "total_requests": 192,
          "success_rate": 0.0
        }
      }
    ],
    "provider_data": {
      "all_providers": [
        {"provider_name": "Viasat", "total_requests": 116},
        {"provider_name": "HughesNet", "total_requests": 116}
      ],
      "common_providers": [
        {"provider_name": "Viasat", "total_requests": 116}
      ],
      "unique_providers_count": 35
    }
  }
}
```

**Interpretação:**
- Ambos domínios têm 192 requests totais
- Somados, têm 116 requests de Viasat
- Viasat está presente nos 2 domínios (common)
- Total de 35 providers únicos entre os 2

---

## 🎨 Interface Sugerida

```
┌──────────────────────────────────────────────────────────────┐
│ 🔄 Compare Domains                                           │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ Comparing: zip.50g.io vs fiberfinder.com                    │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐  │
│ │ 📊 Provider Overview                                   │  │
│ │                                                        │  │
│ │ Total Unique Providers: 35                             │  │
│ │ Common Providers: 35                                   │  │
│ │                                                        │  │
│ │ Top 5 Providers (Aggregated):                          │  │
│ │ 1. Viasat             116 requests    88.5% success    │  │
│ │ 2. HughesNet          116 requests    85.0% success    │  │
│ │ 3. Verizon            112 requests    92.0% success    │  │
│ │                                                        │  │
│ │ ✅ Common in all domains:                              │  │
│ │ • Viasat (Satellite) - 116 requests                    │  │
│ │ • HughesNet (Satellite) - 116 requests                 │  │
│ └────────────────────────────────────────────────────────┘  │
│                                                              │
│ Domain Comparison:                                           │
│ ─────────────────────────────────────────────────────────   │
│ Metric         | zip.50g.io | fiberfinder.com | Diff       │
│ ─────────────────────────────────────────────────────────   │
│ Requests       | 192        | 192             | +0%         │
│ Success Rate   | 0%         | 0%              | +0%         │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## 🎯 Uso no Nuxt

```javascript
const domain1Id = 1;
const domain2Id = 2;

const { data } = await $fetch('/api/admin/reports/global/comparison', {
  params: {
    domains: `${domain1Id},${domain2Id}`
  }
});

// Dados dos domínios
const domains = data.domains;

// Provider data agregado
const providerData = data.provider_data;

// Renderizar "Top Providers"
providerData.all_providers.forEach(p => {
  console.log(`${p.provider_name}: ${p.total_requests} requests (${p.appearances} reports)`);
});

// Renderizar "Common Providers"
providerData.common_providers.forEach(p => {
  console.log(`✅ ${p.provider_name} - Presente em todos os domínios`);
});

// Mostrar total de providers únicos
console.log(`Total: ${providerData.unique_providers_count} providers únicos`);
```

---

## 📋 Estrutura TypeScript

```typescript
interface CompareDomainsResponse {
  success: boolean;
  data: {
    domains: Array<{
      domain_id: number;
      domain_name: string;
      metrics: {
        total_requests: number;
        success_rate: number;
        top_providers: Array<{
          name: string;
          technology: string;
          requests: number;
        }>;
      };
      vs_base_domain: {
        requests_diff: number;
        requests_diff_label: string;
      } | null;
    }>;
    total_compared: number;
    provider_data: {
      all_providers: Array<{
        provider_id: number;
        provider_name: string;
        technology: string;
        total_requests: number;
        avg_success_rate: number;
        avg_speed: number;
        appearances: number;
      }>;
      common_providers: Array<{...}>; // Mesmo formato
      unique_providers_count: number;
    };
    filters: {
      metric: string | null;
      date_from: string | null;
      date_to: string | null;
    };
  };
}
```

---

## 🧪 Testar

```bash
# Comparar 2 domínios
curl "http://localhost:8007/api/admin/reports/global/comparison?domains=1,2" \
  -H "Authorization: Bearer $TOKEN" \
  -s | jq '{
    domains: (.data.domains | map(.domain_name)),
    all_providers_count: (.data.provider_data.all_providers | length),
    common_providers_count: (.data.provider_data.common_providers | length),
    unique_providers: .data.provider_data.unique_providers_count
  }'
```

**Output:**
```json
{
  "domains": ["zip.50g.io", "fiberfinder.com"],
  "all_providers_count": 35,
  "common_providers_count": 35,
  "unique_providers": 35
}
```

---

## ✅ O Que Foi Adicionado

### **Antes:**
```json
{
  "data": {
    "domains": [...]
  }
}
```

### **Agora:**
```json
{
  "data": {
    "domains": [...],
    "provider_data": {
      "all_providers": [...],        // ← NOVO
      "common_providers": [...],     // ← NOVO
      "unique_providers_count": 35   // ← NOVO
    }
  }
}
```

---

## 🎯 Casos de Uso

### **1. Comparar Performance de Providers**
```
Pergunta: "Quais providers são comuns entre os domínios?"
Response: common_providers mostra providers presentes em todos
```

### **2. Análise de Cobertura**
```
Pergunta: "Quantos providers diferentes existem?"
Response: unique_providers_count = 35
```

### **3. Top Providers Agregados**
```
Pergunta: "Qual provider tem mais volume nos domínios comparados?"
Response: all_providers[0] = provider com maior total_requests
```

---

## ✅ Status

**Implementado:**
- ✅ Dados agregados de providers
- ✅ Providers comuns entre domínios
- ✅ Contador de providers únicos
- ✅ Soma de requests por provider
- ✅ Médias de success_rate e speed

**Testes:** 3/3 passando ✅

**Pronto para usar!** 🚀

