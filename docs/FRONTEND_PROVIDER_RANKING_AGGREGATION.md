# Provider Ranking - Agregação por Provider

## 📋 Resumo da Mudança

O endpoint `/api/admin/reports/global/provider-ranking` agora suporta um novo parâmetro `aggregate_by_provider` que permite agregar os dados de todas as tecnologias de um mesmo provider para um domínio, evitando que o mesmo domínio apareça múltiplas vezes no ranking quando o provider oferece mais de uma tecnologia.

## 🎯 Problema Resolvido

**Antes:** Se um provider (ex: Spectrum) oferece Fiber e Cable para o mesmo domínio, o domínio aparecia duas vezes no ranking:
- `domain.com` com Spectrum (Fiber) - 800 requests
- `domain.com` com Spectrum (Cable) - 700 requests

**Agora (com `aggregate_by_provider=true`):** O domínio aparece apenas uma vez com os dados agregados:
- `domain.com` com Spectrum (Fiber, Cable) - 1500 requests

## 📡 Endpoint

```
GET /api/admin/reports/global/provider-ranking
```

## 🔧 Novo Parâmetro

### `aggregate_by_provider` (opcional)

- **Tipo:** `boolean`
- **Valores aceitos:** `true`, `false`, `1`, `0`, ou omitido
- **Padrão:** `false` (comportamento original)
- **Descrição:** Quando `true`, agrega os dados de todas as tecnologias do mesmo provider para cada domínio

## 📊 Comportamento

### Sem `aggregate_by_provider` (padrão)

- Agrupa por: `domain + provider + technology`
- Cada combinação de tecnologia aparece como uma entrada separada
- Campo `technology` retorna uma única tecnologia (ex: `"Fiber"`)

### Com `aggregate_by_provider=true`

- Agrupa por: `domain + provider` (ignora tecnologia)
- Cada provider aparece apenas uma vez por domínio
- Campo `technology` retorna todas as tecnologias concatenadas (ex: `"Fiber, Cable"`)
- `total_requests` = soma de todas as tecnologias
- `avg_success_rate` = média ponderada de todas as tecnologias
- `avg_speed` = média ponderada de todas as tecnologias

## 📝 Exemplos de Requisições

### Exemplo 1: Ranking normal (por tecnologia)

```http
GET /api/admin/reports/global/provider-ranking?period=last_month&page=1&per_page=15
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "rank": 1,
      "domain_id": 1,
      "domain_name": "example.com",
      "domain_slug": "example-com",
      "provider_id": 5,
      "provider_name": "Spectrum",
      "technology": "Fiber",
      "total_requests": 800,
      "avg_success_rate": 96.5,
      "avg_speed": 450.2,
      "total_reports": 30,
      "period_start": "2025-10-01",
      "period_end": "2025-10-31",
      "days_covered": 31,
      "domain_total_requests": 5000,
      "percentage_of_domain": 16.0
    },
    {
      "rank": 2,
      "domain_id": 1,
      "domain_name": "example.com",
      "domain_slug": "example-com",
      "provider_id": 5,
      "provider_name": "Spectrum",
      "technology": "Cable",
      "total_requests": 700,
      "avg_success_rate": 94.2,
      "avg_speed": 380.5,
      "total_reports": 30,
      "period_start": "2025-10-01",
      "period_end": "2025-10-31",
      "days_covered": 31,
      "domain_total_requests": 5000,
      "percentage_of_domain": 14.0
    }
  ],
  "filters": {
    "aggregate_by_provider": false
  }
}
```

### Exemplo 2: Ranking agregado (ignorando tecnologia)

```http
GET /api/admin/reports/global/provider-ranking?aggregate_by_provider=true&period=last_month&page=1&per_page=15
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "rank": 1,
      "domain_id": 1,
      "domain_name": "example.com",
      "domain_slug": "example-com",
      "provider_id": 5,
      "provider_name": "Spectrum",
      "technology": "Fiber, Cable",
      "total_requests": 1500,
      "avg_success_rate": 95.4,
      "avg_speed": 415.3,
      "total_reports": 30,
      "period_start": "2025-10-01",
      "period_end": "2025-10-31",
      "days_covered": 31,
      "domain_total_requests": 5000,
      "percentage_of_domain": 30.0
    }
  ],
  "filters": {
    "aggregate_by_provider": true
  }
}
```

### Exemplo 3: Combinando com outros filtros

```http
GET /api/admin/reports/global/provider-ranking?aggregate_by_provider=true&provider_id=5&sort_by=total_requests&page=1
```

