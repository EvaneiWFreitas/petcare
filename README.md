# PetCare — Sistema de Gestão Veterinária

Sistema web completo para clínicas veterinárias, desenvolvido em **PHP 8.2+ puro**, sem frameworks PHP, com MySQL/MariaDB, Bootstrap 5, Chart.js e Font Awesome.

## Requisitos

- XAMPP com Apache e MySQL/MariaDB ativos.
- PHP 8.2 ou superior.
- Extensões PHP: `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`.
- MySQL 5.7+/8.0 ou MariaDB 10.4+.
- Navegador moderno.

## Instalação local (XAMPP)

1. Copie a pasta `petcare` para `C:\xampp\htdocs\petcare`.
2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. Crie o banco (o SQL também cria o banco se ele não existir):

   ```powershell
   & 'C:\xampp\mysql\bin\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS petcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

4. Importe o SQL:

   ```powershell
   & 'C:\xampp\mysql\bin\mysql.exe' -u root petcare_db < 'C:\xampp\htdocs\petcare\database\petcare_db.sql'
   ```

5. Ajuste as credenciais em `C:\xampp\htdocs\petcare\.env`.
   - Se não houver `.env`, copie `.env.example` para `.env`.
6. Acesse:

   ```text
   http://localhost/petcare/public/index.php?r=auth.login
   ```

   Também é possível usar `http://localhost/petcare/` se o Apache estiver com `mod_rewrite` ativo.

## Servidor embutido do PHP

Para abrir o site sem configurar um virtual host, execute na raiz do projeto:

```powershell
cd C:\xampp\htdocs\petcare
php -S localhost:8000
```

Páginas disponíveis:

```text
http://localhost:8000/                    Página principal
http://localhost:8000/?r=site.profissional Página Profissional
http://localhost:8000/profissional        Alias da página Profissional
http://localhost:8000/?r=auth.login       Acesso ao sistema
```

O banco continua sendo o MySQL/MariaDB do XAMPP; apenas o servidor web é o embutido do PHP.

## Credencial inicial

| Campo | Valor |
| --- | --- |
| E-mail | `admin@petcare.com.br` |
| Senha | `PetCare@2026` |

No primeiro login o sistema exige a troca de senha. Após a troca, o acesso continua com o e-mail e a nova senha definida.

> O SQL de instalação usa `INSERT ... ON DUPLICATE KEY UPDATE` para o administrador e **não substitui uma senha já alterada**. Para restaurar a senha inicial, execute:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "UPDATE petcare_db.usuarios SET senha='`$2y`$12`$1HyBt72SyQn6a7Rl50QUEey16Lh1SXrm56dGpxxEAK2/DeJhG.kq2', trocar_senha=1 WHERE id=1;"
```

## Portal do tutor

O portal é separado do painel administrativo:

```text
http://localhost/petcare/public/index.php?r=portal.login
```

Login do tutor: **e-mail cadastrado + CPF cadastrado** (somente dígitos, com ou sem pontuação).

## Estrutura

```text
petcare/
├── app/
│   ├── Core/          # Router, Database, Session, Request, Controller, Autoloader
│   ├── Controllers/   # Controllers dos módulos
│   ├── Helpers/       # Funções globais
│   ├── Middleware/    # Suporte a autenticação/permissões
│   └── Validators/    # Validator e regras
├── config/            # app, database, security, modules
├── database/
│   ├── petcare_db.sql       # SQL principal
│   └── petcare_install.sql  # Cópia de segurança do SQL
├── public/
│   ├── index.php      # Front controller
│   ├── assets/        # CSS/JS
│   └── uploads/       # Arquivos enviados (precisa ser gravável)
├── resources/views/   # Layouts, páginas e parciais
├── routes/web.php     # Todas as rotas nomeadas
├── storage/logs/      # Logs de erro (precisa ser gravável)
├── .env               # Configuração ativa local
└── .env.example       # Modelo de configuração
```

## Permissões de pastas

Em Windows normalmente não é necessário ajustar. Em Linux/servidor:

```bash
chmod 755 -R public storage
chmod 775 -R public/uploads storage/logs
```

## Configuração

Edite `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/petcare/public
APP_KEY=984e981d64c51c75976ae873463a677d7b787fbaef5023a4ad15b88d294a1d33
TIMEZONE=America/Sao_Paulo

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=petcare_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

