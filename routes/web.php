<?php

declare(strict_types=1);

$routes = [
    'site.home' => ['method' => 'GET', 'controller' => 'SiteController@home', 'auth' => false],
    'site.profissional' => ['method' => 'GET', 'controller' => 'SiteController@profissional', 'auth' => false],
    'home' => ['method' => 'GET', 'controller' => 'SiteController@home', 'auth' => false],
    'profissional' => ['method' => 'GET', 'controller' => 'SiteController@profissional', 'auth' => false],

    'auth.login' => ['method' => 'GET', 'controller' => 'AuthController@login', 'auth' => false],
    'auth.authenticate' => ['method' => 'POST', 'controller' => 'AuthController@authenticate', 'auth' => false],
    'auth.logout' => ['method' => 'POST', 'controller' => 'AuthController@logout', 'auth' => true],
    'auth.change-password' => ['method' => 'GET', 'controller' => 'AuthController@changePassword', 'auth' => true],
    'auth.update-password' => ['method' => 'POST', 'controller' => 'AuthController@updatePassword', 'auth' => true],

    'dashboard.index' => ['method' => 'GET', 'controller' => 'DashboardController@index', 'auth' => true],

    'clientes.index' => ['method' => 'GET', 'controller' => 'ClienteController@index', 'auth' => true, 'permission' => ['clientes', 'view']],
    'clientes.create' => ['method' => 'GET', 'controller' => 'ClienteController@create', 'auth' => true, 'permission' => ['clientes', 'create']],
    'clientes.store' => ['method' => 'POST', 'controller' => 'ClienteController@store', 'auth' => true, 'permission' => ['clientes', 'create']],
    'clientes.show' => ['method' => 'GET', 'controller' => 'ClienteController@show', 'auth' => true, 'permission' => ['clientes', 'view']],
    'clientes.edit' => ['method' => 'GET', 'controller' => 'ClienteController@edit', 'auth' => true, 'permission' => ['clientes', 'edit']],
    'clientes.update' => ['method' => 'POST', 'controller' => 'ClienteController@update', 'auth' => true, 'permission' => ['clientes', 'edit']],
    'clientes.destroy' => ['method' => 'POST', 'controller' => 'ClienteController@destroy', 'auth' => true, 'permission' => ['clientes', 'delete']],

    'animais.index' => ['method' => 'GET', 'controller' => 'AnimalController@index', 'auth' => true, 'permission' => ['animais', 'view']],
    'animais.create' => ['method' => 'GET', 'controller' => 'AnimalController@create', 'auth' => true, 'permission' => ['animais', 'create']],
    'animais.store' => ['method' => 'POST', 'controller' => 'AnimalController@store', 'auth' => true, 'permission' => ['animais', 'create']],
    'animais.show' => ['method' => 'GET', 'controller' => 'AnimalController@show', 'auth' => true, 'permission' => ['animais', 'view']],
    'animais.edit' => ['method' => 'GET', 'controller' => 'AnimalController@edit', 'auth' => true, 'permission' => ['animais', 'edit']],
    'animais.update' => ['method' => 'POST', 'controller' => 'AnimalController@update', 'auth' => true, 'permission' => ['animais', 'edit']],
    'animais.destroy' => ['method' => 'POST', 'controller' => 'AnimalController@destroy', 'auth' => true, 'permission' => ['animais', 'delete']],

    'agenda.index' => ['method' => 'GET', 'controller' => 'AgendaController@index', 'auth' => true, 'permission' => ['agenda', 'view']],
    'agenda.create' => ['method' => 'GET', 'controller' => 'AgendaController@create', 'auth' => true, 'permission' => ['agenda', 'create']],
    'agenda.store' => ['method' => 'POST', 'controller' => 'AgendaController@store', 'auth' => true, 'permission' => ['agenda', 'create']],
    'agenda.edit' => ['method' => 'GET', 'controller' => 'AgendaController@edit', 'auth' => true, 'permission' => ['agenda', 'edit']],
    'agenda.update' => ['method' => 'POST', 'controller' => 'AgendaController@update', 'auth' => true, 'permission' => ['agenda', 'edit']],
    'agenda.destroy' => ['method' => 'POST', 'controller' => 'AgendaController@destroy', 'auth' => true, 'permission' => ['agenda', 'delete']],
    'agenda.delete' => ['method' => 'POST', 'controller' => 'AgendaController@delete', 'auth' => true, 'permission' => ['agenda', 'delete']],
    'agenda.events' => ['method' => 'GET', 'controller' => 'AgendaController@events', 'auth' => true, 'permission' => ['agenda', 'view']],

    'atendimentos.index' => ['method' => 'GET', 'controller' => 'AtendimentoController@index', 'auth' => true, 'permission' => ['atendimentos', 'view']],
    'atendimentos.create' => ['method' => 'GET', 'controller' => 'AtendimentoController@create', 'auth' => true, 'permission' => ['atendimentos', 'create']],
    'atendimentos.store' => ['method' => 'POST', 'controller' => 'AtendimentoController@store', 'auth' => true, 'permission' => ['atendimentos', 'create']],
    'atendimentos.show' => ['method' => 'GET', 'controller' => 'AtendimentoController@show', 'auth' => true, 'permission' => ['atendimentos', 'view']],
    'atendimentos.edit' => ['method' => 'GET', 'controller' => 'AtendimentoController@edit', 'auth' => true, 'permission' => ['atendimentos', 'edit']],
    'atendimentos.update' => ['method' => 'POST', 'controller' => 'AtendimentoController@update', 'auth' => true, 'permission' => ['atendimentos', 'edit']],
    'atendimentos.destroy' => ['method' => 'POST', 'controller' => 'AtendimentoController@destroy', 'auth' => true, 'permission' => ['atendimentos', 'delete']],

    'prontuarios.index' => ['method' => 'GET', 'controller' => 'ProntuarioController@index', 'auth' => true, 'permission' => ['prontuarios', 'view']],
    'prontuarios.create' => ['method' => 'GET', 'controller' => 'ProntuarioController@create', 'auth' => true, 'permission' => ['prontuarios', 'create']],
    'prontuarios.store' => ['method' => 'POST', 'controller' => 'ProntuarioController@store', 'auth' => true, 'permission' => ['prontuarios', 'create']],
    'prontuarios.show' => ['method' => 'GET', 'controller' => 'ProntuarioController@show', 'auth' => true, 'permission' => ['prontuarios', 'view']],
    'prontuarios.edit' => ['method' => 'GET', 'controller' => 'ProntuarioController@edit', 'auth' => true, 'permission' => ['prontuarios', 'edit']],
    'prontuarios.update' => ['method' => 'POST', 'controller' => 'ProntuarioController@update', 'auth' => true, 'permission' => ['prontuarios', 'edit']],
    'prontuarios.destroy' => ['method' => 'POST', 'controller' => 'ProntuarioController@destroy', 'auth' => true, 'permission' => ['prontuarios', 'delete']],

    'vacinas.index' => ['method' => 'GET', 'controller' => 'VacinacaoController@index', 'auth' => true, 'permission' => ['vacinas', 'view']],
    'vacinas.create' => ['method' => 'GET', 'controller' => 'VacinacaoController@create', 'auth' => true, 'permission' => ['vacinas', 'create']],
    'vacinas.store' => ['method' => 'POST', 'controller' => 'VacinacaoController@store', 'auth' => true, 'permission' => ['vacinas', 'create']],
    'vacinas.edit' => ['method' => 'GET', 'controller' => 'VacinacaoController@edit', 'auth' => true, 'permission' => ['vacinas', 'edit']],
    'vacinas.update' => ['method' => 'POST', 'controller' => 'VacinacaoController@update', 'auth' => true, 'permission' => ['vacinas', 'edit']],
    'vacinas.carteira' => ['method' => 'GET', 'controller' => 'VacinacaoController@carteira', 'auth' => true, 'permission' => ['vacinas', 'print']],
    'vacinas.print' => ['method' => 'GET', 'controller' => 'VacinacaoController@printCarteira', 'auth' => true, 'permission' => ['vacinas', 'print']],
    'vacinas.destroy' => ['method' => 'POST', 'controller' => 'VacinacaoController@destroy', 'auth' => true, 'permission' => ['vacinas', 'delete']],

    'receitas.index' => ['method' => 'GET', 'controller' => 'ReceitaController@index', 'auth' => true, 'permission' => ['receitas', 'view']],
    'receitas.create' => ['method' => 'GET', 'controller' => 'ReceitaController@create', 'auth' => true, 'permission' => ['receitas', 'create']],
    'receitas.store' => ['method' => 'POST', 'controller' => 'ReceitaController@store', 'auth' => true, 'permission' => ['receitas', 'create']],
    'receitas.edit' => ['method' => 'GET', 'controller' => 'ReceitaController@edit', 'auth' => true, 'permission' => ['receitas', 'edit']],
    'receitas.update' => ['method' => 'POST', 'controller' => 'ReceitaController@update', 'auth' => true, 'permission' => ['receitas', 'edit']],
    'receitas.show' => ['method' => 'GET', 'controller' => 'ReceitaController@show', 'auth' => true, 'permission' => ['receitas', 'view']],
    'receitas.print' => ['method' => 'GET', 'controller' => 'ReceitaController@printRecipe', 'auth' => true, 'permission' => ['receitas', 'print']],
    'receitas.destroy' => ['method' => 'POST', 'controller' => 'ReceitaController@destroy', 'auth' => true, 'permission' => ['receitas', 'delete']],

    'exames.index' => ['method' => 'GET', 'controller' => 'ExameController@index', 'auth' => true, 'permission' => ['exames', 'view']],
    'exames.create' => ['method' => 'GET', 'controller' => 'ExameController@create', 'auth' => true, 'permission' => ['exames', 'create']],
    'exames.store' => ['method' => 'POST', 'controller' => 'ExameController@store', 'auth' => true, 'permission' => ['exames', 'create']],
    'exames.show' => ['method' => 'GET', 'controller' => 'ExameController@show', 'auth' => true, 'permission' => ['exames', 'view']],
    'exames.result' => ['method' => 'POST', 'controller' => 'ExameController@saveResult', 'auth' => true, 'permission' => ['exames', 'edit']],
    'exames.destroy' => ['method' => 'POST', 'controller' => 'ExameController@destroy', 'auth' => true, 'permission' => ['exames', 'delete']],

    'cirurgias.index' => ['method' => 'GET', 'controller' => 'CirurgiaController@index', 'auth' => true, 'permission' => ['cirurgias', 'view']],
    'cirurgias.create' => ['method' => 'GET', 'controller' => 'CirurgiaController@create', 'auth' => true, 'permission' => ['cirurgias', 'create']],
    'cirurgias.store' => ['method' => 'POST', 'controller' => 'CirurgiaController@store', 'auth' => true, 'permission' => ['cirurgias', 'create']],
    'cirurgias.show' => ['method' => 'GET', 'controller' => 'CirurgiaController@show', 'auth' => true, 'permission' => ['cirurgias', 'view']],
    'cirurgias.update' => ['method' => 'POST', 'controller' => 'CirurgiaController@update', 'auth' => true, 'permission' => ['cirurgias', 'edit']],
    'cirurgias.destroy' => ['method' => 'POST', 'controller' => 'CirurgiaController@destroy', 'auth' => true, 'permission' => ['cirurgias', 'delete']],

    'internacoes.index' => ['method' => 'GET', 'controller' => 'InternacaoController@index', 'auth' => true, 'permission' => ['internacoes', 'view']],
    'internacoes.create' => ['method' => 'GET', 'controller' => 'InternacaoController@create', 'auth' => true, 'permission' => ['internacoes', 'create']],
    'internacoes.store' => ['method' => 'POST', 'controller' => 'InternacaoController@store', 'auth' => true, 'permission' => ['internacoes', 'create']],
    'internacoes.show' => ['method' => 'GET', 'controller' => 'InternacaoController@show', 'auth' => true, 'permission' => ['internacoes', 'view']],
    'internacoes.evolve' => ['method' => 'POST', 'controller' => 'InternacaoController@evolve', 'auth' => true, 'permission' => ['internacoes', 'edit']],
    'internacoes.discharge' => ['method' => 'POST', 'controller' => 'InternacaoController@discharge', 'auth' => true, 'permission' => ['internacoes', 'approve']],
    'internacoes.destroy' => ['method' => 'POST', 'controller' => 'InternacaoController@destroy', 'auth' => true, 'permission' => ['internacoes', 'delete']],

    'estoque.index' => ['method' => 'GET', 'controller' => 'EstoqueController@index', 'auth' => true, 'permission' => ['farmacia', 'view']],
    'estoque.create' => ['method' => 'GET', 'controller' => 'EstoqueController@create', 'auth' => true, 'permission' => ['farmacia', 'create']],
    'estoque.store' => ['method' => 'POST', 'controller' => 'EstoqueController@store', 'auth' => true, 'permission' => ['farmacia', 'create']],
    'estoque.edit' => ['method' => 'GET', 'controller' => 'EstoqueController@edit', 'auth' => true, 'permission' => ['farmacia', 'edit']],
    'estoque.update' => ['method' => 'POST', 'controller' => 'EstoqueController@update', 'auth' => true, 'permission' => ['farmacia', 'edit']],
    'estoque.movements' => ['method' => 'GET', 'controller' => 'EstoqueController@movements', 'auth' => true, 'permission' => ['farmacia', 'view']],
    'estoque.move' => ['method' => 'POST', 'controller' => 'EstoqueController@move', 'auth' => true, 'permission' => ['farmacia', 'edit']],
    'estoque.destroy' => ['method' => 'POST', 'controller' => 'EstoqueController@destroy', 'auth' => true, 'permission' => ['farmacia', 'delete']],

    'compras.index' => ['method' => 'GET', 'controller' => 'CompraController@index', 'auth' => true, 'permission' => ['compras', 'view']],
    'compras.create' => ['method' => 'GET', 'controller' => 'CompraController@create', 'auth' => true, 'permission' => ['compras', 'create']],
    'compras.store' => ['method' => 'POST', 'controller' => 'CompraController@store', 'auth' => true, 'permission' => ['compras', 'create']],
    'compras.receive' => ['method' => 'POST', 'controller' => 'CompraController@receive', 'auth' => true, 'permission' => ['compras', 'approve']],
    'compras.destroy' => ['method' => 'POST', 'controller' => 'CompraController@destroy', 'auth' => true, 'permission' => ['compras', 'delete']],

    'financeiro.index' => ['method' => 'GET', 'controller' => 'FinanceiroController@index', 'auth' => true, 'permission' => ['financeiro', 'view']],
    'financeiro.receber' => ['method' => 'GET', 'controller' => 'FinanceiroController@receber', 'auth' => true, 'permission' => ['financeiro', 'view']],
    'financeiro.pagar' => ['method' => 'GET', 'controller' => 'FinanceiroController@pagar', 'auth' => true, 'permission' => ['financeiro', 'view']],
    'financeiro.create-receber' => ['method' => 'GET', 'controller' => 'FinanceiroController@createReceber', 'auth' => true, 'permission' => ['financeiro', 'create']],
    'financeiro.store-receber' => ['method' => 'POST', 'controller' => 'FinanceiroController@storeReceber', 'auth' => true, 'permission' => ['financeiro', 'create']],
    'financeiro.edit-receber' => ['method' => 'GET', 'controller' => 'FinanceiroController@editReceber', 'auth' => true, 'permission' => ['financeiro', 'edit']],
    'financeiro.update-receber' => ['method' => 'POST', 'controller' => 'FinanceiroController@updateReceber', 'auth' => true, 'permission' => ['financeiro', 'edit']],
    'financeiro.create-pagar' => ['method' => 'GET', 'controller' => 'FinanceiroController@createPagar', 'auth' => true, 'permission' => ['financeiro', 'create']],
    'financeiro.store-pagar' => ['method' => 'POST', 'controller' => 'FinanceiroController@storePagar', 'auth' => true, 'permission' => ['financeiro', 'create']],
    'financeiro.pay' => ['method' => 'POST', 'controller' => 'FinanceiroController@pay', 'auth' => true, 'permission' => ['financeiro', 'edit']],
    'financeiro.destroy-receber' => ['method' => 'POST', 'controller' => 'FinanceiroController@destroyReceber', 'auth' => true, 'permission' => ['financeiro', 'delete']],
    'financeiro.destroy-pagar' => ['method' => 'POST', 'controller' => 'FinanceiroController@destroyPagar', 'auth' => true, 'permission' => ['financeiro', 'delete']],
    'financeiro.fluxo' => ['method' => 'GET', 'controller' => 'FinanceiroController@fluxo', 'auth' => true, 'permission' => ['financeiro', 'view']],

    'banho_tosa.index' => ['method' => 'GET', 'controller' => 'BanhoTosaController@index', 'auth' => true, 'permission' => ['banho_tosa', 'view']],
    'banho_tosa.create' => ['method' => 'GET', 'controller' => 'BanhoTosaController@create', 'auth' => true, 'permission' => ['banho_tosa', 'create']],
    'banho_tosa.store' => ['method' => 'POST', 'controller' => 'BanhoTosaController@store', 'auth' => true, 'permission' => ['banho_tosa', 'create']],
    'banho_tosa.show' => ['method' => 'GET', 'controller' => 'BanhoTosaController@show', 'auth' => true, 'permission' => ['banho_tosa', 'view']],
    'banho_tosa.destroy' => ['method' => 'POST', 'controller' => 'BanhoTosaController@destroy', 'auth' => true, 'permission' => ['banho_tosa', 'delete']],

    'veterinarios.index' => ['method' => 'GET', 'controller' => 'ProfissionalController@index', 'auth' => true, 'permission' => ['usuarios', 'view']],
    'veterinarios.create' => ['method' => 'GET', 'controller' => 'ProfissionalController@create', 'auth' => true, 'permission' => ['usuarios', 'create']],
    'veterinarios.store' => ['method' => 'POST', 'controller' => 'ProfissionalController@store', 'auth' => true, 'permission' => ['usuarios', 'create']],
    'veterinarios.edit' => ['method' => 'GET', 'controller' => 'ProfissionalController@edit', 'auth' => true, 'permission' => ['usuarios', 'edit']],
    'veterinarios.update' => ['method' => 'POST', 'controller' => 'ProfissionalController@update', 'auth' => true, 'permission' => ['usuarios', 'edit']],
    'veterinarios.destroy' => ['method' => 'POST', 'controller' => 'ProfissionalController@destroy', 'auth' => true, 'permission' => ['usuarios', 'delete']],

    'usuarios.index' => ['method' => 'GET', 'controller' => 'UsuarioController@index', 'auth' => true, 'permission' => ['usuarios', 'view']],
    'usuarios.create' => ['method' => 'GET', 'controller' => 'UsuarioController@create', 'auth' => true, 'permission' => ['usuarios', 'create']],
    'usuarios.store' => ['method' => 'POST', 'controller' => 'UsuarioController@store', 'auth' => true, 'permission' => ['usuarios', 'create']],
    'usuarios.edit' => ['method' => 'GET', 'controller' => 'UsuarioController@edit', 'auth' => true, 'permission' => ['usuarios', 'edit']],
    'usuarios.update' => ['method' => 'POST', 'controller' => 'UsuarioController@update', 'auth' => true, 'permission' => ['usuarios', 'edit']],
    'usuarios.permissions' => ['method' => 'GET', 'controller' => 'UsuarioController@permissions', 'auth' => true, 'permission' => ['usuarios', 'approve']],
    'usuarios.save-permissions' => ['method' => 'POST', 'controller' => 'UsuarioController@savePermissions', 'auth' => true, 'permission' => ['usuarios', 'approve']],
    'usuarios.destroy' => ['method' => 'POST', 'controller' => 'UsuarioController@destroy', 'auth' => true, 'permission' => ['usuarios', 'delete']],

    'relatorios.index' => ['method' => 'GET', 'controller' => 'RelatorioController@index', 'auth' => true, 'permission' => ['relatorios', 'view']],
    'relatorios.generate' => ['method' => 'GET', 'controller' => 'RelatorioController@generate', 'auth' => true, 'permission' => ['relatorios', 'print']],

    'notificacoes.index' => ['method' => 'GET', 'controller' => 'NotificacaoController@index', 'auth' => true],
    'notificacoes.read' => ['method' => 'POST', 'controller' => 'NotificacaoController@markRead', 'auth' => true],

    'config.index' => ['method' => 'GET', 'controller' => 'ConfigController@index', 'auth' => true, 'permission' => ['configuracoes', 'view']],
    'config.update' => ['method' => 'POST', 'controller' => 'ConfigController@update', 'auth' => true, 'permission' => ['configuracoes', 'edit']],
    'config.backup' => ['method' => 'GET', 'controller' => 'ConfigController@backup', 'auth' => true, 'permission' => ['configuracoes', 'approve']],

    'licenca.index' => ['method' => 'GET', 'controller' => 'LicencaController@index', 'auth' => true, 'superadmin' => true],
    'licenca.configuracao' => ['method' => 'GET', 'controller' => 'LicencaController@configuracao', 'auth' => true, 'superadmin' => true],
    'licenca.salvar-configuracao' => ['method' => 'POST', 'controller' => 'LicencaController@salvarConfiguracao', 'auth' => true, 'superadmin' => true],
    'licenca.salvar-data-bloqueio' => ['method' => 'POST', 'controller' => 'LicencaController@salvarDataBloqueio', 'auth' => true, 'superadmin' => true],
    'licenca.prorrogacao' => ['method' => 'GET', 'controller' => 'LicencaController@prorrogacao', 'auth' => true, 'superadmin' => true],
    'licenca.prorrogar' => ['method' => 'POST', 'controller' => 'LicencaController@prorrogar', 'auth' => true, 'superadmin' => true],
    'licenca.ativar' => ['method' => 'POST', 'controller' => 'LicencaController@ativar', 'auth' => true, 'superadmin' => true],
    'licenca.ilimitadas' => ['method' => 'GET', 'controller' => 'LicencaController@ilimitadas', 'auth' => true, 'superadmin' => true],
    'licenca.ativar-permanente' => ['method' => 'POST', 'controller' => 'LicencaController@ativarPermanente', 'auth' => true, 'superadmin' => true],
    'licenca.desativar-permanente' => ['method' => 'POST', 'controller' => 'LicencaController@desativarPermanente', 'auth' => true, 'superadmin' => true],
    'licenca.historico' => ['method' => 'GET', 'controller' => 'LicencaController@historico', 'auth' => true, 'superadmin' => true],
    'licenca.situacao' => ['method' => 'GET', 'controller' => 'LicencaController@situacao', 'auth' => true, 'superadmin' => true],
    'licenca.expirada' => ['method' => 'GET', 'controller' => 'LicencaController@expirada', 'auth' => false],

    'anexos.store' => ['method' => 'POST', 'controller' => 'AnexoController@store', 'auth' => true],
    'anexos.download' => ['method' => 'GET', 'controller' => 'AnexoController@download', 'auth' => true],

    'portal.login' => ['method' => 'GET', 'controller' => 'PortalController@login', 'auth' => false],
    'portal.authenticate' => ['method' => 'POST', 'controller' => 'PortalController@authenticate', 'auth' => false],
    'portal.logout' => ['method' => 'POST', 'controller' => 'PortalController@logout', 'auth' => false],
    'portal.index' => ['method' => 'GET', 'controller' => 'PortalController@index', 'auth' => false],
    'portal.animais' => ['method' => 'GET', 'controller' => 'PortalController@animais', 'auth' => false],
    'portal.agendamentos' => ['method' => 'GET', 'controller' => 'PortalController@agendamentos', 'auth' => false],
    'portal.solicitar' => ['method' => 'GET', 'controller' => 'PortalController@solicitar', 'auth' => false],
    'portal.store-agendamento' => ['method' => 'POST', 'controller' => 'PortalController@storeAgendamento', 'auth' => false],
    'portal.vacinas' => ['method' => 'GET', 'controller' => 'PortalController@vacinas', 'auth' => false],
    'portal.receitas' => ['method' => 'GET', 'controller' => 'PortalController@receitas', 'auth' => false],
    'portal.exames' => ['method' => 'GET', 'controller' => 'PortalController@exames', 'auth' => false],
    'portal.pagamentos' => ['method' => 'GET', 'controller' => 'PortalController@pagamentos', 'auth' => false],
    'portal.perfil' => ['method' => 'GET', 'controller' => 'PortalController@perfil', 'auth' => false],
    'portal.update-perfil' => ['method' => 'POST', 'controller' => 'PortalController@updatePerfil', 'auth' => false],
];

