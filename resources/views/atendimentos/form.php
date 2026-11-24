<?php

$title = $title ?? 'Atendimento';
$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'atendimentos.store';
$cancelRoute = $cancelRoute ?? 'atendimentos.index';
$submitLabel = $submitLabel ?? 'Registrar atendimento';
$clienteNome = $clienteNome ?? '';
$enctype = $enctype ?? null;

ob_start();
require __DIR__ . '/../common/form.php';
$html = (string) ob_get_clean();
$html = str_replace(
    '<input type="text" name="field_data_atendimento" id="field_data_atendimento"',
    '<input type="datetime-local" name="field_data_atendimento" id="field_data_atendimento"',
    $html
);

$alert = '';
if ($clienteNome !== '') {
    $alert = '<div class="alert alert-info border-0 shadow-sm mb-4"><i class="fa-solid fa-circle-info me-2"></i>Tutor do agendamento: <strong>' . e($clienteNome) . '</strong></div>';
}
echo $alert . $html;
?>
<script>
(function () {
    var toggle = document.getElementById('field_gerar_prontuario');
    var campos = document.querySelectorAll('.clinical-field');
    if (!toggle) {
        return;
    }
    function sincronizar() {
        campos.forEach(function (campo) {
            campo.style.display = toggle.checked ? '' : 'none';
        });
    }
    toggle.addEventListener('change', sincronizar);
    sincronizar();
})();
</script>
