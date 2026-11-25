-- PetCare - Schema completo para MySQL 8 / phpMyAdmin (XAMPP)
CREATE DATABASE IF NOT EXISTS petcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petcare_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Remoção em ordem inversa de dependência
DROP TABLE IF EXISTS historico_licencas;
DROP TABLE IF EXISTS licencas;
DROP TABLE IF EXISTS clinicas;
DROP TABLE IF EXISTS prontuario_auditorias;
DROP TABLE IF EXISTS permissoes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS notificacoes;
DROP TABLE IF EXISTS anexos;
DROP TABLE IF EXISTS internacao_evolucoes;
DROP TABLE IF EXISTS exame_resultados;
DROP TABLE IF EXISTS receita_itens;
DROP TABLE IF EXISTS pagamentos;
DROP TABLE IF EXISTS despesas;
DROP TABLE IF EXISTS compra_itens;
DROP TABLE IF EXISTS estoque_movimentacoes;
DROP TABLE IF EXISTS compras;
DROP TABLE IF EXISTS banho_tosa;
DROP TABLE IF EXISTS vacinacoes;
DROP TABLE IF EXISTS receitas;
DROP TABLE IF EXISTS exames;
DROP TABLE IF EXISTS prontuarios;
DROP TABLE IF EXISTS atendimentos;
DROP TABLE IF EXISTS agendamentos;
DROP TABLE IF EXISTS animais;
DROP TABLE IF EXISTS cirurgias;
DROP TABLE IF EXISTS internacoes;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS racas;
DROP TABLE IF EXISTS especies;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS servicos;
DROP TABLE IF EXISTS vacinas;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS fornecedores;
DROP TABLE IF EXISTS contas_receber;
DROP TABLE IF EXISTS contas_pagar;
DROP TABLE IF EXISTS veterinarios;
DROP TABLE IF EXISTS especialidades;
DROP TABLE IF EXISTS configuracoes;
DROP TABLE IF EXISTS perfis;

