#!/bin/bash

# Script para testar o endpoint de ranking global de domínios

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  🏆 TESTANDO RANKING GLOBAL DE DOMÍNIOS                       ║"
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

# Test 1: Default ranking (by score)
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣  RANKING POR SCORE (padrão)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking[] | {
    rank: .rank,
    domain: .domain.name,
    requests: .metrics.total_requests,
    success: .metrics.success_rate,
    speed: .metrics.avg_speed,
    score: .metrics.score
  }'

echo ""

# Test 2: Ranking by volume
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2️⃣  RANKING POR VOLUME"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?sort_by=volume" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking[] | {
    rank: .rank,
    domain: .domain.name,
    total_requests: .metrics.total_requests
  }'

echo ""

# Test 3: Ranking by success rate
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3️⃣  RANKING POR TAXA DE SUCESSO"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?sort_by=success" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking[] | {
    rank: .rank,
    domain: .domain.name,
    success_rate: .metrics.success_rate
  }'

echo ""

# Test 4: Ranking by speed
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4️⃣  RANKING POR VELOCIDADE MÉDIA"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking?sort_by=speed" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.ranking[] | {
    rank: .rank,
    domain: .domain.name,
    avg_speed: .metrics.avg_speed
  }'

echo ""

# Test 5: Full ranking details
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5️⃣  RANKING COMPLETO (todas as métricas)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

curl -s "http://localhost:8006/api/admin/reports/global/domain-ranking" \
  -H "Authorization: Bearer $TOKEN" | jq '.'

echo ""
echo "🎉 Testes de ranking concluídos!"
echo ""

