# Como Resolver o Bloqueio do GitHub Secret Scanning

## 🚨 Problema

O GitHub detectou um padrão similar a uma API key da Stripe (`sk_live_`) no commit e está bloqueando o push.

**Commit problemático:** `73a2caacef32f9033d0e35829b20bcea7f0d0efb`  
**Arquivo:** `docs/DOMAIN_FINAL_SUMMARY.md:552`

---

## ✅ Soluções

### Opção 1: Permitir o Secret via GitHub (Mais Rápido)

1. Acesse a URL fornecida pelo GitHub:
   ```
   https://github.com/gumbapt/addresses_dashboard/security/secret-scanning/unblock-secret/33vn8fHYdLZeTJVv9TOR6pispIU
   ```

2. Clique em **"Allow secret"**

3. Confirme que é um falso positivo (não é uma chave real da Stripe)

4. Faça push novamente:
   ```bash
   git push
   ```

**Vantagens:**
- ✅ Rápido (1 clique)
- ✅ Não reescreve histórico
- ✅ Commits permanecem iguais

**Desvantagens:**
- ⚠️ Secret permanece no histórico do Git

---

### Opção 2: Reescrever Histórico (Mais Limpo)

#### A. Interactive Rebase

1. Fazer rebase interativo dos últimos 3 commits:
   ```bash
   git rebase -i HEAD~3
   ```

2. No editor, mudar `pick` para `edit` no commit `73a2caa`:
   ```
   edit 73a2caa api test featuring
   pick 973201f fixing commit
   ```

3. Salvar e fechar o editor

4. Editar o arquivo:
   ```bash
   # Os arquivos estarão disponíveis
   # Já foram corrigidos localmente
   ```

5. Adicionar as mudanças:
   ```bash
   git add docs/DOMAIN_FINAL_SUMMARY.md
   git commit --amend --no-edit
   ```

6. Continuar o rebase:
   ```bash
   git rebase --continue
   ```

7. Force push (⚠️ cuidado se outros têm a branch):
   ```bash
   git push --force-with-lease
   ```

**Vantagens:**
- ✅ Histórico limpo
- ✅ Sem secrets no Git

**Desvantagens:**
- ⚠️ Reescreve histórico (force push necessário)
- ⚠️ Pode causar conflitos se branch compartilhada

---

#### B. Resetar e Recomitar (Alternativa)

1. Fazer backup das mudanças:
   ```bash
   git stash
   ```

2. Resetar para antes do commit problemático:
   ```bash
   git reset --hard e6ee2ea
   ```

3. Aplicar mudanças:
   ```bash
   git stash pop
   ```

4. Fazer novos commits:
   ```bash
   git add .
   git commit -m "feat: implement domain management system"
   ```

5. Push:
   ```bash
   git push --force-with-lease
   ```

---

### Opção 3: Criar Nova Branch (Mais Seguro)

1. Criar branch limpa a partir do commit bom:
   ```bash
   git checkout -b addresses_dashboard_clean e6ee2ea
   ```

2. Cherry-pick mudanças importantes:
   ```bash
   # Pegar mudanças de código (ignorar docs com sk_live_)
   git cherry-pick 973201f
   ```

3. Aplicar mudanças atuais:
   ```bash
   git add .
   git commit -m "feat: implement domain management with dmn_live_ API keys"
   ```

4. Push da nova branch:
   ```bash
   git push -u origin addresses_dashboard_clean
   ```

5. Deletar branch antiga (opcional):
   ```bash
   git push origin --delete addresses_dashboard
   ```

---

## 🎯 Recomendação

### Para Branch Não Compartilhada: Opção 1 ou 2
Se você é o único trabalhando nesta branch, use **Opção 1** (mais rápido) ou **Opção 2A** (mais limpo).

### Para Branch Compartilhada: Opção 1
Se outros desenvolvedores têm esta branch, use **Opção 1** (permitir via GitHub) para não causar conflitos.

### Para Máxima Limpeza: Opção 3
Se quer histórico perfeito e não tem problema em criar nova branch.

---

## 📋 Verificação

Após resolver, verificar que não há mais `sk_live_`:

```bash
# Buscar em todos os arquivos
git grep "sk_live_"

# Buscar em commits
git log -p --all -S "sk_live_"
```

Se retornar vazio, está limpo! ✅

---

## 🔐 Prevenção Futura

### 1. Git Hooks (Pre-commit)

Criar `.git/hooks/pre-commit`:

```bash
#!/bin/bash

# Detectar padrões de API keys conhecidas
if git diff --cached | grep -E "(sk_live_|pk_live_|sk_test_)"; then
    echo "❌ Detectado padrão de API key da Stripe!"
    echo "Use dmn_live_ ou dmn_test_ para exemplos de documentação"
    exit 1
fi

exit 0
```

Tornar executável:
```bash
chmod +x .git/hooks/pre-commit
```

### 2. .gitignore Patterns

Adicionar ao `.gitignore`:

```gitignore
# Secrets
*.key
*.pem
*_secret.json
.env.local
config/secrets.php
```

### 3. Pre-commit Hook Tool

Instalar ferramentas como:
- **git-secrets** (AWS)
- **detect-secrets** (Yelp)
- **gitleaks**

---

## ✅ Solução Imediata

**A mais simples:**

1. Clique no link:
   ```
   https://github.com/gumbapt/addresses_dashboard/security/secret-scanning/unblock-secret/33vn8fHYdLZeTJVv9TOR6pispIU
   ```

2. Clique em **"Allow secret"**

3. Execute:
   ```bash
   git push
   ```

Pronto! 🎉

---

**Nota:** O código já está correto (usando `dmn_live_`), apenas o histórico do Git tem o commit antigo com `sk_live_`.

