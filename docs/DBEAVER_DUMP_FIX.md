# Fix: DBeaver MySQL Dump - Erros Comuns

## Problema 1: Socket Error
Ao tentar fazer dump de uma base de dados MySQL externa via DBeaver, ocorre o erro:
```
mysqldump: Got error: 2002: Can't connect to local MySQL server through socket '/tmp/mysql.sock' (2)
```

### Causa
Quando você usa `--host=localhost`, o MySQL tenta usar um socket Unix em vez de uma conexão TCP/IP. Para conexões em portas não-padrão (como 36949), é necessário usar TCP/IP.

## Problema 2: MariaDB Client Not Found
Erro ao executar tarefas no DBeaver:
```
Utility 'mariadb' not found in client home '/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysqldump'
```

### Causa
O DBeaver está configurado para usar o cliente MariaDB, mas você tem MySQL instalado. Além disso, o caminho está apontando para o executável em vez do diretório bin.

### Solução: Configurar Cliente MySQL no DBeaver

1. **Abra as Preferências do DBeaver:**
   - macOS: `DBeaver` → `Preferences` (ou `Cmd + ,`)
   - Windows/Linux: `Window` → `Preferences`

2. **Navegue até MySQL:**
   - Vá em `Connections` → `Drivers` → `MySQL`
   - Ou: `Database` → `Drivers` → `MySQL`

3. **Configure o Client Home:**
   - Encontre o campo **"Client Home"** ou **"Native Client"**
   - Altere de qualquer caminho MariaDB para:
     ```
     /opt/homebrew/Cellar/mysql/9.5.0_2/bin
     ```
   - **Importante:** Deve ser o **diretório** `bin`, não o executável `mysqldump`

4. **Verifique os Caminhos dos Executáveis:**
   - **mysql:** `/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysql`
   - **mysqldump:** `/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysqldump`
   - **mysqladmin:** `/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysqladmin`

5. **Se não encontrar a opção "Client Home":**
   - Vá em `Connections` → `Drivers` → `MySQL`
   - Clique em **"Edit Driver"**
   - Na aba **"Native Client"**, configure:
     - **Client Home:** `/opt/homebrew/Cellar/mysql/9.5.0_2/bin`
     - **Client Type:** `MySQL` (não MariaDB)

6. **Aplique e Reinicie:**
   - Clique em **"OK"** ou **"Apply"**
   - Reinicie o DBeaver se necessário

## Solução para Problema 1 (Socket Error) no DBeaver

### Opção 1: Alterar o Host na Tarefa
1. Abra a tarefa de dump no DBeaver
2. Vá em **Connection settings** ou **Advanced settings**
3. Altere o **Host** de `localhost` para `127.0.0.1`
4. Mantenha a porta `36949`
5. Salve e execute novamente

### Opção 2: Adicionar Parâmetro Extra
1. Abra a tarefa de dump no DBeaver
2. Vá em **Extra command line arguments** ou **Additional options**
3. Adicione: `--protocol=TCP`
4. Salve e execute novamente

### Opção 3: Usar Terminal Diretamente (Recomendado se DBeaver continuar com problemas)
Execute o comando diretamente no terminal:

```bash
/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysqldump \
  --skip-lock-tables \
  --routines \
  --add-drop-table \
  --disable-keys \
  --extended-insert \
  --protocol=TCP \
  -u dash3 \
  --host=127.0.0.1 \
  --port=36949 \
  dash3 > ~/dump-dash3-$(date +%Y%m%d%H%M%S).sql
```

**Nota:** Se precisar de senha, adicione `-p` (sem espaço) e digite a senha quando solicitado, ou use `--password=SENHA` (menos seguro).

## Diferença entre localhost e 127.0.0.1
- `localhost`: Tenta usar socket Unix primeiro (`/tmp/mysql.sock`)
- `127.0.0.1`: Força conexão TCP/IP na porta especificada

Para conexões em portas não-padrão ou remotas, sempre use `127.0.0.1` ou `--protocol=TCP`.

## Verificação Rápida

Para verificar se os caminhos estão corretos no seu sistema:

```bash
# Verificar instalação do MySQL
ls -la /opt/homebrew/Cellar/mysql/9.5.0_2/bin/ | grep -E "(mysql|mysqldump)"

# Verificar links simbólicos
which mysql mysqldump

# Testar conexão direta
/opt/homebrew/Cellar/mysql/9.5.0_2/bin/mysql --version
```

## Resumo das Configurações Corretas

- **Client Home:** `/opt/homebrew/Cellar/mysql/9.5.0_2/bin` (diretório, não arquivo)
- **Client Type:** `MySQL` (não MariaDB)
- **Host para conexões TCP/IP:** `127.0.0.1` (não `localhost`)
- **Protocol:** `TCP` (para portas não-padrão)

