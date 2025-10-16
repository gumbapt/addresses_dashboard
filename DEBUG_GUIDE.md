# 🔍 Guia de Debug do Sistema de Relatórios

## 🎯 Objetivo

Este guia mostra como debugar o fluxo completo de submissão e processamento de relatórios.

---

## 🚀 **Método 1: Script Automático de Debug**

### **Execução Simples:**
```bash
./debug-report-flow.sh
```

### **O que o script faz:**

1. ✅ Mostra estado inicial do banco de dados
2. ✅ Oferece opção de limpar dados anteriores
3. ✅ Verifica/cria domínio necessário
4. ✅ Submete o newdata.json via API
5. ✅ Acompanha o processamento
6. ✅ Mostra dados processados
7. ✅ Testa API de listagem
8. ✅ Fornece comandos úteis para próximos passos

### **Exemplo de Output:**
```
╔════════════════════════════════════════════════════════════════╗
║  🔍 DEBUG COMPLETO DO FLUXO DE RELATÓRIOS                     ║
╚════════════════════════════════════════════════════════════════╝

━━━ PASSO 1: ESTADO INICIAL DO BANCO ━━━

📊 Contagem de registros ANTES da submissão:
Reports: 0
Report Summaries: 0
Report Providers: 0
Report States: 0
Report Cities: 0
Report ZipCodes: 0

━━━ PASSO 2: VERIFICAR DOMÍNIO ━━━

✅ Domínio encontrado: zip.50g.io

━━━ PASSO 3: SUBMISSÃO DO RELATÓRIO ━━━

✅ Relatório submetido com sucesso!
🎉 Report ID: 1
📅 Report Date: 2025-10-11
📊 Status: pending

━━━ PASSO 4: PROCESSAMENTO DO JOB ━━━

⏳ Aguardando processamento do job...

━━━ PASSO 5: DADOS PROCESSADOS ━━━

Reports: 1
Report Summaries: 1
Report Providers: 8
Report States: 38
Report Cities: 20
Report ZipCodes: 100
```

---

## 🚀 **Método 2: Comando Artisan de Debug**

### **Debug do Relatório Mais Recente:**
```bash
docker-compose exec app php artisan report:debug --latest
```

### **Debug de Relatório Específico:**
```bash
docker-compose exec app php artisan report:debug 1
```

### **Debug Completo (todos os detalhes):**
```bash
docker-compose exec app php artisan report:debug --latest --full
```

### **O que mostra:**
- 📄 Informações básicas do relatório
- 📈 Resumo estatístico
- 📡 Top 5 provedores processados
- 🗺️ Estados processados (com --full)
- 🏙️ Cidades processadas
- 📮 CEPs processados
- 💾 Total de dados mestres criados
- ⚠️ Erros (se houver)
- 💡 Comandos úteis

---

## 🔧 **Método 3: Debug Manual Passo a Passo**

### **Passo 1: Ver Estado Inicial**
```bash
docker-compose exec app php artisan tinker --execute="
echo '📊 Estado do Banco:' . PHP_EOL;
echo 'Reports: ' . App\Models\Report::count() . PHP_EOL;
echo 'Providers: ' . App\Models\Provider::count() . PHP_EOL;
echo 'States: ' . App\Models\State::count() . PHP_EOL;
echo 'Cities: ' . App\Models\City::count() . PHP_EOL;
echo 'ZipCodes: ' . App\Models\ZipCode::count() . PHP_EOL;
"
```

### **Passo 2: Submeter Relatório**
```bash
./submit-test-report.sh
```

### **Passo 3: Ver Relatório Criado**
```bash
docker-compose exec app php artisan tinker --execute="
\$report = App\Models\Report::latest()->first();
echo 'ID: ' . \$report->id . PHP_EOL;
echo 'Status: ' . \$report->status . PHP_EOL;
echo 'Data: ' . \$report->report_date . PHP_EOL;
"
```

### **Passo 4: Processar Manualmente (se pending)**
```bash
# Se status = 'pending', processar o job:
docker-compose exec app php artisan queue:work --once

# Ou despachar manualmente:
docker-compose exec app php artisan tinker --execute="
\$report = App\Models\Report::latest()->first();
App\Jobs\ProcessReportJob::dispatch(\$report->id, \$report->raw_data);
echo 'Job despachado' . PHP_EOL;
"
```

### **Passo 5: Verificar Processamento**
```bash
docker-compose exec app php artisan tinker --execute="
\$report = App\Models\Report::find(1);
echo 'Status: ' . \$report->status . PHP_EOL;
echo 'Summary existe: ' . (\$report->summary ? 'Sim' : 'Não') . PHP_EOL;
echo 'Providers: ' . App\Models\ReportProvider::where('report_id', 1)->count() . PHP_EOL;
echo 'States: ' . App\Models\ReportState::where('report_id', 1)->count() . PHP_EOL;
echo 'Cities: ' . App\Models\ReportCity::where('report_id', 1)->count() . PHP_EOL;
echo 'ZipCodes: ' . App\Models\ReportZipCode::where('report_id', 1)->count() . PHP_EOL;
"
```

### **Passo 6: Ver Dados Detalhados**
```bash
# Ver raw_data completo
docker-compose exec app php artisan tinker --execute="
\$report = App\Models\Report::find(1);
print_r(\$report->raw_data);
"

# Ver summary processado
docker-compose exec app php artisan tinker --execute="
\$summary = App\Models\ReportSummary::where('report_id', 1)->first();
print_r(\$summary->toArray());
"
```

