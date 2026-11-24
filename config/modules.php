<?php

declare(strict_types=1);

return [
    'especies' => [
        'table' => 'especies',
        'label' => 'Espécie',
        'plural' => 'Espécies',
        'route' => 'crud.especies',
        'columns' => ['id', 'nome'],
        'search' => ['nome'],
        'order' => 'nome ASC',
        'fields' => [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|max:80|unique:especies,nome,id'],
        ],
    ],
    'racas' => [
        'table' => 'racas',
        'label' => 'Raça',
        'plural' => 'Raças',
        'route' => 'crud.racas',
        'columns' => ['id', 'especie_id', 'nome'],
        'search' => ['nome'],
        'order' => 'nome ASC',
        'fields' => [
            'especie_id' => ['label' => 'Espécie', 'type' => 'select', 'rules' => 'required|integer', 'options_query' => 'SELECT id, nome FROM especies ORDER BY nome'],
            'nome' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|max:80'],
        ],
    ],
    'especialidades' => [
        'table' => 'especialidades',
        'label' => 'Especialidade',
        'plural' => 'Especialidades',
        'route' => 'crud.especialidades',
        'columns' => ['id', 'nome'],
        'search' => ['nome'],
        'order' => 'nome ASC',
        'fields' => [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|max:100|unique:especialidades,nome,id'],
        ],
    ],
    'catalogo-vacinas' => [
        'table' => 'vacinas',
        'label' => 'Vacina',
        'plural' => 'Catálogo de Vacinas',
        'route' => 'crud.catalogo-vacinas',
        'columns' => ['id', 'nome', 'fabricante', 'tipo'],
        'search' => ['nome', 'fabricante'],
        'order' => 'nome ASC',
        'fields' => [
            'nome' => ['label' => 'Nome da vacina', 'type' => 'text', 'rules' => 'required|max:100'],
            'fabricante' => ['label' => 'Fabricante', 'type' => 'text', 'rules' => 'nullable|max:100'],
            'tipo' => ['label' => 'Tipo', 'type' => 'text', 'rules' => 'nullable|max:60'],
        ],
    ],
    'categorias' => [
        'table' => 'categorias',
        'label' => 'Categoria',
        'plural' => 'Categorias',
        'route' => 'crud.categorias',
        'columns' => ['id', 'nome'],
        'search' => ['nome'],
        'order' => 'nome ASC',
        'fields' => [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|max:80|unique:categorias,nome,id'],
        ],
    ],
    'fornecedores' => [
        'table' => 'fornecedores',
        'label' => 'Fornecedor',
        'plural' => 'Fornecedores',
        'route' => 'crud.fornecedores',
        'columns' => ['id', 'nome', 'cnpj_cpf', 'telefone', 'email'],
        'search' => ['nome', 'cnpj_cpf'],
        'order' => 'nome ASC',
        'fields' => [
            'nome' => ['label' => 'Nome / Razão social', 'type' => 'text', 'rules' => 'required|max:150'],
            'cnpj_cpf' => ['label' => 'CNPJ/CPF', 'type' => 'text', 'rules' => 'nullable|cnpj|unique:fornecedores,cnpj_cpf,id'],
            'telefone' => ['label' => 'Telefone', 'type' => 'text', 'rules' => 'nullable|max:30'],
            'email' => ['label' => 'E-mail', 'type' => 'email', 'rules' => 'nullable|email|max:150'],
            'endereco' => ['label' => 'Endereço', 'type' => 'text', 'rules' => 'nullable|max:200'],
            'contato' => ['label' => 'Contato', 'type' => 'text', 'rules' => 'nullable|max:100'],
            'observacoes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable'],
            'ativo' => ['label' => 'Ativo', 'type' => 'checkbox', 'rules' => 'nullable'],
        ],
    ],
    'servicos' => [
        'table' => 'servicos',
        'label' => 'Serviço',
        'plural' => 'Serviços',
        'route' => 'crud.servicos',
        'columns' => ['id', 'nome', 'preco', 'duracao_minutos'],
        'search' => ['nome'],
        'order' => 'nome ASC',
        'fields' => [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|max:100'],
            'descricao' => ['label' => 'Descrição', 'type' => 'textarea', 'rules' => 'nullable'],
            'preco' => ['label' => 'Preço', 'type' => 'number', 'rules' => 'required|numeric'],
            'duracao_minutos' => ['label' => 'Duração (minutos)', 'type' => 'number', 'rules' => 'required|integer'],
            'ativo' => ['label' => 'Ativo', 'type' => 'checkbox', 'rules' => 'nullable'],
        ],
    ],
];
