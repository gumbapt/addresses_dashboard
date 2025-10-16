#!/bin/bash

# Script de debug completo do fluxo de relatórios
# Submete o newdata.json e acompanha todo o processamento

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  🔍 DEBUG COMPLETO DO FLUXO DE RELATÓRIOS                     ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}\n"

# 1. Verificar estado inicial do banco
echo -e "${BLUE}━━━ PASSO 1: ESTADO INICIAL DO BANCO ━━━${NC}\n"

echo -e "${YELLOW}📊 Contagem de registros ANTES da submissão:${NC}"
docker-compose exec -T app php artisan tinker --execute="
echo 'Reports: ' . App\Models\Report::count() . PHP_EOL;
echo 'Report Summaries: ' . App\Models\ReportSummary::count() . PHP_EOL;
echo 'Report Providers: ' . App\Models\ReportProvider::count() . PHP_EOL;
echo 'Report States: ' . App\Models\ReportState::count() . PHP_EOL;
echo 'Report Cities: ' . App\Models\ReportCity::count() . PHP_EOL;
echo 'Report ZipCodes: ' . App\Models\ReportZipCode::count() . PHP_EOL;
echo 'States: ' . App\Models\State::count() . PHP_EOL;
echo 'Cities: ' . App\Models\City::count() . PHP_EOL;
echo 'ZipCodes: ' . App\Models\ZipCode::count() . PHP_EOL;
echo 'Providers: ' . App\Models\Provider::count() . PHP_EOL;
" 2>/dev/null | tail -10

echo ""

# 2. Limpar dados anteriores (opcional)
read -p "$(echo -e ${YELLOW}Deseja limpar dados de relatórios anteriores? [y/N]: ${NC})" -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "\n${YELLOW}🧹 Limpando dados anteriores...${NC}"
    docker-compose exec -T app php artisan tinker --execute="
    App\Models\ReportZipCode::truncate();
    App\Models\ReportCity::truncate();
    App\Models\ReportState::truncate();
    App\Models\ReportProvider::truncate();
    App\Models\ReportSummary::truncate();
    App\Models\Report::truncate();
    echo '✅ Dados de relatórios limpos' . PHP_EOL;
    " 2>/dev/null | tail -1
    echo ""
fi

# 3. Verificar domínio
echo -e "${BLUE}━━━ PASSO 2: VERIFICAR DOMÍNIO ━━━${NC}\n"