### **Passo 7: Testar API**
```bash
# Login
TOKEN=$(curl -s http://localhost:8006/api/admin/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

# Listar relatórios
curl -s "http://localhost:8006/api/admin/reports" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Ver relatório específico
curl -s "http://localhost:8006/api/admin/reports/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'
```

---

## 🐛 **Troubleshooting**

### **Problema: Relatório fica "pending"**
```bash
# Verificar se queue está rodando
docker-compose ps | grep queue

# Processar manualmente
docker-compose exec app php artisan queue:work --once

# Ver logs
docker-compose logs -f queue_events
```

### **Problema: Dados não aparecem processados**
```bash
# Verificar se o job executou
docker-compose logs app | grep "ProcessReportJob"

# Verificar erros
docker-compose exec app php artisan tinker --execute="
\$report = App\Models\Report::latest()->first();
echo 'Status: ' . \$report->status . PHP_EOL;
if (\$report->status === 'failed') {
    echo 'Verifique os logs para detalhes do erro' . PHP_EOL;
}
"
```

### **Problema: 502 Bad Gateway**
```bash
# Verificar se app está rodando
docker-compose ps app

# Reiniciar app
docker-compose restart app

# Ver logs
docker-compose logs -f app
```

### **Problema: Validation errors**
```bash
# Verificar estrutura do JSON
cat docs/newdata.json | jq 'keys'

# Deve ter: source, metadata, summary (obrigatórios)
# Opcional: providers, geographic, performance, etc.
```

---

## 📊 **Queries Úteis para Debug**

### **Ver último relatório criado:**
```sql
SELECT id, domain_id, report_date, status, created_at 
FROM reports 
ORDER BY created_at DESC 
LIMIT 1;
```

### **Ver processamento de um relatório:**
```sql
SELECT 
    (SELECT COUNT(*) FROM report_summaries WHERE report_id = 1) as summaries,
    (SELECT COUNT(*) FROM report_providers WHERE report_id = 1) as providers,
    (SELECT COUNT(*) FROM report_states WHERE report_id = 1) as states,
    (SELECT COUNT(*) FROM report_cities WHERE report_id = 1) as cities,
    (SELECT COUNT(*) FROM report_zip_codes WHERE report_id = 1) as zip_codes;
```

### **Ver top providers de um relatório:**
```sql
SELECT rp.*, p.name, p.slug
FROM report_providers rp
LEFT JOIN providers p ON p.id = rp.provider_id
WHERE rp.report_id = 1
ORDER BY rp.rank
LIMIT 10;
```

---

## 🎯 **Fluxo Completo de Teste**

### **1. Preparar Ambiente**
```bash
# Iniciar containers
docker-compose up -d

# Verificar status
docker-compose ps
```

### **2. Executar Debug Completo**
```bash
# Executar script de debug
./debug-report-flow.sh
```

### **3. Processar Job (se necessário)**
```bash
# Se o relatório ficar "pending"
docker-compose exec app php artisan queue:work --once
```

### **4. Verificar Resultados**
```bash
# Via comando
docker-compose exec app php artisan report:debug --latest --full

# Via API
TOKEN=$(curl -s http://localhost:8006/api/admin/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' \
  | jq -r '.token')

curl -s "http://localhost:8006/api/admin/reports" \
  -H "Authorization: Bearer $TOKEN" | jq '.'
```

### **5. Limpar para Novo Teste (opcional)**
```bash
docker-compose exec app php artisan tinker --execute="
App\Models\ReportZipCode::truncate();
App\Models\ReportCity::truncate();
App\Models\ReportState::truncate();
App\Models\ReportProvider::truncate();
App\Models\ReportSummary::truncate();
App\Models\Report::truncate();
echo 'Dados limpos' . PHP_EOL;
"
```

---

## 📝 **Logs e Monitoramento**

### **Ver logs em tempo real:**
```bash
# Logs da aplicação
docker-compose logs -f app

# Filtrar por relatórios
docker-compose logs -f app | grep -i report

# Ver jobs processados
docker-compose logs -f queue_events
```

### **Ver jobs na fila:**
```bash
docker-compose exec app php artisan queue:monitor

# Ou via tinker
docker-compose exec app php artisan tinker --execute="
echo 'Jobs pendentes: ' . DB::table('jobs')->count() . PHP_EOL;
"
```

---

## 🎯 **Checklist de Validação**

Após submeter e processar um relatório, verifique:

- [ ] Relatório criado no banco (`reports` table)
- [ ] Status mudou de 'pending' para 'processed'
- [ ] Summary foi criado (`report_summaries`)
- [ ] Providers foram processados (`report_providers`)
- [ ] Estados foram processados (`report_states`)
- [ ] Cidades foram processadas (`report_cities`)
- [ ] CEPs foram processados (`report_zip_codes`)
- [ ] Entidades mestras criadas (States, Cities, ZipCodes, Providers)
- [ ] API retorna o relatório corretamente
- [ ] Dados estão consistentes

---

## 💡 **Dicas de Debug**

1. **Sempre verifique os logs** - Maioria dos problemas aparece nos logs
2. **Use o comando report:debug** - Visão rápida e completa
3. **Processe jobs manualmente** - Para debug sem queue worker
4. **Verifique raw_data** - Dados originais sempre preservados
5. **Use jq** - Para formatar JSON nas respostas

---

**Arquivos:**
- Script: `debug-report-flow.sh`
- Comando: `app/Console/Commands/DebugReportProcessing.php`
- Submissão: `submit-test-report.sh`

---

*Última atualização: Outubro 2024*  
*Status: ✅ Operacional*

