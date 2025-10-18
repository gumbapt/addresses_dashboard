# 📊 Explicação: Dados Faltantes nos Relatórios

## 🔍 Problema Identificado

Alguns relatórios não possuem dados de **velocidade por estado** (`speed_metrics.by_state`) ou **distribuição de tecnologias completa**.

### **Estatísticas:**
- **Total de relatórios**: 160
- **Com `speed_metrics`**: 160/160 (100%)
- **Com `by_state`**: 114/160 (71.3%)
- **Com `by_provider`**: 120/160 (75%)

---

## 🎯 Causas Identificadas

### **1. Domínio Real (zip.50g.io) - 0% com `by_state`**

**Motivo:** Os dados reais do WordPress **não incluem** velocidades detalhadas por estado ou provedor.

**Estrutura dos arquivos originais (`docs/daily_reports/*.json`):**
```json
{
  "data": {
    "summary": {
      "avg_speed_mbps": 1502.89,
      "max_speed_mbps": 219000,
      "min_speed_mbps": 10
    },
    "geographic": {
      "states": {
        "CA": 32,
        "NY": 14
      }
    },
    "providers": {
      "available": {
        "Verizon": 103,
        "HughesNet": 103
      }
    }
  }
}
```

**O que falta:**
- ❌ Velocidade média **por estado** (CA: X Mbps, NY: Y Mbps)
- ❌ Velocidade média **por provedor** (Verizon: X Mbps, HughesNet: Y Mbps)

**Solução atual:**
- O `CreateDailyReportUseCase` cria `by_state` e `by_provider` **vazios** para dados reais
- Apenas a velocidade geral (`overall.avg`) é preenchida

### **2. Domínios Sintéticos - ~25-30% sem `by_state`**

**Motivo:** Alguns arquivos JSON originais **não têm dados geográficos** ou têm dados muito limitados.

**Exemplo de arquivo sem estados:**
```json
{
  "data": {
    "summary": {
      "total_requests": 50
    },
    "geographic": {
      "states": {},  // <- VAZIO!
      "cities": {},
      "zipcodes": {}
    }
  }
}
```

**Quando acontece:**
- Dias com **baixo volume** de requisições
- Erros no plugin WordPress durante coleta
- Dados incompletos ou corrompidos

**Solução atual:**
- O algoritmo de síntese (`SeedAllDomainsWithReports`) verifica se existem estados:
  ```php
  if (isset($data['data']['geographic']['states']) && !empty($profile['state_focus'])) {
      // Gera by_state
  } else {
      // by_state fica vazio
  }
  ```

---

## 📊 Breakdown por Domínio

### **zip.50g.io** (Dados Reais)
- **Total**: 40 relatórios
- **Com `by_state`**: 0/40 (0%)
- **Com `by_provider`**: 40/40 (100%)
- **Motivo**: WordPress não envia velocidades detalhadas

### **smarterhome.ai** (Sintético)
- **Total**: 40 relatórios
- **Com `by_state`**: 39/40 (97.5%)
- **Com `by_provider`**: 40/40 (100%)
- **Motivo**: 1 arquivo original sem dados de estados

### **ispfinder.net** (Sintético)
- **Total**: 40 relatórios
- **Com `by_state`**: 36/40 (90%)
- **Com `by_provider`**: 40/40 (100%)
- **Motivo**: 4 arquivos originais com dados geográficos limitados

### **broadbandcheck.io** (Sintético)
- **Total**: 40 relatórios
- **Com `by_state`**: 39/40 (97.5%)
- **Com `by_provider`**: 40/40 (100%)
- **Motivo**: 1 arquivo original sem dados de estados

---

## 🔧 Soluções Possíveis

### **Opção 1: Manter Como Está** ✅ (Recomendado)

**Vantagens:**
- ✅ Reflete a realidade dos dados
- ✅ Domínio real sem dados fictícios
- ✅ ~71-75% dos relatórios têm dados completos
- ✅ Suficiente para análises robustas

**Desvantagens:**
- ⚠️ Alguns gráficos podem ter dados vazios
- ⚠️ Necessário tratar casos de dados faltantes no frontend

**Uso:**
```php
// No dashboard, verificar se existem dados
if (!empty($report->raw_data['speed_metrics']['by_state'])) {
    // Mostrar mapa de calor
} else {
    // Mostrar mensagem "Dados não disponíveis"
}
```

### **Opção 2: Gerar Dados Sintéticos para TODOS** 🎲

**Vantagens:**
- ✅ 100% dos relatórios com dados completos
- ✅ Gráficos sempre preenchidos

