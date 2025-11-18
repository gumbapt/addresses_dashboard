# 🔧 Correção: Gráfico de Velocidade por Estado

## 🔍 **Problema Identificado**

O gráfico de velocidade por estado não aparecia para o **Domain 15**, enquanto funcionava corretamente para o **Domain 1**.

### **Causa Raiz:**

1. **Domain 1**: Tem `avg_speed > 0` em `report_states` (365 estados com velocidade)
2. **Domain 15**: Tem `avg_speed = 0` em `report_states` (367 estados, todos com velocidade zero)

### **Análise:**

- O `ReportProcessor` está salvando `avg_speed` corretamente de `geographic.states[].avg_speed`
- Mas os reports do **Domain 15** não têm `avg_speed` em `geographic.states`
- Os reports do **Domain 15** também não têm `speed_metrics.by_state`

---

## ✅ **Solução Implementada**

### **1. Fallback para Buscar Velocidade de Múltiplas Fontes**

Modificado `GetAggregatedReportStatsUseCase.aggregateStates()` para buscar dados de velocidade de múltiplas fontes quando `report_states.avg_speed` for 0:

```php
// Se avg_speed for 0 para todos os estados, tentar buscar de:
1. speed_metrics.by_state do raw_data
2. geographic.states[].avg_speed do raw_data (caso não tenha sido processado)
```

### **2. Código Adicionado:**

```php
// Se avg_speed for 0 para todos os estados, tentar buscar de speed_metrics.by_state do raw_data
$hasSpeedData = array_sum(array_column($result, 'avg_speed')) > 0;

if (!$hasSpeedData) {
    $speedDataByState = [];
    $reports = Report::whereIn('id', $reportIds)->get();
    
    foreach ($reports as $report) {
        $rawData = $report->raw_data;
        
        // Tentar buscar de speed_metrics.by_state
        if (isset($rawData['speed_metrics']['by_state']) && is_array($rawData['speed_metrics']['by_state'])) {
            foreach ($rawData['speed_metrics']['by_state'] as $stateCode => $speedData) {
                if (isset($speedData['avg']) && $speedData['avg'] > 0) {
                    $speedDataByState[$stateCode][] = $speedData['avg'];
                }
            }
        }
        
        // Tentar buscar de geographic.states[].avg_speed
        if (isset($rawData['geographic']['states']) && is_array($rawData['geographic']['states'])) {
            foreach ($rawData['geographic']['states'] as $stateData) {
                $stateCode = $stateData['code'] ?? null;
                $avgSpeed = $stateData['avg_speed'] ?? 0;
                if ($stateCode && $avgSpeed > 0) {
                    $speedDataByState[$stateCode][] = $avgSpeed;
                }
            }
        }
    }
    
    // Atualizar avg_speed nos resultados se encontramos dados
    foreach ($result as &$state) {
        $stateCode = $state['code'];
        if (isset($speedDataByState[$stateCode]) && !empty($speedDataByState[$stateCode])) {
            $state['avg_speed'] = round(array_sum($speedDataByState[$stateCode]) / count($speedDataByState[$stateCode]), 2);
        }
    }
}
```

---

## ⚠️ **Limitação Atual**

**Domain 15** ainda não tem dados de velocidade porque:

1. ❌ Os reports não têm `geographic.states[].avg_speed`
2. ❌ Os reports não têm `speed_metrics.by_state`
3. ❌ O WordPress não está enviando dados de velocidade

---

## 🎯 **Próximos Passos**

### **Para o WordPress (Recomendado):**

O WordPress precisa enviar dados de velocidade em um dos seguintes formatos:

**Opção 1: Em `geographic.states`**
```json
{
  "geographic": {
    "states": [
      {
        "code": "CA",
        "name": "California",
        "request_count": 32,
        "avg_speed": 1500.0  // ✅ Adicionar este campo
      }
    ]
  }
}
```

**Opção 2: Em `speed_metrics.by_state`**
```json
{
  "speed_metrics": {
    "by_state": {
      "CA": {
        "avg": 1500.0,
        "max": 5000.0,
        "min": 50.0
      }
    }
  }
}
```

---

## 📊 **Status Atual**

| Item | Domain 1 | Domain 15 |
|------|----------|-----------|
| **report_states.avg_speed > 0** | ✅ 365 estados | ❌ 0 estados |
| **speed_metrics.by_state** | ❌ Não verificado | ❌ Não existe |
| **geographic.states[].avg_speed** | ✅ Existe | ❌ Não existe |
| **Fallback Implementado** | ✅ Sim | ✅ Sim |
| **Gráfico Funciona** | ✅ Sim | ⚠️ Aguardando dados |

---

## ✅ **Melhorias Implementadas**

1. ✅ Fallback para buscar velocidade de múltiplas fontes
2. ✅ Suporte a `speed_metrics.by_state`
3. ✅ Suporte a `geographic.states[].avg_speed` não processado
4. ✅ Cálculo de média quando há múltiplos valores

---

## 🧪 **Como Testar**

### **Teste 1: Verificar se Fallback Funciona**

```bash
# Enviar um report com speed_metrics.by_state
# Verificar se o gráfico aparece
```

### **Teste 2: Verificar Reports Individuais**

```bash
# Selecionar um report individual do domain 15
# Verificar se tem dados de velocidade no raw_data
```

---

**Status:** ✅ Código corrigido e pronto  
**Ação WordPress:** ⚠️ Necessária - Enviar dados de velocidade  
**Data:** November 15, 2025

