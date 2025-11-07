# 🖥️ Scripts para Servidor (Sem Docker)

## 📋 Scripts Disponíveis

Estes scripts rodam **DIRETAMENTE** no servidor via SSH, sem usar `docker-compose`.

---

## 🚀 1. Setup Completo

### **server-setup-with-reports.sh**

**Uso:**
```bash
# Setup completo
./server-setup-with-reports.sh

# Modo rápido (5 arquivos)
./server-setup-with-reports.sh --quick

# Período específico
./server-setup-with-reports.sh --date-from=2025-06-27 --date-to=2025-06-30

# Limite de arquivos
./server-setup-with-reports.sh --limit=10
```

**O que faz:**
1. ✅ Reset do banco (`migrate:fresh --seed`)
2. ✅ Cria domínios
3. ✅ Popula reports sincronamente
4. ✅ Mostra resumo final

---

## 🔄 2. Reprocessar Reports

### **server-reprocess-reports.sh**

**Uso:**
```bash
./server-reprocess-reports.sh
```

**Quando usar:**
- Reports existem mas dados estão vazios
- Após atualizar lógica de processamento
- Corrigir dados corrompidos

**O que faz:**
1. ✅ Mostra estado atual
2. ✅ Pede confirmação
3. ✅ Limpa tabelas relacionadas
4. ✅ Reprocessa todos os reports
5. ✅ Mostra resultado final

---

## 📊 3. Popular Reports

### **server-seed-reports.sh**

**Uso:**
```bash
# Popular reports
./server-seed-reports.sh

# Com limite
./server-seed-reports.sh --limit=10

# Teste (dry-run)
./server-seed-reports.sh --dry-run

# Forçar (sobrescrever)
./server-seed-reports.sh --force

# Período específico
./server-seed-reports.sh --date-from=2025-07-01 --date-to=2025-07-31
```

**Quando usar:**
- Após `migrate:fresh --seed`
- Adicionar mais reports
- Atualizar reports existentes

---

## 🔧 Comandos Diretos

### **Popular Reports:**
```bash
php artisan reports:seed-all-domains --sync --limit=10
```

### **Reprocessar:**
```bash
php artisan tinker --execute="
\$processor = app(App\Application\Services\ReportProcessor::class);
\$reports = App\Models\Report::all();
foreach (\$reports as \$report) {
    \$processor->process(\$report->id, \$report->raw_data);
}
"
```

### **Verificar Estado:**
```bash
php artisan tinker --execute="
echo 'Reports: ' . App\Models\Report::count() . PHP_EOL;
echo 'Summaries: ' . App\Models\ReportSummary::count() . PHP_EOL;
echo 'Providers: ' . App\Models\ReportProvider::count() . PHP_EOL;
"
```

---

## 📁 Estrutura de Arquivos

```
├── server-setup-with-reports.sh    # Setup completo
├── server-reprocess-reports.sh     # Reprocessar
├── server-seed-reports.sh          # Popular reports
├── full-setup-with-reports.sh      # Versão Docker
├── reprocess-all-reports.sh        # Versão Docker
└── seed-all-domains.sh             # Versão Docker
```

---

## 🎯 Quando Usar Qual Script

### **No Servidor (via SSH):**
```bash
# Use os scripts server-*
./server-setup-with-reports.sh --quick
./server-reprocess-reports.sh
./server-seed-reports.sh --limit=10
```

### **Local (com Docker):**
```bash
# Use os scripts sem prefixo server-
./full-setup-with-reports.sh --quick
./reprocess-all-reports.sh
./seed-all-domains.sh --limit=10
```

---

## 💡 Fluxo Típico no Servidor

### **1. Primeira Vez (Setup Inicial):**
```bash
# SSH no servidor
ssh user@seu-servidor.com

# Ir para pasta do projeto
cd /var/www/addresses_dashboard

# Setup completo
./server-setup-with-reports.sh --quick
```

### **2. Adicionar Mais Reports:**
```bash
# SSH no servidor
ssh user@seu-servidor.com

# Popular mais reports
./server-seed-reports.sh --limit=20
```

### **3. Corrigir Dados:**
```bash
# SSH no servidor
ssh user@seu-servidor.com

# Reprocessar tudo
./server-reprocess-reports.sh
```

---

## ⚙️ Requisitos

✅ PHP instalado  
✅ Composer instalado  
✅ Laravel configurado  
✅ Banco de dados acessível  
✅ Arquivos em `docs/daily_reports/`  
❌ Docker **NÃO** necessário  
❌ Queue workers **NÃO** necessários (usa --sync)  

---

## 🔐 Exemplo Completo

```bash
# 1. SSH no servidor
ssh address3@37.27.192.116

# 2. Ir para pasta do projeto
cd /home/address3/addresses_dashboard

# 3. Setup completo com reports
./server-setup-with-reports.sh --quick

# 4. Testar API
TOKEN=$(curl -s http://localhost/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

# 5. Ver dashboard
curl -s http://localhost/api/admin/reports/domain/1/dashboard \
  -H "Authorization: Bearer $TOKEN" | jq '.data.kpis'
```

---

## 🐛 Troubleshooting

### **Erro: "Command not found: php"**
```bash
# Verificar PHP
which php
php -v

# Se não estiver no PATH, usar caminho completo
/usr/bin/php artisan ...
```

### **Erro: "Permission denied"**
```bash
# Dar permissão de execução
chmod +x server-*.sh

# Ou rodar com bash
bash server-setup-with-reports.sh --quick
```

### **Erro: "Class not found"**
```bash
# Atualizar autoload
composer dump-autoload

# Limpar cache
php artisan cache:clear
php artisan config:clear
```

---

## 📊 Comparação: Servidor vs Docker

| Aspecto | Servidor (SSH) | Docker (Local) |
|---------|----------------|----------------|
| **Comando** | `php artisan` | `docker-compose exec app php artisan` |
| **Scripts** | `server-*.sh` | Scripts normais |
| **Workers** | Não precisa (--sync) | Pode usar ou não |
| **Setup** | Mais simples | Mais complexo |
| **Uso** | Produção VPS | Desenvolvimento |

---

## ✅ Checklist Antes de Rodar

Antes de executar os scripts no servidor:

- [ ] SSH conectado
- [ ] Na pasta do projeto
- [ ] `.env` configurado corretamente
- [ ] Banco de dados acessível
- [ ] Arquivos JSON em `docs/daily_reports/`
- [ ] Permissões de execução nos scripts

---

**Criado em:** Novembro 7, 2025  
**Versão:** 1.0  
**Status:** ✅ Pronto para Uso no Servidor

