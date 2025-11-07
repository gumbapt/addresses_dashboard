#!/bin/bash

# Script completo para setup após migrate:fresh --seed

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  🚀 SETUP COMPLETO - PÓS MIGRATE:FRESH --SEED                 ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${BLUE}━━━ PASSO 1: Verificar Estado Atual ━━━${NC}\n"

docker-compose exec -T app php artisan tinker --execute="
echo '🔍 Estado Atual:' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo PHP_EOL;

\$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
echo '👤 Admin: ' . (\$admin ? '✅ Existe' : '❌ Não existe') . PHP_EOL;
if (\$admin) {
    echo '   Super Admin: ' . (\$admin->is_super_admin ? 'SIM ✅' : 'NÃO ❌') . PHP_EOL;
    echo '   Roles: ' . \$admin->roles->count() . PHP_EOL;
}
echo PHP_EOL;

\$domains = App\Models\Domain::all();
echo '🌐 Domínios: ' . \$domains->count() . PHP_EOL;
foreach (\$domains as \$d) {
    echo '   • ' . \$d->name . ' (ID: ' . \$d->id . ', Active: ' . (\$d->is_active ? 'Sim' : 'Não') . ')' . PHP_EOL;
}
echo PHP_EOL;

\$reports = App\Models\Report::count();
echo '📊 Reports: ' . \$reports . PHP_EOL;
echo PHP_EOL;
"

echo ""
echo -e "${BLUE}━━━ PASSO 2: Criar Domínios (se necessário) ━━━${NC}\n"

docker-compose exec app php artisan db:seed --class=DomainSeeder

echo ""
echo -e "${BLUE}━━━ PASSO 3: Configurar Permissões de Domínio para Admin ━━━${NC}\n"

docker-compose exec -T app php artisan tinker --execute="
echo '🔑 Configurando permissões de domínio...' . PHP_EOL;
echo PHP_EOL;

\$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
if (!\$admin) {
    echo '❌ Admin não encontrado!' . PHP_EOL;
    exit(1);
}

// Super Admins têm acesso a TODOS os domínios automaticamente via getAccessibleDomains()
if (\$admin->is_super_admin) {
    echo '✅ Admin é SUPER ADMIN - tem acesso automático a todos os domínios!' . PHP_EOL;
} else {
    echo '⚠️  Admin NÃO é super admin. Atribuindo role...' . PHP_EOL;
    \$superRole = App\Models\AdminRole::where('name', 'Super Admin')->first();
    if (\$superRole) {
        \$admin->roles()->syncWithoutDetaching([\$superRole->id]);
        echo '✅ Role Super Admin atribuída!' . PHP_EOL;
    }
}
echo PHP_EOL;

// Verificar acesso
\$accessibleDomains = \$admin->getAccessibleDomains();
echo '📊 Domínios acessíveis: ' . count(\$accessibleDomains) . PHP_EOL;
foreach (\$accessibleDomains as \$domain) {
    echo '   • ' . \$domain->name . ' (ID: ' . \$domain->id . ')' . PHP_EOL;
}
echo PHP_EOL;
"

echo ""
echo -e "${BLUE}━━━ PASSO 4: Popular Relatórios (Real + Fictícios) ━━━${NC}\n"

echo -e "${YELLOW}Escolha uma opção:${NC}"
echo "  1. Teste rápido (5 arquivos por domínio)"
echo "  2. Período específico (ex: junho 2025)"
echo "  3. Todos os arquivos"
echo ""
read -p "Opção [1-3]: " OPTION

case $OPTION in
    1)
        echo ""
        echo -e "${CYAN}📊 Executando seed com limite de 5 arquivos...${NC}"
        echo ""
        docker-compose exec app php artisan reports:seed-all-domains --limit=5
        ;;
    2)
        echo ""
        read -p "Data inicial (YYYY-MM-DD): " DATE_FROM
        read -p "Data final (YYYY-MM-DD): " DATE_TO
        echo ""
        echo -e "${CYAN}📊 Executando seed de $DATE_FROM até $DATE_TO...${NC}"
        echo ""
        docker-compose exec app php artisan reports:seed-all-domains --date-from=$DATE_FROM --date-to=$DATE_TO
        ;;
    3)
        echo ""
        echo -e "${CYAN}📊 Executando seed completo (TODOS os arquivos)...${NC}"
        echo -e "${YELLOW}⚠️  Isso pode demorar alguns minutos!${NC}"
        echo ""
        docker-compose exec app php artisan reports:seed-all-domains
        ;;
    *)
        echo ""
        echo -e "${YELLOW}⏭️  Pulando seed de relatórios...${NC}"
        ;;
esac

echo ""
echo -e "${BLUE}━━━ PASSO 5: Aguardar Processamento ━━━${NC}\n"

echo -e "${CYAN}⏳ Aguardando processamento dos jobs... (máx 30 segundos)${NC}"
echo ""

