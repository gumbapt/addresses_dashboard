# 🔍 Análise: Diferença entre Seeder e API

## 📊 **O QUE MUDOU?**

### **Seeder** (funciona):
- **Lê**: `docs/daily_reports/*.json`
- **Formato**: WordPress antigo
  ```json
  {
    "source": {...},
    "data": {
      "technologies": {...}  ← AQUI!
    }
  }
  ```
- **UseCase**: `CreateDailyReportUseCase`
- **Conversão**: ✅ Converte `data.technologies` → `technology_metrics.distribution`

### **API** (não funciona):
- **Recebe**: POST `/api/reports/submit`
- **Formato**: Novo formato
  ```json
  {
    "source": {...},
    "metadata": {...},
    "summary": {...},
    "technology_metrics": {...}  ← DEVERIA ESTAR AQUI!
  }
  ```
- **UseCase**: `CreateReportUseCase`
- **Normalização**: ✅ Normaliza formatos antigos

---

## ❌ **PROBLEMA IDENTIFICADO**

### O WordPress está enviando:
```json
{
  "metadata": {...},
  "summary": {...},
  "providers": {...},
  // ❌ NÃO TEM technology_metrics!
}
```

### Mas deveria enviar:
```json
{
  "metadata": {...},
  "summary": {...},
  "technology_metrics": {
    "distribution": {
      "Fiber": 560,
      "Cable": 450
    }
  }
}
```

---

## 🔍 **POR QUE O SEEDER FUNCIONA?**

O seeder lê arquivos que têm `data.technologies`, e o `CreateDailyReportUseCase` **converte** isso para `technology_metrics.distribution` antes de salvar.

Mas a API recebe dados que **não têm** `technology_metrics` nem `data.technologies`!

---

## ✅ **SOLUÇÃO**

O WordPress precisa enviar `technology_metrics.distribution` no payload, OU o backend precisa inferir/calcular isso a partir dos providers.

**Opção 1**: WordPress envia `technology_metrics` (recomendado)
**Opção 2**: Backend calcula a partir de `providers.top_providers[].technology`

