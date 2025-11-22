# Guia de Dump Seguro do Banco de Dados

## Uso

Execute o script para fazer um dump seguro do banco de dados:

```bash
./dump-database.sh
```

O script irá:
1. Carregar as configurações do banco de dados do Laravel
2. Solicitar a senha do banco (sem exibir na tela)
3. Criar um dump comprimido em `database/dumps/`
4. Gerar um arquivo de checksum MD5 para verificação de integridade

## Recursos de Segurança

✅ **Senha nunca exposta**: Usa arquivo temporário com permissões 600 e `trap` para limpeza automática
✅ **Sem histórico**: A senha não aparece no histórico de comandos
✅ **Sem processos visíveis**: A senha não aparece em `ps` ou logs do sistema
✅ **Limpeza automática**: Remove arquivos temporários mesmo em caso de erro

## Opções do mysqldump

O dump inclui:
- **--single-transaction**: Garante consistência sem bloquear tabelas
- **--routines**: Stored procedures e functions
- **--triggers**: Triggers
- **--events**: Eventos do MySQL
- **--hex-blob**: Codifica BLOBs em hexadecimal
- **--quick**: Processa linha por linha (eficiente para grandes tabelas)
- **--add-drop-database**: Adiciona comandos DROP DATABASE (útil para restauração completa)
- **--add-drop-table**: Adiciona comandos DROP TABLE

## Localização dos Dumps

Os dumps são salvos em:
```
database/dumps/dump_[NOME_DO_BANCO]_[TIMESTAMP].sql.gz
```

Cada dump inclui:
- Arquivo comprimido `.sql.gz`
- Arquivo de checksum `.md5` para verificação de integridade

## Restaurar um Dump

Para restaurar um dump:

```bash
gunzip < database/dumps/dump_[NOME]_[TIMESTAMP].sql.gz | \
  mysql -h [HOST] -P [PORT] -u [USER] -p [DATABASE]
```

Ou usando o Laravel:

```bash
gunzip < database/dumps/dump_[NOME]_[TIMESTAMP].sql.gz | \
  mysql -h $(php artisan tinker --execute="echo config('database.connections.mysql.host');") \
        -P $(php artisan tinker --execute="echo config('database.connections.mysql.port');") \
        -u $(php artisan tinker --execute="echo config('database.connections.mysql.username');") \
        -p \
        $(php artisan tinker --execute="echo config('database.connections.mysql.database');")
```

## Verificar Integridade

Para verificar a integridade de um dump:

```bash
cd database/dumps
md5sum -c dump_[NOME]_[TIMESTAMP].sql.gz.md5
```

## Listar Dumps Disponíveis

```bash
ls -lht database/dumps/*.sql.gz
```

