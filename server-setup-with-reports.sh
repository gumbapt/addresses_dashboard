#!/bin/bash

# Script para rodar DIRETAMENTE no servidor (sem Docker)

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  🚀 SETUP COMPLETO - SERVIDOR DE PRODUÇÃO                      ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Parse arguments
LIMIT=""
DATE_FROM=""
DATE_TO=""
QUICK=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --limit)
            LIMIT="--limit=$2"
            shift 2
            ;;
        --date-from)
            DATE_FROM="--date-from=$2"
            shift 2
            ;;
        --date-to)
            DATE_TO="--date-to=$2"
            shift 2
            ;;
        --quick)
            QUICK=true
            LIMIT="--limit=5"
            shift
            ;;
        *)
            echo "Opção desconhecida: $1"
            echo "Uso: $0 [--quick] [--limit N] [--date-from YYYY-MM-DD] [--date-to YYYY-MM-DD]"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}━━━ PASSO 1: Reset do Banco de Dados ━━━${NC}\n"

php artisan migrate:fresh --seed

echo ""
echo -e "${GREEN}✅ Banco resetado e seeders executados!${NC}"
echo ""

echo -e "${BLUE}━━━ PASSO 2: Criando/Verificando Domínios ━━━${NC}\n"

php artisan db:seed --class=DomainSeeder

echo ""
echo -e "${GREEN}✅ Domínios criados!${NC}"
echo ""

echo -e "${BLUE}━━━ PASSO 3: Populando Reports (Real + Fictícios) ━━━${NC}"
echo -e "${CYAN}   Modo: SÍNCRONO (processamento imediato, sem queue)${NC}\n"

if [ "$QUICK" = true ]; then
    echo -e "${YELLOW}Modo rápido: 5 arquivos por domínio${NC}\n"
fi

php artisan reports:seed-all-domains --sync $LIMIT $DATE_FROM $DATE_TO

echo ""
echo -e "${GREEN}✅ Reports criados e processados!${NC}"
echo ""

echo -e "${BLUE}━━━ PASSO 4: Resumo Final ━━━${NC}\n"

php artisan tinker --execute="
echo '╔════════════════════════════════════════════════════════════════╗' . PHP_EOL;
echo '║  📊 RESUMO FINAL                                               ║' . PHP_EOL;
echo '╚════════════════════════════════════════════════════════════════╝' . PHP_EOL;
echo PHP_EOL;

\$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
\$domains = App\Models\Domain::where('is_active', true)->get();
\$totalReports = App\Models\Report::count();

echo '👤 Admin: ' . \$admin->email . PHP_EOL;
echo '   Super Admin: ' . (\$admin->is_super_admin ? 'SIM ✅' : 'NÃO ❌') . PHP_EOL;
echo '   Domínios acessíveis: ' . count(\$admin->getAccessibleDomains()) . PHP_EOL;
echo PHP_EOL;

echo '🌐 Domínios Ativos: ' . \$domains->count() . PHP_EOL;
foreach (\$domains as \$domain) {
    \$count = \$domain->reports()->count();
    \$processed = \$domain->reports()->whereHas('summary')->count();
    \$isReal = \$domain->name === 'zip.50g.io';
    \$badge = \$isReal ? '📊 REAL' : '🎲 FICTÍCIO';
    echo '   • ' . \$domain->name . ': ' . \$count . ' reports (' . \$processed . ' processados) ' . \$badge . PHP_EOL;
}
echo PHP_EOL;

echo '📋 REPORTS:' . PHP_EOL;
echo '   Total: ' . \$totalReports . PHP_EOL;
echo '   Processados: ' . App\Models\ReportSummary::count() . PHP_EOL;
echo PHP_EOL;

echo '🗄️  DADOS PROCESSADOS:' . PHP_EOL;
echo '   Summaries: ' . App\Models\ReportSummary::count() . PHP_EOL;
echo '   Providers: ' . App\Models\ReportProvider::count() . PHP_EOL;
echo '   Estados: ' . App\Models\ReportState::count() . PHP_EOL;
echo '   Cidades: ' . App\Models\ReportCity::count() . PHP_EOL;
echo '   CEPs: ' . App\Models\ReportZipCode::count() . PHP_EOL;
echo PHP_EOL;

echo '📚 ENTIDADES ÚNICAS:' . PHP_EOL;
echo '   Provedores: ' . App\Models\Provider::count() . PHP_EOL;
echo '   Estados: ' . App\Models\State::count() . PHP_EOL;
echo '   Cidades: ' . App\Models\City::count() . PHP_EOL;
echo '   CEPs: ' . App\Models\ZipCode::count() . PHP_EOL;
echo PHP_EOL;
"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✅ SETUP COMPLETO! TUDO PRONTO PARA USAR!                     ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${BLUE}━━━ Testar Agora ━━━${NC}\n"

echo -e "${YELLOW}🔐 1. Login via API:${NC}"
echo 'curl -s http://localhost/api/admin/login \'
echo '  -H "Content-Type: application/json" \'
echo '  -d '"'"'{"email":"admin@dashboard.com","password":"password123"}'"'"' | jq -r '"'"'.token'"'"
echo ""

echo -e "${YELLOW}📊 2. Dashboard do zip.50g.io:${NC}"
echo 'TOKEN="seu_token_aqui"'
echo 'curl -s http://localhost/api/admin/reports/domain/1/dashboard \'
echo '  -H "Authorization: Bearer $TOKEN" | jq ".data.kpis"'
echo ""

echo -e "${YELLOW}🏆 3. Ranking Global:${NC}"
echo 'curl -s http://localhost/api/admin/reports/global/domain-ranking \'
echo '  -H "Authorization: Bearer $TOKEN" | jq ".data"'
echo ""

echo -e "${CYAN}💡 Todos os dados, gráficos e métricas agora estão disponíveis!${NC}"
echo ""

