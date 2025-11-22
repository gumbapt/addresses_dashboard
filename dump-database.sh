#!/bin/bash

# Script seguro para fazer dump do banco de dados
# Não expõe senhas no histórico de comandos ou processos

set -e  # Sair em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Dump Seguro do Banco de Dados ===${NC}"

# Diretório para salvar os dumps
DUMP_DIR="/home/address3/addresses_dashboard/database/dumps"
mkdir -p "$DUMP_DIR"

# Mudar para o diretório do projeto
cd /home/address3/addresses_dashboard

# Obter configurações do banco de dados via Artisan (mais seguro)
# Usa configurações do Laravel ao invés de ler diretamente o .env
echo -e "${YELLOW}Carregando configurações do banco de dados...${NC}"
DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" 2>/dev/null | grep -v "Tinker" | grep -v "Psy" | xargs)
DB_PORT=$(php artisan tinker --execute="echo config('database.connections.mysql.port');" 2>/dev/null | grep -v "Tinker" | grep -v "Psy" | xargs)
DB_DATABASE=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | grep -v "Tinker" | grep -v "Psy" | xargs)
DB_USERNAME=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | grep -v "Tinker" | grep -v "Psy" | xargs)

# Solicitar senha de forma segura (não aparece no histórico ou processos)
# Sempre pede a senha para garantir máxima segurança
echo -e "${YELLOW}Digite a senha do banco de dados (não será exibida):${NC}"
# Usar stty para desabilitar echo durante a leitura (compatível com sh e bash)
# Salvar configuração do terminal antes de alterar
SAVED_STTY=$(stty -g 2>/dev/null || echo "")
# Função para restaurar terminal em caso de interrupção
restore_terminal() {
    if [ -n "$SAVED_STTY" ]; then
        stty "$SAVED_STTY" 2>/dev/null
    fi
}
trap restore_terminal INT TERM EXIT

if [ -n "$SAVED_STTY" ]; then
    stty -echo 2>/dev/null
    read DB_PASSWORD
    stty "$SAVED_STTY" 2>/dev/null
else
    # Fallback: usar read -s se disponível (bash)
    if [ -n "$BASH_VERSION" ]; then
        read -s DB_PASSWORD 2>/dev/null || read DB_PASSWORD
    else
        # Último recurso: ler normalmente (menos seguro, mas funcional)
        read DB_PASSWORD
    fi
fi
# Remover trap de restore_terminal após ler senha (trap de EXIT será mantido)
trap - INT TERM
echo

# Validar configurações
if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    echo -e "${RED}Erro: Configurações do banco de dados não encontradas!${NC}"
    exit 1
fi

# Definir porta padrão se não especificada
DB_PORT=${DB_PORT:-3306}

# Nome do arquivo com timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DUMP_FILE="$DUMP_DIR/dump_${DB_DATABASE}_${TIMESTAMP}.sql"
DUMP_FILE_COMPRESSED="${DUMP_FILE}.gz"

echo -e "${GREEN}Configurações:${NC}"
echo "  Host: $DB_HOST"
echo "  Porta: $DB_PORT"
echo "  Banco: $DB_DATABASE"
echo "  Usuário: $DB_USERNAME"
echo "  Arquivo: $DUMP_FILE_COMPRESSED"
echo

# Fazer o dump usando mysqldump
# Opções de segurança:
# - --single-transaction: Garante consistência sem bloquear tabelas
# - --routines: Inclui stored procedures e functions
# - --triggers: Inclui triggers
# - --events: Inclui eventos
# - --hex-blob: Codifica BLOBs em hexadecimal
# - --quick: Processa linha por linha (melhor para grandes tabelas)
# - --lock-tables=false: Não trava as tabelas (usando --single-transaction)

echo -e "${YELLOW}Iniciando dump...${NC}"

# Criar arquivo temporário de senha (com permissões restritas)
# Este método evita expor a senha no histórico de comandos ou processos
PASSWD_FILE=$(mktemp)
trap "rm -f '$PASSWD_FILE'; DB_PASSWORD=''; unset DB_PASSWORD" EXIT INT TERM
chmod 600 "$PASSWD_FILE"
echo "[client]" > "$PASSWD_FILE"
echo "password=$DB_PASSWORD" >> "$PASSWD_FILE"

# Executar mysqldump com arquivo de senha
if mysqldump \
    --defaults-file="$PASSWD_FILE" \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    --quick \
    --lock-tables=false \
    --set-gtid-purged=OFF \
    --default-character-set=utf8mb4 \
    --add-drop-database \
    --add-drop-table \
    "$DB_DATABASE" | gzip > "$DUMP_FILE_COMPRESSED"; then
    
    # Arquivo de senha será removido pelo trap EXIT
    # Limpar senha da memória imediatamente
    DB_PASSWORD=""
    unset DB_PASSWORD
    
    # Obter tamanho do arquivo
    FILE_SIZE=$(du -h "$DUMP_FILE_COMPRESSED" | cut -f1)
    
    echo -e "${GREEN}✓ Dump concluído com sucesso!${NC}"
    echo -e "  Arquivo: ${GREEN}$DUMP_FILE_COMPRESSED${NC}"
    echo -e "  Tamanho: ${GREEN}$FILE_SIZE${NC}"
    echo
    
    # Calcular checksum para verificação de integridade
    CHECKSUM=$(md5sum "$DUMP_FILE_COMPRESSED" | cut -d' ' -f1)
    CHECKSUM_FILE="${DUMP_FILE_COMPRESSED}.md5"
    echo "$CHECKSUM  $(basename $DUMP_FILE_COMPRESSED)" > "$CHECKSUM_FILE"
    echo -e "  Checksum MD5: ${GREEN}$CHECKSUM${NC}"
    echo -e "  Checksum salvo em: ${GREEN}$CHECKSUM_FILE${NC}"
    
    # Listar últimos 5 dumps
    echo
    echo -e "${YELLOW}Últimos 5 dumps:${NC}"
    ls -lht "$DUMP_DIR"/*.sql.gz 2>/dev/null | head -5 || echo "  Nenhum dump anterior encontrado"
    
else
    # Arquivo de senha será removido pelo trap EXIT
    # Limpar senha da memória
    DB_PASSWORD=""
    unset DB_PASSWORD
    
    echo -e "${RED}✗ Erro ao fazer dump do banco de dados!${NC}"
    echo -e "${YELLOW}Verifique as credenciais e a conexão com o banco de dados.${NC}"
    exit 1
fi

echo
echo -e "${GREEN}Para restaurar o dump:${NC}"
echo "  gunzip < $DUMP_FILE_COMPRESSED | mysql -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p $DB_DATABASE"
echo
echo -e "${YELLOW}Nota: O dump foi comprimido para economizar espaço.${NC}"

