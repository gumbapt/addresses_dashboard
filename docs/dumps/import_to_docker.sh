#!/bin/bash

# Script para importar dump no banco dash3 do Docker

if [ -z "$1" ]; then
    echo "❌ Uso: $0 <arquivo.sql>"
    echo ""
    echo "Exemplo:"
    echo "  $0 docs/dumps/dash3-20251122_025638.sql"
    exit 1
fi

SQL_FILE="$1"

if [ ! -f "$SQL_FILE" ]; then
    echo "❌ Arquivo não encontrado: ${SQL_FILE}"
    exit 1
fi

# Verificar se o container está rodando
CONTAINER_NAME="dashboard_addresses_db"
if ! docker ps | grep -q "${CONTAINER_NAME}"; then
    echo "❌ Container ${CONTAINER_NAME} não está rodando."
    echo "   Execute: docker-compose up -d db"
    exit 1
fi

echo "🔄 Importando ${SQL_FILE} no banco dash3 do Docker..."
echo ""

# Ler variáveis do .env ou usar defaults
DB_USER="${DB_USERNAME:-dashboard_addresses}"
DB_PASS="${DB_PASSWORD:-password}"
DB_NAME="dash3"

# Copiar arquivo para o container
echo "📦 Copiando arquivo para o container..."
docker cp "${SQL_FILE}" "${CONTAINER_NAME}:/tmp/dump.sql"

# Criar banco se não existir
echo "🗄️  Criando banco de dados se não existir..."
docker exec -i "${CONTAINER_NAME}" mysql \
  -u root \
  -p"${DB_PASS}" \
  -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1

# Importar dump (usar arquivo dentro do container)
echo "📥 Importando dump..."
docker exec "${CONTAINER_NAME}" sh -c "cat /tmp/dump.sql | mysql -u root -p'${DB_PASS}' ${DB_NAME}" 2>&1

# Verificar se importou com sucesso
IMPORT_STATUS=$?

# Limpar arquivo temporário
docker exec "${CONTAINER_NAME}" rm -f /tmp/dump.sql

if [ $IMPORT_STATUS -eq 0 ]; then
    echo ""
    echo "✅ Importação concluída com sucesso!"
    echo "   Banco: ${DB_NAME}"
    echo "   Container: ${CONTAINER_NAME}"
    echo ""
    echo "📝 Para verificar, execute:"
    echo "   docker exec -it ${CONTAINER_NAME} mysql -u root -p${DB_PASS} -e 'SHOW DATABASES;'"
else
    echo ""
    echo "❌ Erro ao importar. Verifique as credenciais e se o container está rodando."
fi