## 🔄 Compatibilidade

- **Retrocompatível:** O parâmetro é opcional. Se não for enviado, o comportamento é o mesmo de antes
- **Filtros existentes:** Todos os outros parâmetros continuam funcionando normalmente:
  - `provider_id`
  - `technology` (ainda funciona, mas quando `aggregate_by_provider=true`, o filtro é aplicado antes da agregação)
  - `period`
  - `date_from` / `date_to`
  - `sort_by`
  - `page` / `per_page`

## 💡 Casos de Uso

### Quando usar `aggregate_by_provider=true`:

1. **Ranking geral de providers por domínio** - Ver qual provider tem mais requests totais, independente da tecnologia
2. **Comparação simplificada** - Evitar duplicação de entradas quando um provider oferece múltiplas tecnologias
3. **Dashboards consolidados** - Mostrar uma visão agregada sem detalhamento por tecnologia

### Quando usar o comportamento padrão:

1. **Análise por tecnologia** - Quando precisa ver o desempenho específico de cada tecnologia
2. **Comparação técnica** - Comparar Fiber vs Cable do mesmo provider
3. **Relatórios detalhados** - Quando o detalhamento por tecnologia é necessário

## 📋 Estrutura da Resposta

A estrutura da resposta permanece a mesma, apenas o campo `technology` e os valores agregados mudam:

```typescript
interface ProviderRankingItem {
  rank: number;
  domain_id: number;
  domain_name: string;
  domain_slug: string;
  provider_id: number;
  provider_name: string;
  technology: string | null; // String única ou múltiplas tecnologias separadas por vírgula
  total_requests: number;
  avg_success_rate: number;
  avg_speed: number;
  total_reports: number;
  period_start: string; // YYYY-MM-DD
  period_end: string; // YYYY-MM-DD
  days_covered: number;
  domain_total_requests: number;
  percentage_of_domain: number;
}
```

## ⚠️ Observações Importantes

1. **Campo `technology`:** 
   - Quando `aggregate_by_provider=false`: retorna uma única tecnologia (ex: `"Fiber"`)
   - Quando `aggregate_by_provider=true`: retorna todas as tecnologias separadas por vírgula (ex: `"Fiber, Cable"`)
   - Pode ser `null` se não houver tecnologia associada

2. **Cálculos agregados:**
   - `total_requests` = soma de todas as tecnologias
   - `avg_success_rate` = média ponderada (não simples média aritmética)
   - `avg_speed` = média ponderada (não simples média aritmética)

3. **Filtro `technology`:**
   - Quando usado junto com `aggregate_by_provider=true`, o filtro é aplicado antes da agregação
   - Exemplo: `technology=Fiber&aggregate_by_provider=true` retorna apenas dados de Fiber, mas agregados por provider

4. **Paginação:**
   - Funciona normalmente com ambos os modos
   - O total de resultados pode ser menor quando `aggregate_by_provider=true` (menos entradas duplicadas)

## 🧪 Exemplo de Implementação Frontend

```typescript
// Função para buscar ranking
async function getProviderRanking(options: {
  aggregateByProvider?: boolean;
  providerId?: number;
  period?: string;
  page?: number;
  perPage?: number;
}) {
  const params = new URLSearchParams();
  
  if (options.aggregateByProvider) {
    params.append('aggregate_by_provider', 'true');
  }
  
  if (options.providerId) {
    params.append('provider_id', options.providerId.toString());
  }
  
  if (options.period) {
    params.append('period', options.period);
  }
  
  if (options.page) {
    params.append('page', options.page.toString());
  }
  
  if (options.perPage) {
    params.append('per_page', options.perPage.toString());
  }
  
  const response = await fetch(
    `/api/admin/reports/global/provider-ranking?${params.toString()}`
  );
  
  return response.json();
}

// Uso
const ranking = await getProviderRanking({
  aggregateByProvider: true,
  period: 'last_month',
  page: 1,
  perPage: 15
});
```

## 📌 Checklist para Implementação

- [ ] Adicionar toggle/switch para `aggregate_by_provider` na interface
- [ ] Atualizar chamadas à API para incluir o parâmetro quando necessário
- [ ] Ajustar exibição do campo `technology` para suportar múltiplas tecnologias (ex: "Fiber, Cable")
- [ ] Considerar tooltip ou badge para mostrar todas as tecnologias quando agregado
- [ ] Testar com diferentes combinações de filtros
- [ ] Verificar se a paginação funciona corretamente em ambos os modos

