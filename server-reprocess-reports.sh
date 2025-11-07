#!/bin/bash

# Script para reprocessar reports DIRETAMENTE no servidor (sem Docker)

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  🔄 REPROCESSAMENTO DE TODOS OS REPORTS - SERVIDOR            ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${BLUE}━━━ Passo 1: Verificando estado atual ━━━${NC}\n"

php artisan tinker --execute="
echo '📊 ESTADO ATUAL:' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo PHP_EOL;

\$totalReports = App\Models\Report::count();
\$pending = App\Models\Report::where('status', 'pending')->count();
\$processed = App\Models\Report::where('status', 'processed')->count();

echo '   Total de Reports: ' . \$totalReports . PHP_EOL;
echo '   Pending: ' . \$pending . PHP_EOL;
echo '   Processed: ' . \$processed . PHP_EOL;
echo PHP_EOL;

echo '   ReportSummary: ' . App\Models\ReportSummary::count() . PHP_EOL;
echo '   ReportProvider: ' . App\Models\ReportProvider::count() . PHP_EOL;
echo '   ReportState: ' . App\Models\ReportState::count() . PHP_EOL;
echo '   ReportCity: ' . App\Models\ReportCity::count() . PHP_EOL;
echo '   ReportZipCode: ' . App\Models\ReportZipCode::count() . PHP_EOL;
echo PHP_EOL;
"

echo ""
echo -e "${YELLOW}⚠️  Este script vai LIMPAR e REPROCESSAR todos os reports.${NC}"
echo -e "${YELLOW}   Isso pode demorar alguns minutos dependendo da quantidade.${NC}"
echo ""
read -p "Deseja continuar? (s/N): " CONFIRM

if [[ ! "$CONFIRM" =~ ^[Ss]$ ]]; then
    echo ""
    echo -e "${RED}❌ Operação cancelada pelo usuário.${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}━━━ Passo 2: Limpando dados existentes ━━━${NC}\n"

php artisan tinker --execute="
echo '🗑️  Limpando tabelas relacionadas...' . PHP_EOL;
App\Models\ReportSummary::truncate();
App\Models\ReportProvider::truncate();
App\Models\ReportState::truncate();
App\Models\ReportCity::truncate();
App\Models\ReportZipCode::truncate();
echo '✅ Tabelas limpas!' . PHP_EOL;
"

echo ""
echo -e "${BLUE}━━━ Passo 3: Reprocessando todos os reports ━━━${NC}\n"

php artisan tinker --execute="
echo '🔄 Iniciando reprocessamento...' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo PHP_EOL;

\$reports = App\Models\Report::all();
\$total = \$reports->count();
\$processor = app(App\Application\Services\ReportProcessor::class);
\$errors = 0;
\$success = 0;

foreach (\$reports as \$index => \$report) {
    try {
        \$processor->process(\$report->id, \$report->raw_data);
        \$success++;
        if ((\$index + 1) % 20 == 0 || \$index == 0 || \$index == \$total - 1) {
            echo '   Processados: ' . (\$index + 1) . '/' . \$total . ' (' . round(((\$index + 1)/\$total)*100, 1) . '%)' . PHP_EOL;
        }
    } catch (\Exception \$e) {
        \$errors++;
        echo '   ❌ Erro no report ' . \$report->id . ': ' . \$e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo '✅ Sucesso: ' . \$success . ' reports' . PHP_EOL;
echo '❌ Erros: ' . \$errors . ' reports' . PHP_EOL;
echo PHP_EOL;
"

echo ""
echo -e "${BLUE}━━━ Passo 4: Verificando resultado final ━━━${NC}\n"

php artisan tinker --execute="
echo '╔════════════════════════════════════════════════════════════════╗' . PHP_EOL;
echo '║  📊 RESULTADO FINAL                                            ║' . PHP_EOL;
echo '╚════════════════════════════════════════════════════════════════╝' . PHP_EOL;
echo PHP_EOL;

echo '📋 REPORTS:' . PHP_EOL;
echo '   Total: ' . App\Models\Report::count() . PHP_EOL;
echo '   Processed: ' . App\Models\Report::where('status', 'processed')->count() . PHP_EOL;
echo PHP_EOL;

echo '🗄️  DADOS PROCESSADOS:' . PHP_EOL;
echo '   Summaries: ' . App\Models\ReportSummary::count() . PHP_EOL;
echo '   Providers nos reports: ' . App\Models\ReportProvider::count() . PHP_EOL;
echo '   Estados nos reports: ' . App\Models\ReportState::count() . PHP_EOL;
echo '   Cidades nos reports: ' . App\Models\ReportCity::count() . PHP_EOL;
echo '   CEPs nos reports: ' . App\Models\ReportZipCode::count() . PHP_EOL;
echo PHP_EOL;

echo '📚 ENTIDADES ÚNICAS:' . PHP_EOL;
echo '   Provedores cadastrados: ' . App\Models\Provider::count() . PHP_EOL;
echo '   Estados cadastrados: ' . App\Models\State::count() . PHP_EOL;
echo '   Cidades cadastradas: ' . App\Models\City::count() . PHP_EOL;
echo '   CEPs cadastrados: ' . App\Models\ZipCode::count() . PHP_EOL;
echo PHP_EOL;

echo '🌐 REPORTS POR DOMÍNIO:' . PHP_EOL;
\$domains = App\Models\Domain::where('is_active', true)->get();
foreach (\$domains as \$domain) {
    \$count = \$domain->reports()->count();
    \$processed = \$domain->reports()->where('status', 'processed')->count();
    \$isReal = \$domain->name === 'zip.50g.io';
    \$badge = \$isReal ? '📊 REAL' : '🎲 FICTÍCIO';
    echo '   • ' . \$domain->name . ': ' . \$count . ' reports (' . \$processed . ' processados) ' . \$badge . PHP_EOL;
}
echo PHP_EOL;
"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✅ REPROCESSAMENTO CONCLUÍDO COM SUCESSO!                     ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${CYAN}💡 Agora todos os dados, gráficos e métricas devem aparecer normalmente!${NC}"
echo ""

