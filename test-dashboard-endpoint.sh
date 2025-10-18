#!/bin/bash

# Script para testar o endpoint de dashboard completo

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  📊 TESTE DO DASHBOARD COMPLETO                               ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}\n"

# 1. Login
echo -e "${BLUE}━━━ PASSO 1: LOGIN DE ADMIN ━━━${NC}\n"

TOKEN=$(curl -s http://localhost:8006/api/admin/login \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@dashboard.com","password":"password123"}' \
    | jq -r '.token' 2>/dev/null)

if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    echo -e "${GREEN}✅ Login bem-sucedido${NC}\n"
else
    echo -e "${RED}❌ Erro no login${NC}\n"
    exit 1
fi

# 2. Testar dashboard completo
DOMAIN_ID=1

echo -e "${BLUE}━━━ PASSO 2: DASHBOARD COMPLETO (Domain #${DOMAIN_ID}) ━━━${NC}\n"

RESPONSE=$(curl -s "http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/dashboard" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")

SUCCESS=$(echo "$RESPONSE" | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ Dashboard carregado com sucesso!${NC}\n"
    
    # KPIs
    echo -e "${YELLOW}📊 KPIs PRINCIPAIS:${NC}"
    echo "$RESPONSE" | jq '.data.kpis'
    echo ""
    
    # Distribuição de Provedores
    echo -e "${YELLOW}📡 DISTRIBUIÇÃO DE PROVEDORES:${NC}"
    echo "$RESPONSE" | jq '.data.provider_distribution[:5] | .[] | {name, technology, total_count, percentage}'
    echo ""
    
    # Estados Mais Solicitados
    echo -e "${YELLOW}🗺️  ESTADOS MAIS SOLICITADOS:${NC}"
    echo "$RESPONSE" | jq '.data.top_states[:5] | .[] | {code, name, total_requests}'
    echo ""
    
    # Distribuição por Horário
    echo -e "${YELLOW}⏰ DISTRIBUIÇÃO POR HORÁRIO (primeiras 12h):${NC}"
    echo "$RESPONSE" | jq '.data.hourly_distribution[:12] | .[] | {hour, count, normalized}'
    echo ""
    
    # Velocidade por Estado
    echo -e "${YELLOW}🚀 VELOCIDADE MÉDIA POR ESTADO:${NC}"
    echo "$RESPONSE" | jq '.data.speed_by_state[:5] | .[] | {name, avg_speed, sample_count}'
    echo ""
    
    # Distribuição de Tecnologias
    echo -e "${YELLOW}🔧 DISTRIBUIÇÃO DE TECNOLOGIAS:${NC}"
    echo "$RESPONSE" | jq '.data.technology_distribution | .[] | {technology, total_count, percentage, unique_providers}'
    echo ""
    
    # Taxa de Exclusão por Provedor
    echo -e "${YELLOW}❌ TAXA DE EXCLUSÃO POR PROVEDOR:${NC}"
    echo "$RESPONSE" | jq '.data.exclusion_by_provider[:5] | .[] | {provider_name, exclusion_count}'
    echo ""
    
    echo -e "${GREEN}✅ DASHBOARD COMPLETO!${NC}\n"
    
    # 3. Comparação com dados do WordPress
    echo -e "${BLUE}━━━ PASSO 3: COMPARAÇÃO COM WORDPRESS ━━━${NC}\n"
    
    echo -e "${YELLOW}📊 Dados do WordPress vs API:${NC}"
    echo ""
    echo -e "${CYAN}KPIs:${NC}"
    echo "  WordPress: 1,502 requests | 85% success | 38 daily avg | 8 providers"
    echo "  API:        $(echo "$RESPONSE" | jq -r '.data.kpis.total_requests') requests | $(echo "$RESPONSE" | jq -r '.data.kpis.success_rate')% success | $(echo "$RESPONSE" | jq -r '.data.kpis.daily_average') daily avg | $(echo "$RESPONSE" | jq -r '.data.kpis.unique_providers') providers"
    echo ""
    
    echo -e "${CYAN}Top Providers:${NC}"
    echo "  WordPress: Earthlink (maior), AT&T, Spectrum, Xfinity..."
    echo "  API:       $(echo "$RESPONSE" | jq -r '.data.provider_distribution[0].name') ($(echo "$RESPONSE" | jq -r '.data.provider_distribution[0].percentage')%), $(echo "$RESPONSE" | jq -r '.data.provider_distribution[1].name'), $(echo "$RESPONSE" | jq -r '.data.provider_distribution[2].name')..."
    echo ""
    
    echo -e "${CYAN}Top Estados:${NC}"
    echo "  WordPress: CA (~230), TX (~180), NY (~160)..."
    echo "  API:       $(echo "$RESPONSE" | jq -r '.data.top_states[0].code') ($(echo "$RESPONSE" | jq -r '.data.top_states[0].total_requests')), $(echo "$RESPONSE" | jq -r '.data.top_states[1].code') ($(echo "$RESPONSE" | jq -r '.data.top_states[1].total_requests')), $(echo "$RESPONSE" | jq -r '.data.top_states[2].code') ($(echo "$RESPONSE" | jq -r '.data.top_states[2].total_requests'))..."
    echo ""
    
    # 4. Comandos úteis
    echo -e "${BLUE}━━━ COMANDOS ÚTEIS ━━━${NC}\n"
    echo -e "${YELLOW}Ver dados completos:${NC}"
    echo -e "curl -s \"http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/dashboard\" \\"
    echo -e "  -H \"Authorization: Bearer \$TOKEN\" | jq '.'"
    echo ""
    
    echo -e "${YELLOW}Salvar em arquivo:${NC}"
    echo -e "curl -s \"http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/dashboard\" \\"
    echo -e "  -H \"Authorization: Bearer \$TOKEN\" > dashboard_data.json"
    echo ""
    
    echo -e "${YELLOW}Ver apenas KPIs:${NC}"
    echo -e "curl -s \"http://localhost:8006/api/admin/reports/domain/${DOMAIN_ID}/dashboard\" \\"
    echo -e "  -H \"Authorization: Bearer \$TOKEN\" | jq '.data.kpis'"
    echo ""
    
else
    echo -e "${RED}❌ Erro ao carregar dashboard${NC}\n"
    echo "$RESPONSE" | jq '.'
    exit 1
fi