SESSION_NAME=petcare_session
CSRF_TTL=7200
PASSWORD_COST=12
UPLOAD_MAX_SIZE=5242880
```

Em produção: use `APP_ENV=production`, `APP_DEBUG=false`, troque `APP_KEY` e configure um usuário MySQL com senha.

## Módulos implementados

- Dashboard com KPIs, gráficos de receitas/despesas e próximos agendamentos.
- Clientes/tutores e animais com ficha individual.
- Veterinários e catálogos: espécies, raças, especialidades, vacinas, categorias, fornecedores e serviços.
- Agenda com bloqueio de conflito de horário por veterinário.
- Atendimentos, prontuários com auditoria, vacinação e carteirinha.
- Receitas com impressão, exames, cirurgias e internações.
- Farmácia/estoque com movimentações, compras e recebimento.
- Financeiro: contas a receber/pagar, pagamentos e fluxo de caixa.
- Banho e tosa.
- Usuários, perfis e permissões por módulo/ação.
- Relatórios em tela, CSV e impressão.
- Notificações, configurações da clínica e backup.
- Portal do tutor com animais, agendamentos e histórico.

## Rotas

Todas as rotas passam por `public/index.php` usando `?r=nome.da.rota`.

Exemplos:

```text
?r=dashboard.index
?r=clientes.index
?r=agenda.create
?r=financeiro.receber
?r=relatorios.generate&tipo=clientes&inicio=2026-11-01&fim=2026-11-30&formato=csv
?r=portal.login
```

O arquivo `routes/web.php` define método HTTP, controller, autenticação e permissão de cada rota.

## Segurança

- CSRF em todos os formulários, com validação no POST.
- Sessão regenerada no login e no portal do tutor.
- Senhas com `password_hash()`/`password_verify()` e custo configurável.
- Bloqueio temporário após tentativas falhas de login.
- Consultas via PDO com prepared statements.
- Upload validado por extensão, MIME e tamanho.
- `.env` protegido pelo `.htaccess` na raiz.
- Permissões verificadas por `perfil_id + módulo + ação` na tabela `permissoes`.

## Backup e restauração

Gerar dump pelo XAMPP:

```powershell
& 'C:\xampp\mysql\bin\mysqldump.exe' -u root petcare_db > 'C:\xampp\htdocs\petcare\storage\backup-petcare.sql'
```

Restaurar:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root petcare_db < 'C:\xampp\htdocs\petcare\storage\backup-petcare.sql'
```

Também há backup pelo painel em `?r=config.index`.

## Verificação rápida

```powershell
php -v
php -l 'C:\xampp\htdocs\petcare\public\index.php'
```

Para validar todos os arquivos PHP:

```powershell
Get-ChildItem -Path 'C:\xampp\htdocs\petcare' -Recurse -Filter *.php | Where-Object { $_.FullName -notmatch '\\vendor\\' } | ForEach-Object { php -l $_.FullName }
```

## Erros comuns

- **Página em branco:** confirme PHP 8.2+ e veja `storage\logs\errors.log`.
- **Erro de conexão:** confira `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` no `.env`.
- **404 em rotas:** use `?r=auth.login` ou habilite `mod_rewrite` no Apache.
- **CSRF inválido:** recarregue a página e reenvie o formulário; sessões expiram após o tempo configurado.
- **SQL desapareceu:** alguns antivírus sinalizam arquivos `.sql`; recopie `database\petcare_install.sql` para `database\petcare_db.sql`.
- **Erro ao enviar arquivo:** verifique `UPLOAD_MAX_SIZE`, `upload_max_filesize` e `post_max_size` no PHP.
- **Acesso negado em módulo:** o perfil do usuário não possui a permissão necessária; ajuste em `?r=usuarios.permissions`.

## Publicação em servidor

1. Envie todos os arquivos, exceto `.env`, `storage\logs\*` e `public\uploads\*`.
2. Crie o banco e importe `database\petcare_db.sql`.
3. Crie `.env` no servidor com `APP_ENV=production`, `APP_DEBUG=false` e credenciais reais.
4. Aponte o DocumentRoot para `public/` ou mantenha o front controller em `public/index.php`.
5. Conceda escrita em `public/uploads/` e `storage/logs/`.
6. Troque a senha inicial do administrador e desative usuários não utilizados.
7. Configure HTTPS e backups periódicos do banco e dos uploads.
