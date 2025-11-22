# Scripts de Dump e Importação

## 📋 Onde colocar o arquivo .sql

Coloque o arquivo `.sql` na pasta `docs/dumps/` (já criada).

## 🔄 Passo a Passo

### 1. Fazer Dump do Banco Externo

Execute o dump do banco externo (precisa de senha):

```bash
/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysqldump \
  --protocol=TCP \
  --skip-lock-tables \
  --routines \
  --add-drop-table \
  --disable-keys \
  --extended-insert \
  -u dash3 -p \
  --host=127.0.0.1 \
  --port=36949 \
  dash3 > docs/dumps/dash3-$(date +%Y%m%d_%H%M%S).sql
```

**Nota:** O `-p` (sem espaço) vai pedir a senha interativamente.

### 2. Verificar se o Docker está rodando

```bash
docker-compose ps
```

Se o container `dashboard_addresses_db` não estiver rodando:

```bash
docker-compose up -d db
```

### 3. Importar no Banco dash3 do Docker

Após criar o dump, importe no banco `dash3` do Docker:

```bash
./docs/dumps/import_to_docker.sh docs/dumps/dash3-YYYYMMDD_HHMMSS.sql
```

**Exemplo:**
```bash
./docs/dumps/import_to_docker.sh docs/dumps/dash3-20251122_025638.sql
```

### 4. Verificar Importação

Para verificar se o banco foi criado e importado:

```bash
# Listar bancos
docker exec -it dashboard_addresses_db mysql -u root -ppassword -e "SHOW DATABASES;"

# Verificar tabelas do dash3
docker exec -it dashboard_addresses_db mysql -u root -ppassword dash3 -e "SHOW TABLES;"
```

## 🔧 Configuração

O script de importação usa as seguintes credenciais (do `.env` ou defaults):
- **Usuário root:** `root`
- **Senha:** `password` (ou valor de `DB_PASSWORD` no `.env`)
- **Banco:** `dash3`
- **Container:** `dashboard_addresses_db`

## 📝 Notas

- O arquivo `.sql` será salvo em `docs/dumps/`
- O script cria o banco `dash3` se não existir
- O banco será criado com charset `utf8mb4` e collation `utf8mb4_unicode_ci`
- O arquivo temporário no container é removido após a importação