for i in {1..10}; do
    sleep 3
    PROCESSED=$(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Report::where('status', 'processed')->count();" 2>/dev/null | tail -n 1)
    TOTAL=$(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Report::count();" 2>/dev/null | tail -n 1)
    echo "   Processados: $PROCESSED / $TOTAL"
    
    if [ "$PROCESSED" = "$TOTAL" ] && [ "$PROCESSED" != "0" ]; then
        echo ""
        echo -e "${GREEN}✅ Todos os relatórios foram processados!${NC}"
        break
    fi
done

echo ""
echo -e "${BLUE}━━━ PASSO 6: Resumo Final ━━━${NC}\n"

docker-compose exec -T app php artisan tinker --execute="
echo '╔════════════════════════════════════════════════════════════════╗' . PHP_EOL;
echo '║  📊 RESUMO FINAL                                               ║' . PHP_EOL;
echo '╚════════════════════════════════════════════════════════════════╝' . PHP_EOL;
echo PHP_EOL;

\$admin = App\Models\Admin::where('email', 'admin@dashboard.com')->first();
\$domains = App\Models\Domain::where('is_active', true)->get();
\$totalReports = App\Models\Report::count();
\$processedReports = App\Models\Report::where('status', 'processed')->count();

echo '👤 Admin: ' . \$admin->email . PHP_EOL;
echo '   Super Admin: ' . (\$admin->is_super_admin ? 'SIM ✅' : 'NÃO ❌') . PHP_EOL;
echo '   Domínios acessíveis: ' . count(\$admin->getAccessibleDomains()) . PHP_EOL;
echo PHP_EOL;

echo '🌐 Domínios Ativos: ' . \$domains->count() . PHP_EOL;
foreach (\$domains as \$domain) {
    \$count = \$domain->reports()->count();
    \$processed = \$domain->reports()->where('status', 'processed')->count();
    \$isReal = \$domain->name === 'zip.50g.io';
    \$badge = \$isReal ? '📊 REAL' : '🎲 FICTÍCIO';
    echo '   • ' . \$domain->name . ' - ' . \$count . ' reports (' . \$processed . ' processados) ' . \$badge . PHP_EOL;
}
echo PHP_EOL;

echo '📊 Total de Reports: ' . \$totalReports . PHP_EOL;
echo '   Processados: ' . \$processedReports . ' (' . round((\$processedReports/max(\$totalReports,1))*100, 1) . '%)' . PHP_EOL;
echo '   Pendentes: ' . (\$totalReports - \$processedReports) . PHP_EOL;
echo PHP_EOL;

echo '📈 Dados Geográficos:' . PHP_EOL;
echo '   Estados: ' . App\Models\State::count() . PHP_EOL;
echo '   Cidades: ' . App\Models\City::count() . PHP_EOL;
echo '   CEPs: ' . App\Models\ZipCode::count() . PHP_EOL;
echo '   Provedores: ' . App\Models\Provider::count() . PHP_EOL;
echo PHP_EOL;
"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✅ SETUP COMPLETO!                                            ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${BLUE}━━━ Próximos Passos ━━━${NC}\n"

echo -e "${YELLOW}🔐 1. Fazer Login:${NC}"
echo -e "curl -X POST http://localhost:8007/api/admin/login \\"
echo -e "  -H \"Content-Type: application/json\" \\"
echo -e "  -d '{\"email\":\"admin@dashboard.com\",\"password\":\"password123\"}' | jq '.token'"
echo ""

echo -e "${YELLOW}📊 2. Listar Domínios:${NC}"
echo -e "curl http://localhost:8007/api/admin/domains \\"
echo -e "  -H \"Authorization: Bearer \$TOKEN\" | jq '.'"
echo ""

echo -e "${YELLOW}📈 3. Ver Dashboard do zip.50g.io:${NC}"
echo -e "curl http://localhost:8007/api/admin/reports/domain/1/dashboard \\"
echo -e "  -H \"Authorization: Bearer \$TOKEN\" | jq '.data.kpis'"
echo ""

echo -e "${YELLOW}🏆 4. Ver Ranking Global:${NC}"
echo -e "curl http://localhost:8007/api/admin/reports/global/domain-ranking \\"
echo -e "  -H \"Authorization: Bearer \$TOKEN\" | jq '.'"
echo ""

echo -e "${YELLOW}⚖️  5. Comparar Domínios:${NC}"
echo -e "curl http://localhost:8007/api/admin/reports/global/comparison?domain_ids[]=1&domain_ids[]=2 \\"
echo -e "  -H \"Authorization: Bearer \$TOKEN\" | jq '.'"
echo ""

echo -e "${CYAN}💡 Dica: Salve o token em uma variável: export TOKEN=\$(curl -s ... | jq -r '.token')${NC}"
echo ""