CREATE TABLE perfis (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    descricao TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_perfis_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    foto VARCHAR(255) NULL AFTER email,
    senha VARCHAR(255) NOT NULL,
    perfil_id INT NOT NULL,
    funcionario_id INT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    trocar_senha TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acesso DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_perfil (perfil_id),
    CONSTRAINT fk_usuarios_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissoes (
    id INT NOT NULL AUTO_INCREMENT,
    perfil_id INT NOT NULL,
    modulo VARCHAR(80) NOT NULL,
    acao VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissoes_perfil_modulo_acao (perfil_id, modulo, acao),
    CONSTRAINT fk_permissoes_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE especialidades (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_especialidades_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE veterinarios (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) NULL,
    rg VARCHAR(20) NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    endereco VARCHAR(255) NULL,
    especialidade_id INT NULL,
    crmv VARCHAR(30) NULL,
    cargo VARCHAR(100) NULL,
    data_contratacao DATE NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_veterinarios_cpf (cpf),
    UNIQUE KEY uq_veterinarios_crmv (crmv),
    KEY idx_veterinarios_especialidade (especialidade_id),
    CONSTRAINT fk_veterinarios_especialidade FOREIGN KEY (especialidade_id) REFERENCES especialidades(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE especies (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_especies_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE racas (
    id INT NOT NULL AUTO_INCREMENT,
    especie_id INT NOT NULL,
    nome VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_racas_especie_nome (especie_id, nome),
    KEY idx_racas_especie (especie_id),
    CONSTRAINT fk_racas_especie FOREIGN KEY (especie_id) REFERENCES especies(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nome VARCHAR(180) NOT NULL,
    cpf VARCHAR(14) NULL,
    rg VARCHAR(20) NULL,
    data_nascimento DATE NULL,
    sexo ENUM('M','F','Outro') NULL,
    telefone VARCHAR(20) NULL,
    whatsapp VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    foto VARCHAR(255) NULL,
    cep VARCHAR(10) NULL,
    endereco VARCHAR(255) NULL,
    numero VARCHAR(20) NULL,
    complemento VARCHAR(120) NULL,
    bairro VARCHAR(120) NULL,
    cidade VARCHAR(120) NULL,
    estado CHAR(2) NULL,
    observacoes TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clientes_codigo (codigo),
    UNIQUE KEY uq_clientes_cpf (cpf),
    KEY idx_clientes_nome (nome),
    KEY idx_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE animais (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    cliente_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    foto VARCHAR(255) NULL,
    especie_id INT NOT NULL,
    raca_id INT NULL,
    sexo ENUM('M','F') NULL,
    data_nascimento DATE NULL,
    cor VARCHAR(80) NULL,
    peso DECIMAL(8,2) NULL,
    porte ENUM('Mini','Pequeno','Medio','Grande','Gigante') NULL,
    microchip VARCHAR(50) NULL,
    castrado TINYINT(1) NOT NULL DEFAULT 0,
    alergias TEXT NULL,
    restricoes_alimentares TEXT NULL,
    doencas_preexistentes TEXT NULL,
    observacoes TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_animais_codigo (codigo),
    UNIQUE KEY uq_animais_microchip (microchip),
    KEY idx_animais_cliente (cliente_id),
    KEY idx_animais_especie (especie_id),
    KEY idx_animais_raca (raca_id),
    KEY idx_animais_nome (nome),
    CONSTRAINT fk_animais_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_animais_especie FOREIGN KEY (especie_id) REFERENCES especies(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_animais_raca FOREIGN KEY (raca_id) REFERENCES racas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agendamentos (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    cliente_id INT NOT NULL,
    animal_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    data_agendamento DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NULL,
    duracao_minutos INT NOT NULL DEFAULT 30,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacoes TEXT NULL,
    status ENUM('aguardando','confirmado','em_atendimento','finalizado','cancelado','falta') NOT NULL DEFAULT 'aguardando',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_agendamentos_codigo (codigo),
    KEY idx_agendamentos_vet_data_hora (veterinario_id, data_agendamento, hora_inicio, hora_fim),
    KEY idx_agendamentos_data (data_agendamento),
    KEY idx_agendamentos_cliente (cliente_id),
    KEY idx_agendamentos_animal (animal_id),
    CONSTRAINT fk_agendamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_agendamentos_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_agendamentos_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE atendimentos (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    agendamento_id INT NULL,
    animal_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    data_atendimento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    queixa_principal TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'finalizado',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_atendimentos_codigo (codigo),
    KEY idx_atendimentos_agendamento (agendamento_id),
    KEY idx_atendimentos_animal (animal_id),
    KEY idx_atendimentos_veterinario (veterinario_id),
    KEY idx_atendimentos_data (data_atendimento),
    CONSTRAINT chk_atendimentos_status CHECK (status IN ('finalizado','em_andamento','concluido','cancelado','pendente','aguardando')),
    CONSTRAINT fk_atendimentos_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_atendimentos_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_atendimentos_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prontuarios (
    id INT NOT NULL AUTO_INCREMENT,
    atendimento_id INT NULL,
    animal_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    data_atendimento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    historico_clinico TEXT NULL,
    anamnese TEXT NULL,
    exame_fisico TEXT NULL,
    temperatura DECIMAL(5,2) NULL,
    frequencia_cardiaca INT NULL,
    frequencia_respiratoria INT NULL,
    peso DECIMAL(8,2) NULL,
    altura DECIMAL(8,2) NULL,
    diagnostico TEXT NULL,
    suspeita_clinica TEXT NULL,
    procedimentos TEXT NULL,
    medicamentos TEXT NULL,
    dosagem TEXT NULL,
    frequencia TEXT NULL,
    duracao_tratamento TEXT NULL,
    recomendacoes TEXT NULL,
    retorno_recomendado DATE NULL,
    finalizado TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_prontuarios_atendimento (atendimento_id),
    KEY idx_prontuarios_animal (animal_id),
    KEY idx_prontuarios_veterinario (veterinario_id),
    KEY idx_prontuarios_data (data_atendimento),
    CONSTRAINT fk_prontuarios_atendimento FOREIGN KEY (atendimento_id) REFERENCES atendimentos(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_prontuarios_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prontuarios_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prontuario_auditorias (
    id INT NOT NULL AUTO_INCREMENT,
    prontuario_id INT NOT NULL,
    usuario_id INT NOT NULL,
    acao VARCHAR(60) NOT NULL,
    dados_anteriores LONGTEXT NULL,
    dados_novos LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_prontuario_auditorias_prontuario (prontuario_id),
    KEY idx_prontuario_auditorias_usuario (usuario_id),
    CONSTRAINT fk_prontuario_auditorias_prontuario FOREIGN KEY (prontuario_id) REFERENCES prontuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prontuario_auditorias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vacinas (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    fabricante VARCHAR(120) NULL,
    tipo VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vacinas_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vacinacoes (
    id INT NOT NULL AUTO_INCREMENT,
    animal_id INT NOT NULL,
    cliente_id INT NOT NULL,
    vacina_id INT NOT NULL,
    veterinario_id INT NULL,
    lote VARCHAR(80) NULL,
    data_aplicacao DATE NOT NULL,
    proxima_dose DATE NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vacinacoes_animal (animal_id),
    KEY idx_vacinacoes_cliente (cliente_id),
    KEY idx_vacinacoes_vacina (vacina_id),
    KEY idx_vacinacoes_veterinario (veterinario_id),
    KEY idx_vacinacoes_data (data_aplicacao),
    CONSTRAINT fk_vacinacoes_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vacinacoes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vacinacoes_vacina FOREIGN KEY (vacina_id) REFERENCES vacinas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vacinacoes_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receitas (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    animal_id INT NOT NULL,
    cliente_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    data_prescricao DATE NOT NULL,
    orientacoes TEXT NULL,
    controle_especial TINYINT(1) NOT NULL DEFAULT 0,
    assinatura TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_receitas_codigo (codigo),
    KEY idx_receitas_animal (animal_id),
    KEY idx_receitas_cliente (cliente_id),
    KEY idx_receitas_veterinario (veterinario_id),
    CONSTRAINT fk_receitas_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_receitas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_receitas_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receita_itens (
    id INT NOT NULL AUTO_INCREMENT,
    receita_id INT NOT NULL,
    medicamento VARCHAR(180) NOT NULL,
    dosagem VARCHAR(120) NULL,
    via_administracao VARCHAR(80) NULL,
    frequencia VARCHAR(120) NULL,
    duracao VARCHAR(120) NULL,
    observacoes TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_receita_itens_receita (receita_id),
    CONSTRAINT fk_receita_itens_receita FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exames (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    animal_id INT NOT NULL,
    cliente_id INT NOT NULL,
    veterinario_id INT NULL,
    prontuario_id INT NULL,
    tipo VARCHAR(120) NOT NULL,
    laboratorio VARCHAR(150) NULL,
    data_solicitacao DATE NOT NULL,
    data_resultado DATE NULL,
    resultados TEXT NULL,
    observacoes TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'solicitado',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_exames_codigo (codigo),
    KEY idx_exames_animal (animal_id),
    KEY idx_exames_cliente (cliente_id),
    KEY idx_exames_veterinario (veterinario_id),
    KEY idx_exames_prontuario (prontuario_id),
    CONSTRAINT chk_exames_status CHECK (status IN ('solicitado','em_andamento','concluido','finalizado','cancelado','resultado_disponivel')),
    CONSTRAINT fk_exames_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_exames_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_exames_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_exames_prontuario FOREIGN KEY (prontuario_id) REFERENCES prontuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exame_resultados (
    id INT NOT NULL AUTO_INCREMENT,
    exame_id INT NOT NULL,
    descricao TEXT NOT NULL,
    valor VARCHAR(150) NULL,
    unidade VARCHAR(40) NULL,
    referencia VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_exame_resultados_exame (exame_id),
    CONSTRAINT fk_exame_resultados_exame FOREIGN KEY (exame_id) REFERENCES exames(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cirurgias (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    animal_id INT NOT NULL,
    cliente_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    tipo VARCHAR(150) NOT NULL,
    data_cirurgia DATE NOT NULL,
    horario TIME NULL,
    avaliacao_pre_operatoria TEXT NULL,
    exames_pre_operatorios TEXT NULL,
    anestesia TEXT NULL,
    procedimento_realizado TEXT NULL,
    materiais TEXT NULL,
    evolucao_pos_operatoria TEXT NULL,
    recomendacoes TEXT NULL,
    retorno DATE NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'agendada',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cirurgias_codigo (codigo),
    KEY idx_cirurgias_animal (animal_id),
    KEY idx_cirurgias_cliente (cliente_id),
    KEY idx_cirurgias_veterinario (veterinario_id),
    KEY idx_cirurgias_data (data_cirurgia),
    CONSTRAINT chk_cirurgias_status CHECK (status IN ('agendada','realizada','concluida','cancelada','em_andamento','pos_operatorio')),
    CONSTRAINT fk_cirurgias_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cirurgias_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cirurgias_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE internacoes (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    animal_id INT NOT NULL,
    cliente_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    motivo TEXT NOT NULL,
    diagnostico TEXT NULL,
    box_leito VARCHAR(60) NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_alta DATETIME NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'internado',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_internacoes_codigo (codigo),
    KEY idx_internacoes_animal (animal_id),
    KEY idx_internacoes_cliente (cliente_id),
    KEY idx_internacoes_veterinario (veterinario_id),
    KEY idx_internacoes_status (status),
    CONSTRAINT chk_internacoes_status CHECK (status IN ('internado','observacao','alta','transferido','falecido','cancelado')),
    CONSTRAINT fk_internacoes_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_internacoes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_internacoes_veterinario FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE internacao_evolucoes (
    id INT NOT NULL AUTO_INCREMENT,
    internacao_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_evolucao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    alimentacao TEXT NULL,
    hidratacao TEXT NULL,
    sinais_vitais TEXT NULL,
    medicamentos TEXT NULL,
    intercorrencias TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_internacao_evolucoes_internacao (internacao_id),
    KEY idx_internacao_evolucoes_usuario (usuario_id),
    CONSTRAINT fk_internacao_evolucoes_internacao FOREIGN KEY (internacao_id) REFERENCES internacoes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_internacao_evolucoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fornecedores (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(180) NOT NULL,
    cnpj_cpf VARCHAR(20) NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    endereco VARCHAR(255) NULL,
    contato VARCHAR(150) NULL,
    observacoes TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fornecedores_cnpj_cpf (cnpj_cpf),
    KEY idx_fornecedores_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE produtos (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nome VARCHAR(180) NOT NULL,
    categoria_id INT NULL,
    fornecedor_id INT NULL,
    fabricante VARCHAR(150) NULL,
    lote VARCHAR(80) NULL,
    codigo_barras VARCHAR(80) NULL,
    quantidade INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 0,
    preco_custo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    preco_venda DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    data_fabricacao DATE NULL,
    data_validade DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_produtos_codigo (codigo),
    UNIQUE KEY uq_produtos_codigo_barras (codigo_barras),
    KEY idx_produtos_categoria (categoria_id),
    KEY idx_produtos_fornecedor (fornecedor_id),
    KEY idx_produtos_nome (nome),
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_produtos_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE compras (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    fornecedor_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_compra DATE NOT NULL,
    forma_pagamento VARCHAR(60) NULL,
    valor_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',
    recebido_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_compras_codigo (codigo),
    KEY idx_compras_fornecedor (fornecedor_id),
    KEY idx_compras_usuario (usuario_id),
    CONSTRAINT chk_compras_status CHECK (status IN ('pendente','recebido','cancelado','pago','parcial')),
    CONSTRAINT fk_compras_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compras_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE compra_itens (
    id INT NOT NULL AUTO_INCREMENT,
    compra_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_compra_itens_compra (compra_id),
    KEY idx_compra_itens_produto (produto_id),
    CONSTRAINT fk_compra_itens_compra FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_compra_itens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE estoque_movimentacoes (
    id INT NOT NULL AUTO_INCREMENT,
    produto_id INT NOT NULL,
    tipo ENUM('entrada','saida') NOT NULL,
    quantidade INT NOT NULL,
    quantidade_anterior INT NOT NULL,
    quantidade_atual INT NOT NULL,
    lote VARCHAR(80) NULL,
    documento VARCHAR(120) NULL,
    observacoes TEXT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_estoque_movimentacoes_produto (produto_id),
    KEY idx_estoque_movimentacoes_usuario (usuario_id),
    KEY idx_estoque_movimentacoes_tipo (tipo),
    CONSTRAINT fk_estoque_movimentacoes_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_estoque_movimentacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicos (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duracao_minutos INT NOT NULL DEFAULT 30,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_servicos_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE banho_tosa (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    animal_id INT NOT NULL,
    cliente_id INT NOT NULL,
    profissional_id INT NULL,
    servico_id INT NOT NULL,
    data_servico DATETIME NOT NULL,
    observacoes TEXT NULL,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'agendado',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_banho_tosa_codigo (codigo),
    KEY idx_banho_tosa_animal (animal_id),
    KEY idx_banho_tosa_cliente (cliente_id),
    KEY idx_banho_tosa_profissional (profissional_id),
    KEY idx_banho_tosa_servico (servico_id),
    KEY idx_banho_tosa_data (data_servico),
    CONSTRAINT chk_banho_tosa_status CHECK (status IN ('agendado','em_andamento','concluido','finalizado','cancelado')),
    CONSTRAINT fk_banho_tosa_animal FOREIGN KEY (animal_id) REFERENCES animais(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_banho_tosa_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_banho_tosa_profissional FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_banho_tosa_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contas_receber (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    cliente_id INT NULL,
    origem VARCHAR(60) NOT NULL,
    referencia_id INT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    desconto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    vencimento DATE NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'aberto',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contas_receber_codigo (codigo),
    KEY idx_contas_receber_cliente (cliente_id),
    KEY idx_contas_receber_origem (origem, referencia_id),
    KEY idx_contas_receber_vencimento (vencimento),
    CONSTRAINT chk_contas_receber_status CHECK (status IN ('aberto','pago','parcial','cancelado','vencido')),
    CONSTRAINT fk_contas_receber_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contas_pagar (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    fornecedor_id INT NULL,
    categoria VARCHAR(100) NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    vencimento DATE NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'aberto',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contas_pagar_codigo (codigo),
    KEY idx_contas_pagar_fornecedor (fornecedor_id),
    KEY idx_contas_pagar_vencimento (vencimento),
    CONSTRAINT chk_contas_pagar_status CHECK (status IN ('aberto','pago','parcial','cancelado','vencido')),
    CONSTRAINT fk_contas_pagar_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagamentos (
    id INT NOT NULL AUTO_INCREMENT,
    conta_receber_id INT NULL,
    conta_pagar_id INT NULL,
    cliente_id INT NULL,
    valor DECIMAL(12,2) NOT NULL,
    forma_pagamento VARCHAR(60) NOT NULL,
    data_pagamento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pagamentos_conta_receber (conta_receber_id),
    KEY idx_pagamentos_conta_pagar (conta_pagar_id),
    KEY idx_pagamentos_cliente (cliente_id),
    KEY idx_pagamentos_usuario (usuario_id),
    KEY idx_pagamentos_data (data_pagamento),
    CONSTRAINT fk_pagamentos_conta_receber FOREIGN KEY (conta_receber_id) REFERENCES contas_receber(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pagamentos_conta_pagar FOREIGN KEY (conta_pagar_id) REFERENCES contas_pagar(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pagamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pagamentos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE despesas (
    id INT NOT NULL AUTO_INCREMENT,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(100) NULL,
    valor DECIMAL(12,2) NOT NULL,
    data_despesa DATE NOT NULL,
    forma_pagamento VARCHAR(60) NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_despesas_usuario (usuario_id),
    KEY idx_despesas_data (data_despesa),
    CONSTRAINT fk_despesas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificacoes (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NULL,
    titulo VARCHAR(180) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo VARCHAR(40) NULL,
    referencia VARCHAR(120) NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_evento DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notificacoes_usuario_lida (usuario_id, lida),
    CONSTRAINT fk_notificacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE anexos (
    id INT NOT NULL AUTO_INCREMENT,
    entidade VARCHAR(60) NOT NULL,
    entidade_id INT NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    caminho VARCHAR(500) NOT NULL,
    mime VARCHAR(120) NULL,
    tamanho INT NOT NULL DEFAULT 0,
    usuario_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_anexos_entidade (entidade, entidade_id),
    KEY idx_anexos_usuario (usuario_id),
    CONSTRAINT fk_anexos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NULL,
    acao VARCHAR(80) NOT NULL,
    modulo VARCHAR(80) NULL,
    registro_id INT NULL,
    detalhes TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_usuario (usuario_id),
    KEY idx_logs_modulo (modulo),
    KEY idx_logs_acao (acao),
    CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracoes (
    id INT NOT NULL AUTO_INCREMENT,
    chave VARCHAR(120) NOT NULL,
    valor TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_configuracoes_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clinicas (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    responsavel VARCHAR(150) NULL,
    cnpj VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(30) NULL,
    local_instalacao TINYINT(1) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clinicas_codigo (codigo),
    KEY idx_clinicas_local (local_instalacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE licencas (
    id INT NOT NULL AUTO_INCREMENT,
    clinica_id INT NOT NULL,
    codigo_licenca VARCHAR(60) NOT NULL,
    modalidade VARCHAR(30) NOT NULL DEFAULT '30_dias',
    dias_contratados INT NOT NULL DEFAULT 30,
    data_ativacao DATE NULL,
    data_vencimento DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'inativa',
    licenca_permanente TINYINT(1) NOT NULL DEFAULT 0,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_licencas_clinica (clinica_id),
    UNIQUE KEY uq_licencas_codigo (codigo_licenca),
    KEY idx_licencas_status (status),
    KEY idx_licencas_vencimento (data_vencimento),
    CONSTRAINT fk_licencas_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historico_licencas (
    id INT NOT NULL AUTO_INCREMENT,
    licenca_id INT NOT NULL,
    usuario_responsavel INT NULL,
    tipo_operacao VARCHAR(60) NOT NULL,
    dias_adicionados INT NULL,
    vencimento_anterior DATE NULL,
    novo_vencimento DATE NULL,
    descricao TEXT NULL,
    ip_responsavel VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historico_licenca (licenca_id),
    KEY idx_historico_usuario (usuario_responsavel),
    CONSTRAINT fk_historico_licencas_licenca FOREIGN KEY (licenca_id) REFERENCES licencas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_historico_licencas_usuario FOREIGN KEY (usuario_responsavel) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Dados iniciais: perfis
INSERT INTO perfis (id, nome, slug, descricao) VALUES
(1, 'Administrador', 'administrador', 'Acesso total ao sistema'),
(2, 'Médico veterinário', 'veterinario', 'Atendimentos clínicos e prontuários'),
(3, 'Recepcionista', 'recepcionista', 'Cadastros e agenda'),
(4, 'Financeiro', 'financeiro', 'Contas, pagamentos e relatórios'),
(5, 'Estoquista', 'estoquista', 'Farmácia e compras'),
(6, 'Auxiliar veterinário', 'auxiliar', 'Apoio em vacinação e exames'),
(7, 'Banho e tosa', 'banho_tosa', 'Agenda de banho e tosa'),
(8, 'Tutor', 'tutor', 'Acesso ao portal do tutor'),
(9, 'Proprietário PetCare', 'superadmin', 'Proprietário do software PetCare')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), slug = VALUES(slug);

-- Usuário administrador (senha não é alterada em reimportações)
INSERT INTO usuarios (nome, email, senha, perfil_id, ativo, trocar_senha) VALUES
('Administrador PetCare', 'admin@petcare.com.br', '$2y$12$1HyBt72SyQn6a7Rl50QUEey16Lh1SXrm56dGpxxEAK2/DeJhG.kq2', 1, 1, 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), trocar_senha = 1;

INSERT INTO usuarios (nome, email, senha, perfil_id, ativo, trocar_senha) VALUES
('Proprietário PetCare', 'proprietario@petcare.com.br', '$2y$12$snFpT8ODSv22AWMXuXqPse14sPt9VRw28/Fwmyi.QiW.iPsM9GO7K', 9, 1, 1)
ON DUPLICATE KEY UPDATE perfil_id = 9, ativo = 1, trocar_senha = 1;

-- Espécies e raças
INSERT IGNORE INTO especies (id, nome) VALUES
(1, 'Cachorro'), (2, 'Gato'), (3, 'Pássaro'), (4, 'Coelho'), (5, 'Hamster'), (6, 'Répteis'), (7, 'Outros');

INSERT IGNORE INTO racas (especie_id, nome) VALUES
(1, 'Golden Retriever'), (1, 'Labrador Retriever'), (1, 'Poodle'), (1, 'Yorkshire Terrier'),
(1, 'Shih Tzu'), (1, 'Pastor Alemão'), (1, 'Buldogue Francês'), (1, 'Beagle'),
(1, 'Dachshund'), (1, 'Boxer'), (1, 'Border Collie'), (1, 'Sem raça definida'),
(2, 'Persa'), (2, 'Siamês'), (2, 'Maine Coon'), (2, 'Ragdoll'),
(2, 'British Shorthair'), (2, 'Angorá Turco'), (2, 'Sem raça definida');

-- Especialidades
INSERT IGNORE INTO especialidades (id, nome) VALUES
(1, 'Clínica geral'), (2, 'Cardiologia'), (3, 'Dermatologia'), (4, 'Ortopedia'),
(5, 'Obstetrícia'), (6, 'Exames de imagem'), (7, 'Cirurgia');

-- Veterinário inicial
INSERT IGNORE INTO veterinarios (nome, cpf, crmv, especialidade_id, cargo, telefone, email, ativo) VALUES
('Dr. Carlos PetCare', '168.995.350-09', 'CRMV-SP-12345', 1, 'Médico veterinário', '(11) 4000-1234', 'veterinario@petcare.com.br', 1);

-- Vacinas
INSERT IGNORE INTO vacinas (id, nome, fabricante, tipo) VALUES
(1, 'V10', 'Fabricante genérico', 'Canina'),
(2, 'V8', 'Fabricante genérico', 'Canina'),
(3, 'Antirrábica', 'Fabricante genérico', 'Canina e felina'),
(4, 'Gripe canina', 'Fabricante genérico', 'Canina'),
(5, 'Giárdia', 'Fabricante genérico', 'Canina'),
(6, 'Quádrupla felina', 'Fabricante genérico', 'Felina'),
(7, 'Quíntupla felina', 'Fabricante genérico', 'Felina'),
(8, 'Outras vacinas', 'Fabricante genérico', 'Outros');

-- Categorias de produtos
INSERT IGNORE INTO categorias (id, nome) VALUES
(1, 'Medicamentos'), (2, 'Vacinas'), (3, 'Alimentos'), (4, 'Higiene'), (5, 'Material cirúrgico'), (6, 'Outros');

-- Serviços
INSERT IGNORE INTO servicos (id, nome, descricao, preco, duracao_minutos) VALUES
(1, 'Banho', 'Banho completo', 0.00, 60),
(2, 'Tosa', 'Tosa higiênica e estética', 0.00, 60),
(3, 'Corte de unhas', 'Corte e lixamento de unhas', 0.00, 20),
(4, 'Higienização', 'Limpeza de ouvidos e glândulas', 0.00, 30),
(5, 'Hidratação', 'Hidratação de pelagem', 0.00, 40);

-- Configurações
INSERT IGNORE INTO configuracoes (chave, valor) VALUES
('clinica_nome', 'PetCare'),
('slogan', 'Cuidando de quem faz parte da sua família.'),
('cnpj', '00.000.000/0001-00'),
('endereco', 'Rua Exemplo, 100 - Centro'),
('telefone', '(11) 4000-0000'),
('whatsapp', '(11) 90000-0000'),
('email', 'contato@petcare.com.br'),
('horario_funcionamento', 'Seg-Sex 08:00-18:00 | Sáb 08:00-12:00'),
('moeda', 'BRL'),
('fuso_horario', 'America/Sao_Paulo'),
('logo', ''),
('email_integracao', '0'),
('whatsapp_integracao', '0'),
('retencao_dados_anos', '5'),
('backup_automatico', '1'),
('licenca_dias_iniciais', '30'),
('licenca_avisos', '10,5,3,1,0');

INSERT INTO clinicas (id, codigo, nome, responsavel, cnpj, email, telefone, local_instalacao, ativo) VALUES
(1, 'PC-LOCAL-001', 'PetCare', 'Administrador PetCare', '00.000.000/0001-00', 'contato@petcare.com.br', '(11) 4000-0000', 1, 1)
ON DUPLICATE KEY UPDATE local_instalacao = 1, ativo = 1;

INSERT IGNORE INTO licencas (id, clinica_id, codigo_licenca, modalidade, dias_contratados, status, licenca_permanente) VALUES
(1, 1, 'LIC-PC-000001', '30_dias', 30, 'inativa', 0);

-- Permissões do administrador: todos os módulos e ações
INSERT IGNORE INTO permissoes (perfil_id, modulo, acao, created_at)
SELECT 1, m.modulo, a.acao, NOW()
FROM (
    SELECT 'dashboard' AS modulo UNION ALL SELECT 'clientes' UNION ALL SELECT 'animais' UNION ALL
    SELECT 'agenda' UNION ALL SELECT 'atendimentos' UNION ALL SELECT 'prontuarios' UNION ALL
    SELECT 'vacinas' UNION ALL SELECT 'receitas' UNION ALL SELECT 'exames' UNION ALL
    SELECT 'cirurgias' UNION ALL SELECT 'internacoes' UNION ALL SELECT 'farmacia' UNION ALL
    SELECT 'financeiro' UNION ALL SELECT 'compras' UNION ALL SELECT 'banho_tosa' UNION ALL
    SELECT 'usuarios' UNION ALL SELECT 'relatorios' UNION ALL SELECT 'notificacoes' UNION ALL
    SELECT 'configuracoes' UNION ALL SELECT 'portal'
) AS m
CROSS JOIN (
    SELECT 'view' AS acao UNION ALL SELECT 'create' UNION ALL SELECT 'edit' UNION ALL
    SELECT 'delete' UNION ALL SELECT 'print' UNION ALL SELECT 'approve'
) AS a;

INSERT IGNORE INTO permissoes (perfil_id, modulo, acao, created_at)
SELECT 9, m.modulo, a.acao, NOW()
FROM (
    SELECT 'dashboard' AS modulo UNION ALL SELECT 'clientes' UNION ALL SELECT 'animais' UNION ALL
    SELECT 'agenda' UNION ALL SELECT 'atendimentos' UNION ALL SELECT 'prontuarios' UNION ALL
    SELECT 'vacinas' UNION ALL SELECT 'receitas' UNION ALL SELECT 'exames' UNION ALL
    SELECT 'cirurgias' UNION ALL SELECT 'internacoes' UNION ALL SELECT 'farmacia' UNION ALL
    SELECT 'financeiro' UNION ALL SELECT 'compras' UNION ALL SELECT 'banho_tosa' UNION ALL
    SELECT 'usuarios' UNION ALL SELECT 'relatorios' UNION ALL SELECT 'notificacoes' UNION ALL
    SELECT 'configuracoes' UNION ALL SELECT 'portal'
) AS m
CROSS JOIN (
    SELECT 'view' AS acao UNION ALL SELECT 'create' UNION ALL SELECT 'edit' UNION ALL
    SELECT 'delete' UNION ALL SELECT 'print' UNION ALL SELECT 'approve'
) AS a;

-- Permissões dos demais perfis (view/create/edit)
INSERT IGNORE INTO permissoes (perfil_id, modulo, acao, created_at)
SELECT p.perfil_id, p.modulo, a.acao, NOW()
FROM (
    SELECT 2 AS perfil_id, 'atendimentos' AS modulo UNION ALL SELECT 2, 'prontuarios' UNION ALL
    SELECT 2, 'vacinas' UNION ALL SELECT 2, 'receitas' UNION ALL SELECT 2, 'exames' UNION ALL
    SELECT 2, 'cirurgias' UNION ALL SELECT 2, 'internacoes' UNION ALL
    SELECT 3, 'clientes' UNION ALL SELECT 3, 'animais' UNION ALL SELECT 3, 'agenda' UNION ALL
    SELECT 4, 'financeiro' UNION ALL SELECT 4, 'relatorios' UNION ALL
    SELECT 5, 'farmacia' UNION ALL SELECT 5, 'compras' UNION ALL
    SELECT 6, 'vacinas' UNION ALL SELECT 6, 'exames' UNION ALL
    SELECT 7, 'banho_tosa'
) AS p
CROSS JOIN (
    SELECT 'view' AS acao UNION ALL SELECT 'create' UNION ALL SELECT 'edit'
) AS a;

-- Permissões específicas
INSERT IGNORE INTO permissoes (perfil_id, modulo, acao, created_at) VALUES
(4, 'financeiro', 'approve', NOW()),
(4, 'relatorios', 'print', NOW()),
(8, 'portal', 'view', NOW());

-- Importação concluída.
