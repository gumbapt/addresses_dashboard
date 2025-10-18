#!/bin/bash

# Script para comparar métricas entre domínios

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  📊 COMPARAÇÃO ENTRE DOMÍNIOS                                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Get admin token
echo "🔑 Obtendo token de admin..."
TOKEN=$(curl -s http://localhost:8006/api/admin/login -X POST -H "Content-Type: application/json" -d '{"email":"admin@dashboard.com","password":"password123"}' | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo "❌ Erro: Não foi possível obter token de admin."
    exit 1
fi
echo "✅ Token obtido!"
echo ""

# Get all domains
echo "🌐 Buscando domínios..."
DOMAINS=$(curl -s "http://localhost:8006/api/admin/domains" -H "Authorization: Bearer $TOKEN" | jq -r '.data[] | "\(.id)|\(.name)"')

if [ -z "$DOMAINS" ]; then
    echo "❌ Nenhum domínio encontrado."
    exit 1
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 COMPARAÇÃO DE MÉTRICAS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Header
printf "%-25s %15s %15s %15s %15s\n" "DOMÍNIO" "TOTAL REQ." "SUCCESS %" "PROVEDORES" "ESTADOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Process each domain
while IFS='|' read -r DOMAIN_ID DOMAIN_NAME; do
    # Get dashboard data for this domain
    DASHBOARD=$(curl -s "http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/dashboard" \
        -H "Authorization: Bearer $TOKEN")
    
    # Extract metrics
    TOTAL_REQUESTS=$(echo "$DASHBOARD" | jq -r '.data.kpis.total_requests // 0')
    SUCCESS_RATE=$(echo "$DASHBOARD" | jq -r '.data.kpis.success_rate // 0')
    UNIQUE_PROVIDERS=$(echo "$DASHBOARD" | jq -r '.data.kpis.unique_providers // 0')
    TOTAL_REPORTS=$(echo "$DASHBOARD" | jq -r '.data.period.total_reports // 0')
    
    # Get unique states from aggregation
    AGGREGATE=$(curl -s "http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/aggregate" \
        -H "Authorization: Bearer $TOKEN")
    UNIQUE_STATES=$(echo "$AGGREGATE" | jq -r '.data.summary.total_unique_states // 0')
    
    # Display row
    printf "%-25s %15s %14s%% %15s %15s\n" \
        "$DOMAIN_NAME" \
        "$TOTAL_REQUESTS" \
        "$SUCCESS_RATE" \
        "$UNIQUE_PROVIDERS" \
        "$UNIQUE_STATES"
    
done <<< "$DOMAINS"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Top providers across all domains
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🏆 TOP 10 PROVEDORES (TODOS OS DOMÍNIOS)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

docker-compose exec -T app php artisan tinker --execute="
\$topProviders = App\Models\ReportProvider::select('provider_id', DB::raw('SUM(total_count) as total'))
    ->groupBy('provider_id')
    ->orderByDesc('total')
    ->limit(10)
    ->with('provider')
    ->get();

\$rank = 1;
foreach (\$topProviders as \$rp) {
    if (\$rp->provider) {
        echo \$rank . '. ' . \$rp->provider->name . ' - ' . number_format(\$rp->total) . ' requisições' . PHP_EOL;
        \$rank++;
    }
}
" 2>/dev/null | grep -v "warning"

echo ""

# Top states across all domains
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗺️  TOP 10 ESTADOS (TODOS OS DOMÍNIOS)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

docker-compose exec -T app php artisan tinker --execute="
\$topStates = App\Models\ReportState::select('state_id', DB::raw('SUM(request_count) as total'))
    ->groupBy('state_id')
    ->orderByDesc('total')
    ->limit(10)
    ->with('state')
    ->get();

\$rank = 1;
foreach (\$topStates as \$rs) {
    if (\$rs->state) {
        echo \$rank . '. ' . \$rs->state->name . ' (' . \$rs->state->code . ') - ' . number_format(\$rs->total) . ' requisições' . PHP_EOL;
        \$rank++;
    }
}
" 2>/dev/null | grep -v "warning"

echo ""

# Technology distribution
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔧 DISTRIBUIÇÃO DE TECNOLOGIAS (GLOBAL)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

docker-compose exec -T app php artisan tinker --execute="
\$technologies = App\Models\ReportProvider::select('technology', DB::raw('SUM(total_count) as total'))
    ->whereNotNull('technology')
    ->where('technology', '!=', '')
    ->groupBy('technology')
    ->orderByDesc('total')
    ->get();

\$totalRequests = \$technologies->sum('total');

foreach (\$technologies as \$tech) {
    \$percentage = \$totalRequests > 0 ? round((\$tech->total / \$totalRequests) * 100, 1) : 0;
    echo \$tech->technology . ': ' . number_format(\$tech->total) . ' requisições (' . \$percentage . '%)' . PHP_EOL;
}
" 2>/dev/null | grep -v "warning"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 INSIGHTS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Use esses dados para:"
echo "   • Identificar domínios com melhor performance"
echo "   • Comparar distribuição geográfica"
echo "   • Analisar preferências de tecnologia"
echo "   • Implementar ranking global de domínios"
echo ""
echo "📊 Para implementar análise cross-domain completa:"
echo "   Ver docs/SISTEMA_RELATORIOS_DESIGN_COMPLETO.md"
echo ""