$crudModules = [
    'especies' => ['controller' => 'CrudController', 'module' => 'configuracoes', 'label' => 'Espécies'],
    'racas' => ['controller' => 'CrudController', 'module' => 'configuracoes', 'label' => 'Raças'],
    'especialidades' => ['controller' => 'CrudController', 'module' => 'configuracoes', 'label' => 'Especialidades'],
    'catalogo-vacinas' => ['controller' => 'CrudController', 'module' => 'configuracoes', 'label' => 'Catálogo de Vacinas'],
    'categorias' => ['controller' => 'CrudController', 'module' => 'configuracoes', 'label' => 'Categorias'],
    'fornecedores' => ['controller' => 'CrudController', 'module' => 'compras', 'label' => 'Fornecedores'],
    'servicos' => ['controller' => 'CrudController', 'module' => 'banho_tosa', 'label' => 'Serviços'],
    'site-servicos' => ['controller' => 'CrudController', 'module' => 'configuracoes', 'label' => 'Serviços do site'],
    'site-carrossel' => ['controller' => 'CrudController', 'module' => 'configuracoes', 'label' => 'Carrossel da p?gina inicial'],
];

foreach ($crudModules as $slug => $config) {
    $routes["crud.{$slug}.index"] = ['method' => 'GET', 'controller' => $config['controller'] . '@index', 'auth' => true, 'permission' => [$config['module'], 'view'], 'crud' => $slug];
    $routes["crud.{$slug}.create"] = ['method' => 'GET', 'controller' => $config['controller'] . '@create', 'auth' => true, 'permission' => [$config['module'], 'create'], 'crud' => $slug];
    $routes["crud.{$slug}.store"] = ['method' => 'POST', 'controller' => $config['controller'] . '@store', 'auth' => true, 'permission' => [$config['module'], 'create'], 'crud' => $slug];
    $routes["crud.{$slug}.edit"] = ['method' => 'GET', 'controller' => $config['controller'] . '@edit', 'auth' => true, 'permission' => [$config['module'], 'edit'], 'crud' => $slug];
    $routes["crud.{$slug}.update"] = ['method' => 'POST', 'controller' => $config['controller'] . '@update', 'auth' => true, 'permission' => [$config['module'], 'edit'], 'crud' => $slug];
    $routes["crud.{$slug}.destroy"] = ['method' => 'POST', 'controller' => $config['controller'] . '@destroy', 'auth' => true, 'permission' => [$config['module'], 'delete'], 'crud' => $slug];
}

return $routes;
