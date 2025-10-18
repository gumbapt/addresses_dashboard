# 📊 Perfis dos Domínios - Dados Sintéticos Divergentes

## 🎯 Objetivo

Este documento detalha os perfis aplicados a cada domínio fictício para criar dados sintéticos **significativamente divergentes** do domínio real, permitindo análise comparativa robusta e visualizações interessantes.

---

## 🌐 Domínios e Perfis

### **1. zip.50g.io** 📊 **DADOS REAIS (BASE)**

**Características:**
- Dados originais dos arquivos `docs/daily_reports/*.json`
- Volume: ~1,490 requisições totais
- Taxa de sucesso: 92.4%
- Distribuição geográfica natural
- Todos os provedores e tecnologias originais

**Uso:**
- Base de comparação
- Dados reais de produção
- Referência para análises

---

### **2. smarterhome.ai** 🚀 **ALTO VOLUME, ALTA QUALIDADE**

**Perfil Aplicado:**
```php
'volume_multiplier' => 2.5      // 250% mais requisições
'success_bias' => 0.05          // +5% na taxa de sucesso
'state_focus' => ['CA', 'NY', 'TX']  // Foco nesses estados
'tech_preference' => 'Fiber'    // Preferência por Fiber
'provider_shuffle' => 0.4       // 40% de shuffle de provedores
```

**Resultados:**
- Volume: ~3,729 requisições (+150% vs zip.50g.io)
- Taxa de sucesso: 96% (+3.6% vs zip.50g.io)
- Foco geográfico: California, New York, Texas (2-3x mais requisições)
- Outros estados: 30-70% do volume original

**Interpretação:**
- Site de alto tráfego com boa infraestrutura
- Público concentrado em grandes mercados
- Performance superior
- Ideal para representar um líder de mercado

---

### **3. ispfinder.net** 📉 **BAIXO VOLUME, BAIXA QUALIDADE**

**Perfil Aplicado:**
```php
'volume_multiplier' => 0.6      // 60% das requisições
'success_bias' => -0.08         // -8% na taxa de sucesso
'state_focus' => ['FL', 'GA', 'NC']  // Foco nesses estados
'tech_preference' => 'Mobile'   // Preferência por Mobile
'provider_shuffle' => 0.6       // 60% de shuffle de provedores
```

**Resultados:**
- Volume: ~928 requisições (-38% vs zip.50g.io)
- Taxa de sucesso: 84.4% (-8% vs zip.50g.io)
- Foco geográfico: Florida, Georgia, North Carolina
- Provedores: Alta variação (60% de shuffle)

**Interpretação:**
- Site menor ou nicho específico
- Performance mais baixa (problemas técnicos ou API)
- Distribuição geográfica diferente
- Ideal para representar um player menor ou em crescimento

---

### **4. broadbandcheck.io** 📈 **MÉDIO-ALTO VOLUME, BOA QUALIDADE**

**Perfil Aplicado:**
```php
'volume_multiplier' => 1.8      // 180% das requisições
'success_bias' => 0.03          // +3% na taxa de sucesso
'state_focus' => ['IL', 'OH', 'PA']  // Foco nesses estados
'tech_preference' => 'Cable'    // Preferência por Cable
'provider_shuffle' => 0.5       // 50% de shuffle de provedores
```

**Resultados:**
- Volume: ~2,737 requisições (+84% vs zip.50g.io)
- Taxa de sucesso: 94.6% (+2.2% vs zip.50g.io)
- Foco geográfico: Illinois, Ohio, Pennsylvania
- Distribuição equilibrada de provedores

**Interpretação:**
- Site de médio-grande porte
- Boa performance e infraestrutura
- Mercado regional (Midwest)
- Ideal para representar um competidor sólido

---

## 📊 Comparação Detalhada

### **Tabela Comparativa**

| Métrica | zip.50g.io | smarterhome.ai | ispfinder.net | broadbandcheck.io |
|---------|-----------|----------------|---------------|-------------------|
| **Volume Total** | 1,490 | 3,729 (+150%) | 928 (-38%) | 2,737 (+84%) |
| **Taxa de Sucesso** | 92.4% | 96% (+3.6%) | 84.4% (-8%) | 94.6% (+2.2%) |
| **Provedores Únicos** | 122 | 121 | 118 | 122 |
| **Estados Cobertos** | 43 | 43 | 28 | 43 |
| **Foco Geográfico** | Nacional | CA/NY/TX | FL/GA/NC | IL/OH/PA |
| **Tech Preferida** | Misto | Fiber | Mobile | Cable |

### **Ranking por Volume**

1. **smarterhome.ai** - 3,729 requisições (Alto tráfego)
2. **broadbandcheck.io** - 2,737 requisições (Médio-alto tráfego)
3. **zip.50g.io** - 1,490 requisições (Médio tráfego)
4. **ispfinder.net** - 928 requisições (Baixo tráfego)

### **Ranking por Qualidade (Success Rate)**

