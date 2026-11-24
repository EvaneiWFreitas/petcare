<?php

$title = $title ?? 'Novo banho e tosa';
$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'banho_tosa.store';
$cancelRoute = $cancelRoute ?? 'banho_tosa.index';
$submitLabel = $submitLabel ?? 'Registrar serviço';
$servicosPrecos = $servicosPrecos ?? [];
$precosJson = json_encode($servicosPrecos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($precosJson === false) {
    $precosJson = '{}';
}

ob_start();
require __DIR__ . '/../common/form.php';
$html = (string) ob_get_clean();
$html = str_replace(
    '<input type="text" name="field_data_servico" id="field_data_servico"',
    '<input type="datetime-local" name="field_data_servico" id="field_data_servico"',
    $html
);
?>
<div id="servicosPrecos" data-precos="<?= e($precosJson) ?>" hidden></div>
<?= $html ?>
<script>
(function () {
    var servico = document.getElementById('field_servico_id');
    var valor = document.getElementById('field_valor');
    var precos = JSON.parse(document.getElementById('servicosPrecos').dataset.precos);
    if (!servico || !valor) {
        return;
    }
    servico.addEventListener('change', function () {
        var preco = precos[servico.value];
        if (preco !== undefined) {
            valor.value = Number(preco).toFixed(2);
        }
    });
})();
</script>
