#!/bin/bash

# Script para popular reports DIRETAMENTE no servidor (sem Docker)

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  📊 POPULAR REPORTS - SERVIDOR DE PRODUÇÃO                     ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Parse arguments
DRY_RUN=""
FORCE=""
LIMIT=""
DATE_FROM=""
DATE_TO=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN="--dry-run"
            shift
            ;;
        --force)
            FORCE="--force"
            shift
            ;;
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
        *)
            echo "Opção desconhecida: $1"
            echo "Uso: $0 [--dry-run] [--force] [--limit N] [--date-from YYYY-MM-DD] [--date-to YYYY-MM-DD]"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}━━━ Passo 1: Verificando Domínios ━━━${NC}\n"

php artisan db:seed --class=DomainSeeder

echo ""
echo -e "${GREEN}✅ Domínios verificados!${NC}"
echo ""

echo -e "${BLUE}━━━ Passo 2: Populando Reports ━━━${NC}"
echo -e "${CYAN}   Modo: SÍNCRONO (processamento imediato)${NC}\n"

php artisan reports:seed-all-domains --sync $DRY_RUN $FORCE $LIMIT $DATE_FROM $DATE_TO

echo ""
echo -e "${GREEN}✅ Concluído!${NC}"
echo ""

echo -e "${BLUE}━━━ Resumo ━━━${NC}\n"

php artisan tinker --execute="
echo '╔════════════════════════════════════════════════════════════════╗' . PHP_EOL;
echo '║  📊 RESUMO                                                     ║' . PHP_EOL;
echo '╚════════════════════════════════════════════════════════════════╝' . PHP_EOL;
echo PHP_EOL;

\$domains = App\Models\Domain::where('is_active', true)->get();
echo '🌐 DOMÍNIOS E RELATÓRIOS:' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo PHP_EOL;

\$totalReports = 0;
foreach (\$domains as \$domain) {
    \$reportCount = \$domain->reports()->count();
    \$processedCount = \$domain->reports()->where('status', 'processed')->count();
    \$totalReports += \$reportCount;
    
    echo '🌐 ' . \$domain->name . PHP_EOL;
    echo '   Total de relatórios: ' . \$reportCount . PHP_EOL;
    echo '   Processados: ' . \$processedCount . PHP_EOL;
    echo PHP_EOL;
}

echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo '📊 TOTAIS:' . PHP_EOL;
echo '   Domínios ativos: ' . \$domains->count() . PHP_EOL;
echo '   Total de relatórios: ' . \$totalReports . PHP_EOL;
echo '   Provedores únicos: ' . App\Models\Provider::count() . PHP_EOL;
echo '   Estados cobertos: ' . App\Models\State::count() . PHP_EOL;
echo PHP_EOL;
"

echo -e "${CYAN}💡 Use: php artisan tinker para verificar os dados${NC}"
echo ""