DOMAIN_INFO=$(docker-compose exec -T app php artisan tinker --execute="
    \$domain = App\Models\Domain::where('name', 'zip.50g.io')->first();
    if (!\$domain) {
        echo 'NOT_FOUND';
    } else {
        echo \$domain->id . '|' . \$domain->name . '|' . \$domain->api_key . '|' . \$domain->is_active;
    }
" 2>/dev/null | tail -1)

if [ "$DOMAIN_INFO" = "NOT_FOUND" ]; then
    echo -e "${YELLOW}⚠️  Domínio 'zip.50g.io' não encontrado${NC}"
    echo -e "${BLUE}📝 Criando domínio...${NC}\n"
    
    docker-compose exec -T app php artisan report:submit-test --create-domain --file=docs/newdata.json 2>&1 | head -15
    
    # Buscar novamente
    DOMAIN_INFO=$(docker-compose exec -T app php artisan tinker --execute="
        \$domain = App\Models\Domain::where('name', 'zip.50g.io')->first();
        echo \$domain->id . '|' . \$domain->name . '|' . \$domain->api_key . '|' . \$domain->is_active;
    " 2>/dev/null | tail -1)
fi

DOMAIN_ID=$(echo "$DOMAIN_INFO" | cut -d'|' -f1)
DOMAIN_NAME=$(echo "$DOMAIN_INFO" | cut -d'|' -f2)
API_KEY=$(echo "$DOMAIN_INFO" | cut -d'|' -f3)
IS_ACTIVE=$(echo "$DOMAIN_INFO" | cut -d'|' -f4)

echo -e "${GREEN}✅ Domínio encontrado:${NC}"
echo -e "   ID: $DOMAIN_ID"
echo -e "   Nome: $DOMAIN_NAME"
echo -e "   Ativo: $IS_ACTIVE"
echo -e "   API Key: ${API_KEY:0:30}..."
echo ""

# 4. Submeter relatório
echo -e "${BLUE}━━━ PASSO 3: SUBMISSÃO DO RELATÓRIO ━━━${NC}\n"

echo -e "${YELLOW}📡 Submetendo docs/newdata.json...${NC}\n"

RESPONSE=$(curl -X POST "http://localhost:8006/api/reports/submit" \
    -H "X-API-Key: $API_KEY" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d @"docs/newdata.json" \
    -w "\n%{http_code}" \
    -s 2>&1)

HTTP_BODY=$(echo "$RESPONSE" | sed '$d')
HTTP_CODE=$(echo "$RESPONSE" | tail -1)

echo -e "${BLUE}📊 HTTP Status: $HTTP_CODE${NC}\n"

if [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
    echo -e "${GREEN}✅ Relatório submetido com sucesso!${NC}\n"
    
    REPORT_ID=$(echo "$HTTP_BODY" | jq -r '.data.id // empty' 2>/dev/null)
    REPORT_DATE=$(echo "$HTTP_BODY" | jq -r '.data.report_date // empty' 2>/dev/null)
    STATUS=$(echo "$HTTP_BODY" | jq -r '.data.status // empty' 2>/dev/null)
    
    echo -e "${GREEN}🎉 Report ID: $REPORT_ID${NC}"
    echo -e "${GREEN}📅 Report Date: $REPORT_DATE${NC}"
    echo -e "${GREEN}📊 Status: $STATUS${NC}\n"
    
    # 5. Verificar job na queue
    echo -e "${BLUE}━━━ PASSO 4: PROCESSAMENTO DO JOB ━━━${NC}\n"
    
    echo -e "${YELLOW}⏳ Aguardando processamento do job...${NC}"
    sleep 2
    
    # Verificar se o job já processou
    CURRENT_STATUS=$(docker-compose exec -T app php artisan tinker --execute="
        \$report = App\Models\Report::find($REPORT_ID);
        echo \$report ? \$report->status : 'NOT_FOUND';
    " 2>/dev/null | tail -1)
    
    echo -e "${BLUE}📊 Status atual do relatório: $CURRENT_STATUS${NC}\n"
    
    if [ "$CURRENT_STATUS" = "processed" ] || [ "$CURRENT_STATUS" = "processing" ]; then
        echo -e "${GREEN}✅ Relatório sendo processado ou já processado${NC}\n"
    else
        echo -e "${YELLOW}⚠️  Status: $CURRENT_STATUS${NC}"
        echo -e "${YELLOW}💡 Processamento pode estar pendente na queue${NC}\n"
    fi
    
    # 6. Ver dados processados
    echo -e "${BLUE}━━━ PASSO 5: DADOS PROCESSADOS ━━━${NC}\n"
    
    echo -e "${YELLOW}📊 Contagem de registros APÓS submissão:${NC}"
    docker-compose exec -T app php artisan tinker --execute="
    echo 'Reports: ' . App\Models\Report::count() . PHP_EOL;
    echo 'Report Summaries: ' . App\Models\ReportSummary::count() . PHP_EOL;
    echo 'Report Providers: ' . App\Models\ReportProvider::count() . PHP_EOL;
    echo 'Report States: ' . App\Models\ReportState::count() . PHP_EOL;
    echo 'Report Cities: ' . App\Models\ReportCity::count() . PHP_EOL;
    echo 'Report ZipCodes: ' . App\Models\ReportZipCode::count() . PHP_EOL;
    echo '---' . PHP_EOL;
    echo 'States (total): ' . App\Models\State::count() . PHP_EOL;
    echo 'Cities (total): ' . App\Models\City::count() . PHP_EOL;
    echo 'ZipCodes (total): ' . App\Models\ZipCode::count() . PHP_EOL;
    echo 'Providers (total): ' . App\Models\Provider::count() . PHP_EOL;
    " 2>/dev/null | tail -11
    
    echo ""
    
    # 7. Ver detalhes do relatório
    echo -e "${BLUE}━━━ PASSO 6: DETALHES DO RELATÓRIO #$REPORT_ID ━━━${NC}\n"
    
    docker-compose exec -T app php artisan tinker --execute="
    \$report = App\Models\Report::with(['summary', 'domain'])->find($REPORT_ID);
    if (\$report) {
        echo '📄 Domínio: ' . \$report->domain->name . PHP_EOL;
        echo '📅 Data: ' . \$report->report_date . PHP_EOL;
        echo '📊 Status: ' . \$report->status . PHP_EOL;
        echo '📦 Versão: ' . \$report->data_version . PHP_EOL;
        echo '⏰ Criado em: ' . \$report->created_at . PHP_EOL;
        echo '🔄 Atualizado em: ' . \$report->updated_at . PHP_EOL;
        
        if (\$report->summary) {
            echo '---' . PHP_EOL;
            echo '📈 RESUMO:' . PHP_EOL;
            echo '  Total Requests: ' . \$report->summary->total_requests . PHP_EOL;
            echo '  Success Rate: ' . \$report->summary->success_rate . '%' . PHP_EOL;
            echo '  Failed Requests: ' . \$report->summary->failed_requests . PHP_EOL;
        }
    }
    " 2>/dev/null | tail -15
    
    echo ""
    
    # 8. Testar API de listagem
    echo -e "${BLUE}━━━ PASSO 7: TESTAR API DE LISTAGEM ━━━${NC}\n"
    
    echo -e "${YELLOW}🔑 Fazendo login como admin...${NC}"
    TOKEN=$(curl -s http://localhost:8006/api/admin/login \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"email":"admin@dashboard.com","password":"password123"}' \
        | jq -r '.token' 2>/dev/null)
    
    if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
        echo -e "${GREEN}✅ Login bem-sucedido${NC}\n"
        
        echo -e "${YELLOW}📋 Listando relatórios via API:${NC}"
        curl -s "http://localhost:8006/api/admin/reports?per_page=10" \
            -H "Authorization: Bearer $TOKEN" \
            -H "Accept: application/json" \
            | jq '{success, total: .meta.total, reports: [.data[] | {id, domain: .domain_id, date: .report_date, status}]}'
        
        echo ""
    else
        echo -e "${RED}❌ Erro no login${NC}\n"
    fi
    
    # 9. Resumo final
    echo -e "${BLUE}━━━ PASSO 8: RESUMO FINAL ━━━${NC}\n"
    
    echo -e "${GREEN}✅ PROCESSO COMPLETO!${NC}\n"
    echo -e "${CYAN}📊 Próximos passos:${NC}"
    echo -e "   1. Processar relatório manualmente (se ainda pending):"
    echo -e "      ${YELLOW}docker-compose exec app php artisan queue:work --once${NC}"
    echo -e ""
    echo -e "   2. Ver logs de processamento:"
    echo -e "      ${YELLOW}docker-compose logs -f app | grep ProcessReport${NC}"
    echo -e ""
    echo -e "   3. Ver relatório completo via API:"
    echo -e "      ${YELLOW}curl -s http://localhost:8006/api/admin/reports/$REPORT_ID \\${NC}"
    echo -e "      ${YELLOW}  -H \"Authorization: Bearer \$TOKEN\" | jq '.'${NC}"
    echo -e ""
    
    exit 0
else
    echo -e "${RED}❌ Falha na submissão!${NC}\n"
    echo -e "${YELLOW}Response:${NC}"
    echo "$HTTP_BODY" | jq '.' 2>/dev/null || echo "$HTTP_BODY"
    exit 1
fi

