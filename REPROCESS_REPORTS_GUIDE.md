# 🔄 Guia de Reprocessamento de Reports

## 📋 Quando Usar

Use o script de reprocessamento quando:

✅ Os reports estão criados mas as tabelas relacionadas estão vazias  
✅ Após um `migrate:fresh --seed` e seed de reports  
✅ Quando os gráficos e dados não aparecem no dashboard  
✅ Após atualizar a lógica do `ReportProcessor`  

---

## 🚀 Script de Reprocessamento

### **Uso:**

```bash
./reprocess-all-reports.sh
```

### **O que o script faz:**

1. ✅ Verifica o estado atual dos reports
2. ✅ Pergunta confirmação antes de prosseguir
3. ✅ Limpa todas as tabelas relacionadas:
   - `report_summaries`
   - `report_providers`
   - `report_states`
   - `report_cities`
   - `report_zip_codes`
4. ✅ Reprocessa **TODOS** os reports
5. ✅ Mostra estatísticas finais
6. ✅ Fornece comandos para testar

---

## 📊 Exemplo de Saída

```
╔════════════════════════════════════════════════════════════════╗
║  🔄 REPROCESSAMENTO DE TODOS OS REPORTS                       ║
╚════════════════════════════════════════════════════════════════╝

━━━ Passo 1: Verificando estado atual ━━━

📊 ESTADO ATUAL:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   Total de Reports: 160
   Pending: 0
   Processed: 160

   ReportSummary: 0
   ReportProvider: 0
   ReportState: 0
   ReportCity: 0
   ReportZipCode: 0

⚠️  Este script vai LIMPAR e REPROCESSAR todos os reports.
   Isso pode demorar alguns minutos dependendo da quantidade.

Deseja continuar? (s/N): s

━━━ Passo 2: Limpando dados existentes ━━━

🗑️  Limpando tabelas relacionadas...
✅ Tabelas limpas!

━━━ Passo 3: Reprocessando todos os reports ━━━

🔄 Iniciando reprocessamento...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   Processados: 20/160 (12.5%)
   Processados: 40/160 (25.0%)
   Processados: 60/160 (37.5%)
   Processados: 80/160 (50.0%)
   Processados: 100/160 (62.5%)
   Processados: 120/160 (75.0%)
   Processados: 140/160 (87.5%)
   Processados: 160/160 (100.0%)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Sucesso: 160 reports
❌ Erros: 0 reports

━━━ Passo 4: Verificando resultado final ━━━

╔════════════════════════════════════════════════════════════════╗
║  📊 RESULTADO FINAL                                            ║
╚════════════════════════════════════════════════════════════════╝

📋 REPORTS:
   Total: 160
   Processed: 160

🗄️  DADOS PROCESSADOS:
   Summaries: 160
   Providers nos reports: 3,098
   Estados nos reports: 1,298
   Cidades nos reports: 2,404
   CEPs nos reports: 4,480

📚 ENTIDADES ÚNICAS:
   Provedores cadastrados: 122
   Estados cadastrados: 43
   Cidades cadastradas: 442
   CEPs cadastrados: 957

🌐 REPORTS POR DOMÍNIO:
   • zip.50g.io: 40 reports (40 processados) 📊 REAL
   • smarterhome.ai: 40 reports (40 processados) 🎲 FICTÍCIO
   • ispfinder.net: 40 reports (40 processados) 🎲 FICTÍCIO
   • broadbandcheck.io: 40 reports (40 processados) 🎲 FICTÍCIO

╔════════════════════════════════════════════════════════════════╗
║  ✅ REPROCESSAMENTO CONCLUÍDO COM SUCESSO!                     ║
╚════════════════════════════════════════════════════════════════╝
```

---

## ⚡ Alternativa Rápida (Via Tinker)

Se quiser reprocessar manualmente via tinker:

