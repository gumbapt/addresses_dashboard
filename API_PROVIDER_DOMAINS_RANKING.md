# 🏆 API - Ranking de Domínios por Provider

## 🎯 Caso de Uso

**Selecionar UM provider** (ex: Spectrum) e ver **quais domínios mais consultam aquele provider**.

Exemplo: "Quais sites mais pesquisam Spectrum?"

---

## 📡 Endpoint

```
GET /api/admin/reports/global/provider-ranking
```

**Auth:** Bearer Token

---

## 🔧 Query Parameters

| Parâmetro | Descrição | Exemplo |
|-----------|-----------|---------|
| `provider_id` | **ID do provider** (obrigatório para filtrar) | `5` |
| `sort_by` | Ordenar por (opcional) | `total_requests` |
| `limit` | Limitar resultados (opcional) | `10` |
| `technology` | Filtrar por tecnologia (opcional) | `Fiber` |
| `date_from` | Data inicial (opcional) | `2025-11-01` |
| `date_to` | Data final (opcional) | `2025-11-30` |

---

## 📊 Exemplo 1: Top 10 Domínios - Earthlink

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?provider_id=5&limit=10
Authorization: Bearer {token}
```

### **Response:**
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
        "provider_id": 5,
        "provider_name": "Earthlink",
        "technology": "Unknown",
        "total_requests": 416,
        "avg_success_rate": 0.0,
        "avg_speed": 0.0,
        "total_reports": 3,
        "period_start": "2025-11-10",
        "period_end": "2025-11-10",
        "days_covered": 1,
        "domain_total_requests": 2236,
        "percentage_of_domain": 18.60
      },
      {
        "rank": 2,
        "domain_id": 5,
        "domain_name": "broadbandcheck.io",
        "domain_slug": "broadbandcheck-io",
        "provider_id": 5,
        "provider_name": "Earthlink",
        "technology": "Unknown",
        "total_requests": 197,
        "avg_success_rate": 0.0,
        "avg_speed": 0.0,
        "total_reports": 3,
        "period_start": "2025-11-10",
        "period_end": "2025-11-10",
        "days_covered": 1,
        "domain_total_requests": 2211,
        "percentage_of_domain": 8.91
      }
    ],
    "total_entries": 5,
    "filters": {
      "provider_id": 5,
      "technology": null,
      "date_from": null,
      "date_to": null,
      "sort_by": "total_requests",
      "limit": 10
    }
  }
}
```

**Interpretação:**
- **smarterhome.ai** tem **416 requests** de Earthlink
- Isso representa **18.60%** de todas as 2,236 requests deste domínio
- **broadbandcheck.io** tem **197 requests** de Earthlink
- Isso representa **8.91%** de todas as 2,211 requests deste domínio

---