1. **smarterhome.ai** - 96% (Excelente)
2. **broadbandcheck.io** - 94.6% (Muito bom)
3. **zip.50g.io** - 92.4% (Bom)
4. **ispfinder.net** - 84.4% (Regular)

---

## 🎨 Impacto nas Visualizações

### **Gráficos de Volume**
- **Variação significativa** entre domínios (-38% a +150%)
- **Barras claramente diferentes** em comparações
- **Trends distintos** ao longo do tempo

### **Mapas Geográficos**
- **Hotspots diferentes** por domínio
- smarterhome.ai: Concentrado em CA/NY/TX
- ispfinder.net: Focado em FL/GA/NC
- broadbandcheck.io: Forte em IL/OH/PA

### **Distribuição de Provedores**
- **Ordenações diferentes** (devido ao shuffle)
- **Variações de 20% a 300%** por provedor
- **Rankings distintos** por domínio

### **Taxa de Sucesso**
- **Range de 84.4% a 96%** (11.6% de diferença)
- **Visualização clara** de performance relativa
- **Insights sobre qualidade** do serviço

---

## 🔧 Algoritmo de Síntese

### **Variações Aplicadas**

1. **Volume Base:**
   - Multiplicador fixo por domínio (0.6x a 2.5x)
   - Variação aleatória adicional de ±20%

2. **Taxa de Sucesso:**
   - Bias fixo por domínio (-8% a +5%)
   - Recálculo de successful_requests e failed_requests

3. **Distribuição Geográfica:**
   - Estados focados: 2-3x mais requisições
   - Outros estados: 0.3-0.7x requisições

4. **Provedores:**
   - Shuffle aleatório: 20% a 300% por provedor
   - Probabilidade de shuffle por domínio (40% a 60%)

5. **Velocidades:**
   - Variação de 60% a 160% do original
   - Mantém proporções relativas (min/avg/max)

6. **Outros Métricas:**
   - Cidades: 50% a 200% variação
   - CEPs: 50% a 200% variação
   - Contadores únicos: 70% a 130% variação

---

## 🎯 Casos de Uso

### **1. Ranking de Domínios**
```
Implementar: GET /api/admin/reports/global/domain-ranking

Resultado esperado:
1. smarterhome.ai - 3,729 requests (96% success)
2. broadbandcheck.io - 2,737 requests (94.6% success)
3. zip.50g.io - 1,490 requests (92.4% success)
4. ispfinder.net - 928 requests (84.4% success)
```

### **2. Análise Comparativa**
```
Implementar: GET /api/admin/reports/global/comparison

Permite comparar:
- Volume por período
- Taxa de sucesso por domínio
- Distribuição geográfica
- Preferências de tecnologia
```

### **3. Insights Automáticos**
```
Possíveis insights:
- "smarterhome.ai tem 250% mais tráfego que zip.50g.io"
- "ispfinder.net tem a menor taxa de sucesso (84.4%)"
- "broadbandcheck.io é forte no Midwest"
- "smarterhome.ai prefere Fiber, enquanto ispfinder.net usa Mobile"
```

---

## 🚀 Próximos Passos

### **Implementar Endpoints Cross-Domain:**

1. **Ranking Global**
   - `/api/admin/reports/global/domain-ranking`
   - Ordenar por volume, success rate, ou score combinado

2. **Comparação Detalhada**
   - `/api/admin/reports/global/comparison?domains=1,2,3`
   - Comparar métricas específicas

3. **Análise de Tecnologias**
   - `/api/admin/reports/global/technology-analysis`
   - Distribuição por domínio

4. **Dashboard Global**
   - Visualização unificada de todos os domínios
   - Rankings, comparações, trends

---

## 📊 Estatísticas Globais Atuais

### **Totais:**
- **Domínios:** 4
- **Relatórios:** 160 (40 por domínio)
- **Requisições Totais:** 8,884
- **Provedores Únicos:** 122
- **Estados Cobertos:** 43

### **Top 5 Provedores (Global):**
1. Viasat Carrier Services Inc - 8,081 requisições
2. Verizon - 8,070 requisições
3. HughesNet - 7,812 requisições
4. Earthlink - 7,688 requisições
5. T-Mobile - 7,605 requisições

### **Top 5 Estados (Global):**
1. California (CA) - 2,029 requisições
2. Texas (TX) - 1,539 requisições
3. New York (NY) - 1,342 requisições
4. Florida (FL) - 169 requisições
5. Ohio (OH) - 154 requisições

---

## 🎉 Conclusão

Os perfis aplicados garantem que:

✅ **Dados Significativamente Divergentes** - Variação real nos gráficos
✅ **Realismo** - Cada domínio tem características coerentes
✅ **Comparabilidade** - Métricas consistentes para análise
✅ **Insights Acionáveis** - Diferenças claras e interpretáveis
✅ **Visualizações Interessantes** - Gráficos variados e informativos

**O sistema está pronto para implementação completa de análise cross-domain!** 🚀
