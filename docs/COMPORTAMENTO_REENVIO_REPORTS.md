# 🔄 Comportamento ao Reenviar Reports

## 📋 O que acontece quando você reenvia um report do mesmo dia?

### Cenário: Reenviar report do mesmo WordPress, mesmo domínio, mesma data

## ✅ Comportamento Atual

### 1. **Detecção de Duplicata**
O sistema verifica se já existe um report para:
- Mesmo `domain_id`
- Mesma `report_date`

### 2. **Comparação de Conteúdo**
Se existe um report, o sistema compara o conteúdo usando `hasReportDataChanged()`:
- Compara: `summary`, `providers`, `geographic`, `performance`, `speed_metrics`, etc.
- Ignora: timestamps, metadados que podem variar
- Usa hash MD5 para comparação eficiente

### 3. **Cenário A: Sem Mudanças**
Se o conteúdo for **idêntico**:
- ✅ Retorna o report existente
- ✅ **NÃO reprocessa** (economiza recursos)
- ✅ Mantém status atual (se já estava `processed`, continua `processed`)
- ✅ Mantém todos os dados processados (summary, providers, states, etc.)

**Exemplo:**
```
Report 837 (2025-11-23) já existe
Reenvio com mesmo conteúdo
→ Retorna report 837 sem reprocessar
```

### 4. **Cenário B: Com Mudanças**
Se o conteúdo for **diferente**:
- ✅ Atualiza o `raw_data` do report existente
- ✅ Reseta status para `pending`
- ✅ **Limpa todos os dados processados:**
  - ReportSummary
  - ReportProvider
  - ReportState
  - ReportStateProvider ← ✅ Agora incluído!
  - ReportCity
  - ReportZipCode
- ✅ Dispara reprocessamento (via job)
- ✅ Processa novamente com os novos dados

**Exemplo:**
```
Report 837 (2025-11-23) já existe
Reenvio com campo "providers" adicionado em geographic.states
→ Detecta mudança
→ Atualiza raw_data
→ Limpa dados antigos (incluindo report_state_providers)
→ Reprocessa com novos dados
→ Salva novos dados em report_state_providers
```

## 🔍 Campos Comparados

O sistema compara os seguintes campos para detectar mudanças:

### Daily Reports:
- `data.summary`
- `data.providers`
- `data.geographic` (incluindo `states[].providers`)
- `speed_metrics`
- `exclusion_metrics`
- `technology_metrics`

### Reports Padrão:
- `summary`
- `providers`
- `geographic`
- `performance`
- `speed_metrics`
- `exclusion_metrics`
- `technology_metrics`

## ⚠️ Campos Ignorados

Os seguintes campos são **ignorados** na comparação (podem variar sem indicar mudança real):
- `metadata.generated_at`
- `metadata.timestamp`
- `metadata.total_processing_time`
- `source.*` (informações do WordPress)

## 📊 Exemplo Prático

### Caso 1: Reenvio Idêntico
```json
// Report original
{
  "geographic": {
    "states": [{"code": "NC", "request_count": 1}]
  }
}

// Reenvio (mesmo conteúdo)
{
  "geographic": {
    "states": [{"code": "NC", "request_count": 1}]
  }
}

Resultado: ✅ Não reprocessa, retorna report existente
```

### Caso 2: Reenvio com Campo Novo
```json
// Report original
{
  "geographic": {
    "states": [{"code": "NC", "request_count": 1}]
  }
}

// Reenvio (com providers adicionado)
{
  "geographic": {
    "states": [{
      "code": "NC",
      "request_count": 1,
      "providers": [{"name": "AT&T", "count": 1}]
    }]
  }
}

Resultado: ✅ Detecta mudança
         ✅ Atualiza raw_data
         ✅ Limpa dados antigos
         ✅ Reprocessa
         ✅ Salva novos dados em report_state_providers
```

## 🎯 Vantagens

1. **Economia de Recursos:** Não reprocessa reports idênticos
2. **Atualização Automática:** Se houver mudanças, atualiza automaticamente
3. **Limpeza Completa:** Remove dados antigos antes de reprocessar
4. **Idempotência:** Pode reenviar quantas vezes quiser sem problemas

## ⚠️ Importante

- **Mesmo ID:** O report mantém o mesmo ID (não cria duplicata)
- **Histórico:** O `raw_data` é atualizado, mas o ID do report permanece
- **Dados Processados:** São completamente limpos e recriados se houver mudança
- **Status:** Resetado para `pending` quando há mudança, para garantir reprocessamento

## 🔧 Correção Aplicada

✅ Adicionada limpeza de `ReportStateProvider` quando report é atualizado
✅ Garante que dados antigos não ficam órfãos
✅ Evita duplicação de dados em `report_state_providers`