## 📊 Exemplo 2: Top 10 Domínios - Spectrum

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?provider_id=15&limit=10
Authorization: Bearer {token}
```

### **Response:**
```json
{
  "success": true,
  "data": {
    "ranking": [
      {
        "rank": 1,
        "domain_name": "zip.50g.io",
        "provider_name": "Spectrum",
        "total_requests": 320,
        "domain_total_requests": 1500,
        "percentage_of_domain": 21.33
      }
    ]
  }
}
```

**Interpretação:**
- zip.50g.io tem 320 consultas de Spectrum
- Spectrum representa 21.33% de todas as consultas deste domínio

---

## 📊 Exemplo 3: Top Cable Providers por Success Rate

### **Request:**
```http
GET /api/admin/reports/global/provider-ranking?technology=Cable&sort_by=success_rate&limit=20
Authorization: Bearer {token}
```

**Retorna:** Top 20 domain+provider (Cable) ordenado por success rate

---

## 🔢 IDs dos Principais Providers

Para descobrir IDs, use:

### **GET /api/admin/providers**
```http
GET /api/admin/providers
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {"id": 5, "name": "Earthlink", "slug": "earthlink"},
    {"id": 1, "name": "HughesNet", "slug": "hughesnet"},
    {"id": 6, "name": "AT&T", "slug": "att"},
    {"id": 7, "name": "Verizon", "slug": "verizon"},
    {"id": 15, "name": "Spectrum", "slug": "spectrum"}
  ]
}
```

**Principais (por volume atual):**
```
ID  5: Earthlink  - 1,137 requests
ID  1: HughesNet  - 1,069 requests
ID  6: AT&T       - 908 requests
ID  8: GeoLinks   - 186 requests
ID 12: Cox        - 149 requests
```

---

## 📋 Campos do Response

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `rank` | int | Posição no ranking (1, 2, 3...) |
| `domain_id` | int | ID do domínio |
| `domain_name` | string | Nome do domínio |
| `provider_id` | int | ID do provider |
| `provider_name` | string | Nome do provider |
| `technology` | string | Fiber, Cable, DSL, Mobile, Satellite |
| `total_requests` | int | **Requests deste provider neste domínio** |
| `domain_total_requests` | int | **Total de requests do domínio (todos providers)** |
| `percentage_of_domain` | float | **% que este provider representa (0-100)** |
| `avg_success_rate` | float | Taxa de sucesso média (0-100) |
| `avg_speed` | float | Velocidade média (ms) |
| `total_reports` | int | Quantidade de reports |
| `period_start` | date | Data inicial (YYYY-MM-DD) |
| `period_end` | date | Data final (YYYY-MM-DD) |
| `days_covered` | int | Dias cobertos |

---

## 🎯 Interface Sugerida

### **Página: Provider Domain Rankings**

**Elementos:**
1. Dropdown para selecionar provider
2. Filtros (tecnologia, período, ordenação)
3. Tabela com colunas:
   - Rank
   - Domain Name
   - Requests (absoluto)
   - Percentage (% do total do domínio)
   - Success Rate
   - Technology

**Exemplo de linha:**
```
#1  smarterhome.ai  416 requests  18.60%  85.5%  Cable
```

**Interpretação:**
- smarterhome.ai é #1 em Earthlink
- Tem 416 requests de Earthlink
- Earthlink representa 18.60% do tráfego deste domínio
- Success rate de 85.5%

---

## 🧪 Testar via cURL

### **Top 10 Earthlink:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=5&limit=10" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking[] | "\(.rank). \(.domain_name) - \(.total_requests) req (\(.percentage_of_domain)%)"'
```

**Output:**
```
1. smarterhome.ai - 416 req (18.60%)
2. broadbandcheck.io - 197 req (8.91%)
3. ispfinder.net - 190 req (11.10%)
4. zip.50g.io - 167 req (12.21%)
5. fiberfinder.com - 167 req (12.21%)
```

---

### **Top 10 AT&T:**
```bash
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?provider_id=6&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

---

### **Top Cable - Ordenado por Porcentagem:**
```bash
# Buscar Cable e ordenar por volume (providers que dominam seus domínios)
curl "http://localhost:8007/api/admin/reports/global/provider-ranking?technology=Cable&sort_by=total_requests&limit=20" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 💡 Lógica da Porcentagem

### **Cálculo:**
```
percentage_of_domain = (total_requests / domain_total_requests) * 100

Exemplo:
- Domínio smarterhome.ai tem 2,236 requests no total (todos providers)
- Earthlink tem 416 requests neste domínio
- Porcentagem: (416 / 2,236) × 100 = 18.60%
```

### **Interpretação:**
- **18.60%** significa que Earthlink representa quase 1/5 de todas as consultas de smarterhome.ai
- Se fosse **50%**, Earthlink seria metade do tráfego
- Se fosse **100%**, seria o único provider (improvável)

---

## 📈 Casos de Uso Práticos

### **1. Descobrir Dependência de Provider**
```
Pergunta: "Qual domínio depende mais de Spectrum?"
Request: ?provider_id=15&sort_by=total_requests&limit=1
Response: Domain X com Y% de todas as requests sendo Spectrum
```

### **2. Diversificação de Providers**
```
Pergunta: "Quais domínios têm boa distribuição de providers?"
Request: ?provider_id=X para cada provider
Análise: Se nenhum provider tem >30%, boa diversificação
```

### **3. Monitorar Concentração**
```
Alerta: Se um provider representa >80% em um domínio
Request: ?provider_id=X
Verificar: Se percentage_of_domain > 80
```

---

## ✅ Resumo

**API:** `GET /api/admin/reports/global/provider-ranking`

**Principal Filtro:** `provider_id` - Seleciona o provider

**Novos Campos Adicionados:**
- ✅ `domain_total_requests` - Total do domínio (todos providers)
- ✅ `percentage_of_domain` - % que este provider representa

**Exemplo "Top Spectrum":**
```http
GET /api/admin/reports/global/provider-ranking?provider_id=15&limit=10

Retorna:
- Top 10 domínios com mais requests de Spectrum
- Para cada: quantidade absoluta + % do total do domínio
```

---

**Status:** ✅ Implementado e testado  
**Backward Compatible:** ✅ Sim (novos campos adicionados ao response existente)

