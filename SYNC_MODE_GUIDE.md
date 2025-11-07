# 🔄 Guia do Modo Síncrono (--sync)

## 📋 O Que É?

O modo `--sync` processa os reports **imediatamente** durante a criação, sem usar fila (queue). Isso é essencial para:

✅ **Servidores SEM Docker**  
✅ **Servidores SEM queue workers**  
✅ **Ambientes de produção simples**  
✅ **Testes rápidos**  

---

## 🚀 Como Usar

### **Comando Direto:**

```bash
# Com flag --sync
php artisan reports:seed-all-domains --sync

# Modo rápido (5 arquivos)
php artisan reports:seed-all-domains --sync --limit=5

# Período específico
php artisan reports:seed-all-domains --sync --date-from=2025-06-27 --date-to=2025-06-30
```

---

### **Via Scripts:**

Todos os scripts agora usam `--sync` por padrão:

```bash
# Setup completo
./full-setup-with-reports.sh --quick

# Apenas seed de reports
./seed-all-domains.sh --limit=10
```

---

## ⚡ Diferenças

| Aspecto | **COM --sync** | **SEM --sync** (queue) |
|---------|---------------|------------------------|
| **Workers necessários** | ❌ NÃO | ✅ SIM |
| **Processamento** | Imediato | Assíncrono (background) |
| **Velocidade** | Mais lento | Mais rápido |
| **Uso** | Produção simples | Produção complexa |
| **Servidor** | Qualquer | Docker/Supervisor |

---

## 📊 Exemplo de Uso

### **Modo Síncrono (--sync):**

```bash
php artisan reports:seed-all-domains --sync --limit=5
```

**Saída:**
```
╔════════════════════════════════════════════════════════════════╗
║  📊 SEED DE RELATÓRIOS PARA TODOS OS DOMÍNIOS                 ║
╚════════════════════════════════════════════════════════════════╝

━━━ MODO SÍNCRONO ATIVADO (sem queue) ━━━

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🌐 Processando domínio: zip.50g.io
   Tipo: 📊 DADOS REAIS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   ✅ Processado 1/5
   ✅ Processado 5/5

   📊 Resumo para zip.50g.io:
      Submetidos: 5
      Ignorados: 0
      Erros: 0
```

**Todos os dados já estão processados!** ✅

---

### **Modo Queue (sem --sync):**

```bash
php artisan reports:seed-all-domains --limit=5
```

**Saída:**
```
   ✅ Processado 5/5

   📊 Resumo para zip.50g.io:
      Submetidos: 5
      Ignorados: 0
      Erros: 0
```

**Mas os dados NÃO estão processados ainda!** ⏳

Você precisa aguardar os workers processarem em background (pode demorar minutos).

---

## 🔧 Quando Usar Cada Modo

### **Use --sync SE:**

- ✅ Servidor **NÃO tem Docker**
- ✅ Servidor **NÃO tem Supervisor/Queue workers**
- ✅ Ambiente de **produção simples**
- ✅ Poucos reports (< 100)
- ✅ Quer garantia de processamento **imediato**

### **Use Queue (sem --sync) SE:**

- ✅ Tem Docker com **queue workers rodando**
- ✅ Tem Supervisor configurado
- ✅ Muitos reports (> 100)
- ✅ Quer **performance máxima**
- ✅ Pode aguardar processamento assíncrono

---

## 🎯 Setup para Produção SEM Docker

### **1. Configurar Queue Connection:**

```bash
# .env
QUEUE_CONNECTION=sync
```

Isso faz **TODOS** os jobs rodarem sincronamente automaticamente.

---

### **2. OU usar --sync explicitamente:**

```bash
# Continuar usando QUEUE_CONNECTION=database
# Mas passar --sync quando popular reports

php artisan reports:seed-all-domains --sync
```

---

## ⚙️ Setup para Produção COM Docker/Workers

### **1. Garantir workers rodando:**

```bash
docker-compose ps queue_messages queue_events
```

Deve mostrar ambos como "Up".

---

### **2. NÃO usar --sync:**

```bash
# Sem --sync = usa queue
php artisan reports:seed-all-domains --limit=10
```

Workers vão processar em background.

---

### **3. Monitorar processamento:**

```bash
# Ver jobs na fila
php artisan queue:work --once

# Ver logs
docker-compose logs -f queue_messages
```

---

## 📈 Performance

### **Teste com 20 reports:**

| Modo | Tempo | CPU | Memória |
|------|-------|-----|---------|
| **--sync** | ~60s | Alto | Médio |
| **Queue** | ~15s seed<br>+30s jobs | Médio | Médio |

**Conclusão:** Queue é mais rápido, mas exige workers rodando.

---

## 🐛 Troubleshooting

### **Problema: "Modo síncrono muito lento"**

**Solução:** Use queue se possível:

```bash
# Remover --sync
php artisan reports:seed-all-domains --limit=10
```

---

### **Problema: "Reports criados mas não processados"**

**Causa:** Usou sem `--sync` e workers não estão rodando.

**Solução:** Reprocessar:

```bash
./reprocess-all-reports.sh
```

---

### **Problema: "No servidor não tem Docker"**

**Solução:** Use `--sync` SEMPRE:

```bash
php artisan reports:seed-all-domains --sync
```

---

## 💡 Recomendações

### **Desenvolvimento Local:**
```bash
# Use --sync para simplicidade
./full-setup-with-reports.sh --quick
```

### **Produção Simples (VPS):**
```bash
# Use --sync
php artisan reports:seed-all-domains --sync
```

### **Produção Avançada (Docker):**
```bash
# Use queue (sem --sync)
php artisan reports:seed-all-domains

# Com workers rodando em background
```

---

## ✅ Scripts Atualizados

Todos os scripts agora usam `--sync` por padrão:

- ✅ `full-setup-with-reports.sh`
- ✅ `seed-all-domains.sh`
- ✅ `setup-after-fresh-seed.sh`

Você **NÃO precisa** mais se preocupar com workers!

---

**Criado em:** Novembro 7, 2025  
**Versão:** 1.0  
**Status:** ✅ Pronto para Uso