```bash
docker-compose exec app php artisan tinker --execute="
\$processor = app(App\Application\Services\ReportProcessor::class);
\$reports = App\Models\Report::all();
foreach (\$reports as \$report) {
    try {
        \$processor->process(\$report->id, \$report->raw_data);
    } catch (\Exception \$e) {
        echo 'Erro: ' . \$e->getMessage() . PHP_EOL;
    }
}
echo 'Concluído!' . PHP_EOL;
"
```

---

## 🔧 Troubleshooting

### **Problema: "Duplicate entry" error**

Se você já rodou o script uma vez e os dados existem, ele vai tentar inserir novamente. Solução:

```bash
# Limpar as tabelas antes
docker-compose exec app php artisan tinker --execute="
App\Models\ReportSummary::truncate();
App\Models\ReportProvider::truncate();
App\Models\ReportState::truncate();
App\Models\ReportCity::truncate();
App\Models\ReportZipCode::truncate();
"

# Depois rodar o script novamente
./reprocess-all-reports.sh
```

---

### **Problema: Queue workers não estão rodando**

Verificar se os workers estão ativos:

```bash
docker-compose ps queue_messages queue_events
```

Se não estiverem, reiniciar:

```bash
docker-compose restart queue_messages queue_events
```

---

### **Problema: Reports marcados como 'processed' mas dados vazios**

Isso significa que os jobs NÃO foram executados. Use o script de reprocessamento:

```bash
./reprocess-all-reports.sh
```

---

## 📝 O Que Foi Corrigido

### **Problema Original:**

O `SeedAllDomainsWithReports` estava passando os dados originais (com estrutura `['data']`) para o `ProcessReportJob`, mas o report tinha os dados convertidos (sem `['data']`).

### **Solução:**

```php
// ANTES (errado)
$report = $this->createDailyReportUseCase->execute($domain->id, $data);
ProcessReportJob::dispatch($report->getId(), $data); // ❌ Dados originais

// DEPOIS (correto)
$report = $this->createDailyReportUseCase->execute($domain->id, $data);
$reportModel = \App\Models\Report::find($report->getId());
ProcessReportJob::dispatch($report->getId(), $reportModel->raw_data); // ✅ Dados convertidos
```

---

## 🎯 Quando NÃO Precisa Reprocessar

Você **NÃO** precisa reprocessar se:

- ✅ Os dados já aparecem no dashboard
- ✅ As tabelas `report_summaries`, `report_providers`, etc. estão populadas
- ✅ O comando `./seed-all-domains.sh` foi executado APÓS a correção do bug

---

## 🚀 Fluxo Correto Após migrate:fresh --seed

```bash
# 1. Reset do banco
docker-compose exec app php artisan migrate:fresh --seed

# 2. Popular reports
./seed-all-domains.sh --limit=10

# 3. (OPCIONAL) Se os dados não aparecerem, reprocessar
./reprocess-all-reports.sh

# 4. Testar
TOKEN=$(curl -s http://localhost:8007/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' | jq -r '.token')

curl -s http://localhost:8007/api/admin/reports/domain/1/dashboard \
  -H "Authorization: Bearer $TOKEN" | jq '.data.kpis'
```

---

## 📊 Verificar se Precisa Reprocessar

Execute este comando para verificar:

```bash
docker-compose exec -T app php artisan tinker --execute="
echo 'Reports: ' . App\Models\Report::count() . PHP_EOL;
echo 'Summaries: ' . App\Models\ReportSummary::count() . PHP_EOL;
echo 'Providers: ' . App\Models\ReportProvider::count() . PHP_EOL;

if (App\Models\Report::count() > 0 && App\Models\ReportSummary::count() == 0) {
    echo PHP_EOL . '⚠️  ATENÇÃO: Reports existem mas dados estão vazios!' . PHP_EOL;
    echo '    Execute: ./reprocess-all-reports.sh' . PHP_EOL;
}
"
```

---

**Criado em:** Novembro 7, 2025  
**Versão:** 1.0  
**Status:** ✅ Pronto para Uso

