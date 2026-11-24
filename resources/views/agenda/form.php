<?php

$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? '';
$title = $title ?? 'Agendamento';
$cancelRoute = $cancelRoute ?? 'agenda.index';
$submitLabel = $submitLabel ?? 'Salvar';
$animaisPorCliente = $animaisPorCliente ?? [];

$animaisJson = json_encode(
    $animaisPorCliente,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
if ($animaisJson === false) {
    $animaisJson = '{}';
}

ob_start();
require __DIR__ . '/../common/form.php';
echo (string) ob_get_clean();
?>
<script>
(function () {
    var porCliente = <?= $animaisJson ?>;
    var cliente = document.getElementById('field_cliente_id');
    var animal = document.getElementById('field_animal_id');
    if (!cliente || !animal) {
        return;
    }
    function atualizarAnimais() {
        var selecionado = animal.value;
        animal.innerHTML = '';
        animal.options.add(new Option('Selecione...', ''));
        var lista = porCliente[cliente.value] || [];
        lista.forEach(function (item) {
            animal.options.add(new Option(item.nome, String(item.id), false, String(item.id) === String(selecionado)));
        });
        animal.value = selecionado;
    }
    cliente.addEventListener('change', atualizarAnimais);
    atualizarAnimais();
})();
</script>
