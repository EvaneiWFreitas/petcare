<?php

$title = $title ?? 'Cliente';
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'clientes.store';
$cancelRoute = $cancelRoute ?? 'clientes.index';
$submitLabel = $submitLabel ?? 'Salvar';
$enctype = null;

$ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

$fields = [];
if (!empty($values['id'])) {
    $fields['id'] = ['label' => 'ID', 'type' => 'text', 'rules' => '', 'col' => 'd-none'];
}
$fields += [
    'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4', 'help' => 'Gerado automaticamente.'],
    'nome' => ['label' => 'Nome completo', 'type' => 'text', 'rules' => 'required|min:3|max:180', 'col' => 'col-md-8'],
    'cpf' => ['label' => 'CPF', 'type' => 'text', 'rules' => 'nullable|cpf', 'col' => 'col-md-3', 'placeholder' => '000.000.000-00'],
    'rg' => ['label' => 'RG', 'type' => 'text', 'rules' => 'nullable|max:20', 'col' => 'col-md-3'],
    'data_nascimento' => ['label' => 'Data de nascimento', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-3'],
    'sexo' => ['label' => 'Sexo', 'type' => 'select', 'rules' => 'nullable|in:M,F,Outro', 'options' => ['M' => 'Masculino', 'F' => 'Feminino', 'Outro' => 'Outro'], 'col' => 'col-md-3'],
    'telefone' => ['label' => 'Telefone', 'type' => 'text', 'rules' => 'nullable|max:20', 'col' => 'col-md-4'],
    'whatsapp' => ['label' => 'WhatsApp', 'type' => 'text', 'rules' => 'nullable|max:20', 'col' => 'col-md-4'],
    'email' => ['label' => 'E-mail', 'type' => 'email', 'rules' => 'nullable|email|max:255', 'col' => 'col-md-4'],
    'cep' => ['label' => 'CEP', 'type' => 'text', 'rules' => 'nullable|max:10', 'col' => 'col-md-3', 'placeholder' => '00000-000'],
    'endereco' => ['label' => 'Endereço', 'type' => 'text', 'rules' => 'nullable|max:255', 'col' => 'col-md-9'],
    'numero' => ['label' => 'Número', 'type' => 'text', 'rules' => 'nullable|max:20', 'col' => 'col-md-2'],
    'complemento' => ['label' => 'Complemento', 'type' => 'text', 'rules' => 'nullable|max:120', 'col' => 'col-md-4'],
    'bairro' => ['label' => 'Bairro', 'type' => 'text', 'rules' => 'nullable|max:120', 'col' => 'col-md-6'],
    'cidade' => ['label' => 'Cidade', 'type' => 'text', 'rules' => 'nullable|max:120', 'col' => 'col-md-5'],
    'estado' => ['label' => 'Estado', 'type' => 'select', 'rules' => 'nullable|max:2', 'options' => array_combine($ufs, $ufs), 'col' => 'col-md-3'],
    'observacoes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-12', 'rows' => 4],
    'ativo' => ['label' => 'Ativo', 'type' => 'checkbox', 'rules' => '', 'col' => 'col-md-6'],
];

require __DIR__ . '/../common/form.php';
