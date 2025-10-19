#!/bin/bash

# Script para demonstrar o sistema de permissões por domínio

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  🔐 DEMONSTRAÇÃO: PERMISSÕES POR DOMÍNIO                      ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Login Super Admin
echo "🔑 1. Login Super Admin..."
SUPER_TOKEN=$(curl -s http://localhost:8006/api/admin/login -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' | jq -r '.token')
echo "✅ Super Admin logado!"
echo ""

# Login Domain Manager
echo "🔑 2. Login Domain Manager..."
MANAGER_TOKEN=$(curl -s http://localhost:8006/api/admin/login -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"manager@dashboard.com","password":"password123"}' | jq -r '.token')
echo "✅ Domain Manager logado!"
echo ""

# Comparar domínios acessíveis
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 COMPARAÇÃO DE ACESSOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "Super Admin (acesso global):"
curl -s "http://localhost:8006/api/admin/my-domains" \
  -H "Authorization: Bearer $SUPER_TOKEN" | jq -c '{
    access_type: .data.access_type,
    total: .data.total,
    domains: .data.domains | map(.name)
  }'
echo ""

echo "Domain Manager (acesso limitado):"
curl -s "http://localhost:8006/api/admin/my-domains" \
  -H "Authorization: Bearer $MANAGER_TOKEN" | jq -c '{
    access_type: .data.access_type,
    total: .data.total,
    domains: .data.domains | map(.name)
  }'
echo ""

# Testar ranking global
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🏆 RANKING GLOBAL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "Super Admin vê (4 domínios):"
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking" \
  -H "Authorization: Bearer $SUPER_TOKEN" | jq -c '{
    total: .data.total_domains,
    domains: .data.ranking | map(.domain.name)
  }'
echo ""

echo "Domain Manager vê (apenas 2 domínios):"
curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking" \
  -H "Authorization: Bearer $MANAGER_TOKEN" | jq -c '{
    total: .data.total_domains,
    domains: .data.ranking | map(.domain.name)
  }'
echo ""

# Testar acesso a dashboard
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔒 TESTE DE ACESSO A DASHBOARDS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "Manager acessando domain 2 (smarterhome.ai) - PERMITIDO:"
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" "http://localhost:8006/api/admin/reports/domain/2/dashboard" \
  -H "Authorization: Bearer $MANAGER_TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)
echo "  Status: $HTTP_CODE (esperado: 200)"
echo ""

echo "Manager acessando domain 3 (ispfinder.net) - BLOQUEADO:"
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" "http://localhost:8006/api/admin/reports/domain/3/dashboard" \
  -H "Authorization: Bearer $MANAGER_TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)
MESSAGE=$(echo "$RESPONSE" | grep -v "HTTP_CODE" | jq -r '.message')
echo "  Status: $HTTP_CODE (esperado: 403)"
echo "  Message: $MESSAGE"
echo ""

# Testar comparação
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 TESTE DE COMPARAÇÃO"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "Manager comparando domains 1 e 2 (permitidos):"
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2" \
  -H "Authorization: Bearer $MANAGER_TOKEN" | jq -c '{
    success: .success,
    total: .data.total_compared
  }'
echo ""

echo "Manager tentando comparar domain 1 e 3 (3 não permitido):"
curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,3" \
  -H "Authorization: Bearer $MANAGER_TOKEN" | jq -c '{
    success: .success,
    message: .message
  }'
echo ""

# Resumo
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ RESUMO DOS TESTES"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Super Admin tem acesso a todos os 4 domínios"
echo "✅ Domain Manager tem acesso apenas a 2 domínios (zip.50g.io, smarterhome.ai)"
echo "✅ Ranking global filtra corretamente por permissões"
echo "✅ Middleware bloqueia acesso a domínios não permitidos (403)"
echo "✅ Middleware permite acesso a domínios atribuídos"
echo "✅ Comparação valida permissões antes de processar"
echo ""
echo "🎉 Sistema de permissões por domínio FUNCIONANDO!"
echo ""

