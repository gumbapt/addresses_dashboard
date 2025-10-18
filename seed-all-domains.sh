#!/bin/bash

# Script para popular todos os domínios com dados
# - Domínio real (zip.50g.io) recebe dados reais
# - Domínios fictícios recebem dados sintéticos (com variação)

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  📊 SEED COMPLETO - TODOS OS DOMÍNIOS                         ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Parse command line arguments
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
        --reset)
            RESET=true
            shift
            ;;
        *)
            echo "Opção desconhecida: $1"
            echo "Uso: $0 [--dry-run] [--force] [--limit N] [--date-from YYYY-MM-DD] [--date-to YYYY-MM-DD] [--reset]"
            exit 1
            ;;
    esac
done

# Step 1: Reset database if requested
if [ "$RESET" = true ]; then
    echo "🗄️  Passo 1: Resetando database..."
    echo ""
    docker-compose exec app php artisan migrate:fresh --seed
    echo ""
    echo "✅ Database resetada!"
    echo ""
fi

# Step 2: Seed domains
echo "🌐 Passo 2: Criando/Verificando domínios..."
echo ""
docker-compose exec app php artisan db:seed --class=DomainSeeder
echo ""

# Step 3: Seed reports for all domains
echo "📊 Passo 3: Populando todos os domínios com relatórios..."
echo ""
docker-compose exec app php artisan reports:seed-all-domains $DRY_RUN $FORCE $LIMIT $DATE_FROM $DATE_TO
echo ""

# Step 4: Wait for processing (if not dry-run)
if [ -z "$DRY_RUN" ]; then
    echo "⏳ Passo 4: Aguardando processamento dos jobs..."
    echo "   (Verificando a cada 3 segundos)"
    echo ""
    
    for i in {1..20}; do
        sleep 3
        TOTAL_REPORTS=$(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Report::count();" 2>/dev/null | tail -n 1)
        PROCESSED=$(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Report::where('status', 'processed')->count();" 2>/dev/null | tail -n 1)
        echo "   Relatórios processados: $PROCESSED / $TOTAL_REPORTS"
        
        if [ "$PROCESSED" = "$TOTAL_REPORTS" ] && [ "$PROCESSED" != "0" ]; then
            echo ""
            echo "✅ Todos os relatórios foram processados!"
            break
        fi
    done
    echo ""
fi

# Step 5: Display summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 RESUMO FINAL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

docker-compose exec -T app php artisan tinker --execute="
echo '🌐 DOMÍNIOS E RELATÓRIOS:' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo PHP_EOL;

\$domains = App\Models\Domain::where('is_active', true)->get();
\$totalReports = 0;

foreach (\$domains as \$domain) {
    \$reportCount = \$domain->reports()->count();
    \$processedCount = \$domain->reports()->where('status', 'processed')->count();
    \$totalReports += \$reportCount;
    
    echo '🌐 ' . \$domain->name . PHP_EOL;
    echo '   Total de relatórios: ' . \$reportCount . PHP_EOL;
    echo '   Processados: ' . \$processedCount . PHP_EOL;
    
    if (\$processedCount > 0) {
        \$summary = \$domain->reports()->where('status', 'processed')->first();
        if (\$summary && \$summary->raw_data) {
            \$totalRequests = \$summary->raw_data['summary']['total_requests'] ?? 0;
            echo '   Exemplo (primeiro relatório): ' . \$totalRequests . ' requisições' . PHP_EOL;
        }
    }
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

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎯 PRÓXIMOS PASSOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Para testar o dashboard de um domínio específico:"
echo "   TOKEN=\$(curl -s http://localhost:8006/api/admin/login -X POST -H \"Content-Type: application/json\" -d '{\"email\":\"admin@dashboard.com\",\"password\":\"password123\"}' | jq -r '.token')"
echo "   curl -s \"http://localhost:8006/api/admin/reports/domain/1/dashboard\" -H \"Authorization: Bearer \$TOKEN\" | jq '.data.kpis'"
echo ""
echo "✅ Para comparar métricas entre domínios:"
echo "   ./compare-domains.sh"
echo ""
echo "✅ Para implementar ranking global:"
echo "   Ver docs/SISTEMA_RELATORIOS_DESIGN_COMPLETO.md - Seção 'Cross-Domain'"
echo ""
echo "🎉 Seed completo! Sistema pronto para análise cross-domain!"
echo ""

