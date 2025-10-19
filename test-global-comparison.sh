#!/bin/bash

# Script para testar o endpoint de comparação entre domínios

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  🔄 TESTANDO COMPARAÇÃO ENTRE DOMÍNIOS                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Get admin token
echo "🔑 Obtendo token de admin..."
TOKEN=$(curl -s http://localhost:8006/api/admin/login -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dashboard.com","password":"password123"}' | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo "❌ Erro: Não foi possível obter token de admin."
    exit 1
fi
echo "✅ Token obtido!"
echo ""

# Test 1: Compare 2 domains
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣  COMPARAR 2 DOMÍNIOS (smarterhome.ai vs zip.50g.io)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=2,1" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.domains[] | {
    domain: .domain.name,
    requests: .metrics.total_requests,
    success: .metrics.success_rate,
    speed: .metrics.avg_speed,
    comparison: .comparison
  }'

echo ""

# Test 2: Compare all 4 domains
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2️⃣  COMPARAR TODOS OS 4 DOMÍNIOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2,3,4" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.domains[] | {
    domain: .domain.name,
    requests: .metrics.total_requests,
    success: .metrics.success_rate,
    vs_base: .comparison
  }'

echo ""

# Test 3: Compare with geographic details
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3️⃣  COMPARAR COM DETALHES GEOGRÁFICOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2&metric=geographic" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.domains[] | {
    domain: .domain.name,
    top_states: .metrics.top_states
  }'

echo ""

# Test 4: Compare with provider details
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4️⃣  COMPARAR COM DETALHES DE PROVEDORES"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2&metric=providers" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.domains[] | {
    domain: .domain.name,
    top_providers: .metrics.top_providers
  }'

echo ""

# Test 5: Full comparison
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5️⃣  COMPARAÇÃO COMPLETA (todas as métricas)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/comparison?domains=1,2" \
  -H "Authorization: Bearer $TOKEN" | jq '.'

echo ""
echo "🎉 Testes de comparação concluídos!"
echo ""