**Desvantagens:**
- ❌ Domínio real com dados fictícios (perde autenticidade)
- ❌ Mais complexo de implementar
- ❌ Pode mascarar problemas reais

**Implementação:**
```php
// Sempre gerar by_state, mesmo sem dados originais
if (empty($data['data']['geographic']['states'])) {
    // Gerar estados aleatórios baseados no total_requests
    $data['data']['geographic']['states'] = $this->generateRandomStates($totalRequests);
}
```

### **Opção 3: Enriquecer Dados Reais** 📊

**Vantagens:**
- ✅ Mantém autenticidade do domínio real
- ✅ Adiciona dados úteis onde possível

**Desvantagens:**
- ⚠️ Ainda terá alguns dados vazios

**Implementação:**
```php
// Apenas para domínios sintéticos, gerar dados quando faltarem
if (!$isRealDomain && empty($data['data']['geographic']['states'])) {
    $data['data']['geographic']['states'] = $this->generateSyntheticStates();
}
```

---

## 🎯 Recomendação

**Manter a Opção 1** (estado atual) pelos seguintes motivos:

1. **Autenticidade**: Domínio real mantém dados originais
2. **Suficiência**: 71-75% de cobertura é adequado para análises
3. **Simplicidade**: Frontend pode tratar dados faltantes facilmente
4. **Realismo**: Reflete cenários reais de APIs incompletas

---

## 💡 Como Tratar no Frontend

### **Verificar Dados Antes de Usar**

```javascript
// Dashboard
if (report.speed_metrics?.by_state && Object.keys(report.speed_metrics.by_state).length > 0) {
  // Renderizar mapa de calor
  renderHeatMap(report.speed_metrics.by_state);
} else {
  // Mostrar placeholder
  showPlaceholder("Dados de velocidade por estado não disponíveis para este período");
}
```

### **Agregação com Fallback**

```php
// Backend - Agregação
$speedByState = [];

foreach ($reports as $report) {
    if (isset($report->raw_data['speed_metrics']['by_state'])) {
        foreach ($report->raw_data['speed_metrics']['by_state'] as $state => $data) {
            // Agregar apenas dados disponíveis
            if (!isset($speedByState[$state])) {
                $speedByState[$state] = [];
            }
            $speedByState[$state][] = $data['avg_speed'];
        }
    }
}

// Calcular médias
foreach ($speedByState as $state => &$speeds) {
    $speeds = round(array_sum($speeds) / count($speeds), 2);
}
```

### **Mostrar Cobertura de Dados**

```javascript
// Informar ao usuário a cobertura
const coverage = {
  total_reports: 40,
  with_speed_by_state: 39,
  coverage_percentage: 97.5
};

showDataCoverage(`Velocidades por estado disponíveis em ${coverage.coverage_percentage}% dos relatórios`);
```

---

## 📊 Status Atual (Após População)

### **Cobertura de Dados:**

| Domínio | Total Reports | Com by_state | Com by_provider | Cobertura |
|---------|---------------|--------------|-----------------|-----------|
| zip.50g.io | 40 | 0 (0%) | 40 (100%) | Dados reais limitados |
| smarterhome.ai | 40 | 39 (97.5%) | 40 (100%) | Excelente |
| ispfinder.net | 40 | 36 (90%) | 40 (100%) | Muito bom |
| broadbandcheck.io | 40 | 39 (97.5%) | 40 (100%) | Excelente |
| **GLOBAL** | **160** | **114 (71.3%)** | **120 (75%)** | **Bom** |

### **Conclusão:**

✅ **71-75% de cobertura é SUFICIENTE** para análises robustas
✅ **Domínios sintéticos têm 90-97.5% de cobertura** (excelente)
✅ **Domínio real mantém autenticidade** (sem dados fictícios)
✅ **Sistema está pronto** para implementar dashboards com tratamento de dados faltantes

---

## 🚀 Próximos Passos

1. **Implementar endpoints** que agregam apenas dados disponíveis
2. **Frontend robusto** que trata casos de dados faltantes
3. **Indicadores de cobertura** para informar usuários
4. **Fallbacks visuais** para gráficos sem dados

---

📚 **Referências:**
- [SPEED_METRICS_GUIDE.md](./SPEED_METRICS_GUIDE.md) - Guia de velocidades
- [DOMAIN_PROFILES.md](./DOMAIN_PROFILES.md) - Perfis dos domínios
- [MULTI_DOMAIN_SETUP_GUIDE.md](./MULTI_DOMAIN_SETUP_GUIDE.md) - Setup completo
