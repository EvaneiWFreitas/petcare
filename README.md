# 🐾 PetCare — Sistema de Gestão Veterinária

**PetCare** é um sistema de gestão veterinária desenvolvido para facilitar a administração de clínicas veterinárias, pet shops e estabelecimentos especializados no atendimento e cuidado de animais de estimação.

A plataforma tem como objetivo centralizar informações, organizar atendimentos, controlar agendamentos e facilitar a rotina administrativa e operacional de uma clínica veterinária.

---

## 📌 Sobre o Projeto

O PetCare foi idealizado para oferecer uma solução completa, moderna e intuitiva para o gerenciamento de estabelecimentos veterinários.

Com uma interface organizada e recursos integrados, o sistema busca proporcionar mais eficiência no atendimento, segurança no armazenamento das informações e praticidade para os profissionais da área veterinária.

## 🚀 Funcionalidades

### 🏥 Painel Administrativo
- Dashboard com visão geral do estabelecimento.
- Indicadores de atendimentos e agendamentos.
- Gerenciamento das informações da clínica.
- Controle de usuários e permissões de acesso.

### 🐶 Gestão de Animais (Pets)
- Cadastro completo de animais.
- Identificação por nome, espécie, raça e idade.
- Associação dos animais aos seus respectivos tutores.
- Histórico de atendimentos veterinários.

### 👥 Gestão de Tutores
- Cadastro de clientes e responsáveis pelos animais.
- Informações de contato.
- Histórico de animais vinculados.
- Consulta de registros dos tutores.

### 📅 Agendamentos
- Cadastro de consultas.
- Organização da agenda veterinária.
- Controle de datas e horários.
- Acompanhamento da situação dos agendamentos.

### 🩺 Atendimento Veterinário
- Registro de consultas.
- Histórico clínico dos animais.
- Anotações veterinárias.
- Registro de diagnósticos e tratamentos.

### 💊 Controle de Medicamentos
- Cadastro de medicamentos.
- Controle de estoque.
- Registro de movimentações.
- Acompanhamento de produtos disponíveis.

### 💰 Gestão Financeira
- Registro de receitas e despesas.
- Controle de pagamentos.
- Acompanhamento financeiro.
- Relatórios administrativos.

### 🔐 Segurança e Controle de Acesso
- Autenticação de usuários.
- Controle de permissões.
- Proteção de páginas administrativas.
- Gerenciamento de sessões.

### ⏳ Sistema de Licenciamento
- Controle do período de utilização do sistema.
- Configuração de prazo de funcionamento.
- Possibilidade de prorrogação da licença pelo administrador responsável.
- Bloqueio automático após o vencimento da licença, quando não houver renovação.

---

## 🛠️ Tecnologias Utilizadas

O projeto foi planejado para utilizar tecnologias de desenvolvimento web:

- **PHP:** processamento e lógica do sistema.
- **MySQL:** gerenciamento do banco de dados.
- **HTML5:** estrutura das páginas.
- **CSS3:** estilização e responsividade.
- **JavaScript:** interatividade da aplicação.
- **Bootstrap:** construção de interfaces responsivas.

*Observação: a relação acima representa a stack prevista. Ajuste conforme as tecnologias efetivamente utilizadas na versão do projeto.*

---

## 📂 Estrutura do Projeto

A organização dos diretórios deve seguir a arquitetura implementada na aplicação.

Exemplo de organização:

```text
PetCare/
│
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Views/
│   └── Helpers/
│
├── config/
│
├── database/
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── index.php
│
├── uploads/
│
├── .env.example
├── .gitignore
└── README.md
```

---

## ⚙️ Instalação e Configuração

### 1. Requisitos

- PHP 8.0 ou superior (conforme compatibilidade do projeto).
- MySQL ou MariaDB.
- Servidor Apache.
- XAMPP ou ambiente equivalente.
- Navegador atualizado.

### 2. Clonar o repositório

```bash
git clone URL_DO_REPOSITORIO
```

### 3. Acessar a pasta

```bash
cd PetCare
```

### 4. Configurar o banco de dados

- Inicie o Apache e o MySQL no XAMPP.
- Acesse o phpMyAdmin.
- Crie o banco de dados do PetCare.
- Importe o arquivo SQL disponibilizado no projeto.
- Configure as credenciais de conexão.

### 5. Configurar o ambiente

Configure o arquivo de ambiente ou conexão com o banco de dados, conforme a arquitetura utilizada.

### 6. Executar o sistema

Se estiver utilizando XAMPP, coloque o projeto na pasta:

```text
C:\xampp\htdocs\PetCare
```

Acesse pelo navegador:

```text
http://localhost/PetCare
```

---

## 🔒 Segurança

O PetCare deve adotar boas práticas de desenvolvimento, incluindo:

- Senhas armazenadas com hash seguro.
- Consultas parametrizadas para evitar SQL Injection.
- Validação de dados enviados pelos usuários.
- Proteção contra CSRF.
- Controle de acesso por perfil.
- Proteção de informações administrativas.
- Configuração adequada das permissões de arquivos e diretórios.

---

## 🎯 Objetivo do Projeto

Desenvolver uma plataforma de gestão veterinária capaz de simplificar os processos administrativos e operacionais de clínicas veterinárias, proporcionando organização, produtividade e melhor gerenciamento das informações.

## 👨‍💻 Desenvolvedor

**Evanei Freitas**

Desenvolvedor de Sistemas | Bacharel em Engenharia de Software

Projeto desenvolvido com foco em aprendizado, aplicação de conhecimentos em Engenharia de Software e desenvolvimento de soluções tecnológicas para necessidades reais.

---

## 📄 Licença

Este projeto possui licença a definir pelo proprietário do repositório.

---

<p align="center">
  🐾 <strong>PetCare — Tecnologia, cuidado e carinho pela vida animal!</strong>
</p>
