# Provider Ranking - Agregação por Provider (Resumo)

## 🎯 Nova Funcionalidade

O endpoint `/api/admin/reports/global/provider-ranking` agora aceita o parâmetro `aggregate_by_provider=true` para agregar dados de todas as tecnologias do mesmo provider por domínio.

## 📡 Parâmetro

```
GET /api/admin/reports/global/provider-ranking?aggregate_by_provider=true
```

- **Tipo:** `boolean` (opcional)
- **Padrão:** `false` (comportamento original)
- **Quando `true`:** Agrega dados de todas as tecnologias, evitando duplicação de entradas

## 🔄 Mudanças no Retorno

### Campo `technology`:
- **Sem agregação:** `"Fiber"` (tecnologia única)
- **Com agregação:** `"Fiber, Cable"` (todas as tecnologias separadas por vírgula)

### Valores agregados:
- `total_requests` = soma de todas as tecnologias
- `avg_success_rate` = média ponderada
- `avg_speed` = média ponderada

## 📝 Exemplo

**Antes (sem agregação):**
```json
[
  {"domain_name": "example.com", "provider_name": "Spectrum", "technology": "Fiber", "total_requests": 800},
  {"domain_name": "example.com", "provider_name": "Spectrum", "technology": "Cable", "total_requests": 700}
]
```

**Agora (com `aggregate_by_provider=true`):**
```json
[
  {"domain_name": "example.com", "provider_name": "Spectrum", "technology": "Fiber, Cable", "total_requests": 1500}
]
```

## ✅ Compatibilidade

- **Retrocompatível:** Se o parâmetro não for enviado, comportamento original é mantido
- **Outros filtros:** Continuam funcionando normalmente (`provider_id`, `period`, `sort_by`, etc.)

## 💡 Quando Usar

- **`aggregate_by_provider=true`:** Ranking geral, evitar duplicação, dashboards consolidados
- **Padrão:** Análise por tecnologia, comparação técnica, relatórios detalhados

