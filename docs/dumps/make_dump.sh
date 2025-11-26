#!/bin/bash

# Script para fazer dump do banco dash3 externo

HOST="127.0.0.1"
PORT="36949"
USER="dash3"
DATABASE="dash3"
OUTPUT_DIR="docs/dumps"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
OUTPUT_FILE="${OUTPUT_DIR}/dash3-${TIMESTAMP}.sql"

echo "🔄 Fazendo dump do banco ${DATABASE}..."
echo "   Host: ${HOST}:${PORT}"
echo "   Usuário: ${USER}"
echo ""

# Criar diretório se não existir
mkdir -p "${OUTPUT_DIR}"

# Pedir senha de forma segura
echo "🔐 Digite a senha do banco (não será exibida):"
read -s PASSWORD
echo ""

# Fazer dump com senha
/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysqldump \
  --protocol=TCP \
  --skip-lock-tables \
  --routines \
  --add-drop-table \
  --disable-keys \
  --extended-insert \
  -u "${USER}" \
  -p"${PASSWORD}" \
  --host="${HOST}" \
  --port="${PORT}" \
  "${DATABASE}" > "${OUTPUT_FILE}" 2>&1

if [ $? -eq 0 ]; then
    FILE_SIZE=$(du -h "${OUTPUT_FILE}" | cut -f1)
    echo "✅ Dump criado com sucesso!"
    echo "   Arquivo: ${OUTPUT_FILE}"
    echo "   Tamanho: ${FILE_SIZE}"
    echo ""
    echo "📝 Para importar no Docker, execute:"
    echo "   ./docs/dumps/import_to_docker.sh ${OUTPUT_FILE}"
else
    echo "❌ Erro ao criar dump."
    echo "   Verifique a senha e se o banco está acessível."
    echo ""
    echo "💡 Para tentar manualmente:"
    echo "   /opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysqldump \\"
    echo "     --protocol=TCP \\"
    echo "     -u ${USER} -p \\"
    echo "     --host=${HOST} \\"
    echo "     --port=${PORT} \\"
    echo "     ${DATABASE} > ${OUTPUT_FILE}"
fi
