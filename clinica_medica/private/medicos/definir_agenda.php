<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

$medico_id = $_GET['medico_id'] ?? null;
$medico_nome = '';

if (!$medico_id) {
    die('Médico não especificado.');
}

// Busca o nome do médico
$sql_medico = "SELECT nome FROM medicos WHERE id = ?";
$stmt_medico = $conn->prepare($sql_medico);
$stmt_medico->bind_param("i", $medico_id);
$stmt_medico->execute();
$resultado_medico = $stmt_medico->get_result();
if ($medico = $resultado_medico->fetch_assoc()) {
    $medico_nome = htmlspecialchars($medico['nome']);
} else {
    die('Médico não encontrado.');
}
$stmt_medico->close();

// CORREÇÃO AQUI: Prioriza a data vinda da URL, senão usa a data atual
$data_agenda = $_GET['data'] ?? date('Y-m-d');
$limite_manha = 0;
$limite_tarde = 0;

// Busca a agenda existente para a data selecionada
$sql_agenda = "SELECT turno, limite_pacientes FROM agenda_medicos WHERE medico_id = ? AND data = ?";
$stmt_agenda = $conn->prepare($sql_agenda);
$stmt_agenda->bind_param("is", $medico_id, $data_agenda);
$stmt_agenda->execute();
$resultado_agenda = $stmt_agenda->get_result();

while ($row = $resultado_agenda->fetch_assoc()) {
    if ($row['turno'] == 'manha') {
        $limite_manha = $row['limite_pacientes'];
    } elseif ($row['turno'] == 'tarde') {
        $limite_tarde = $row['limite_pacientes'];
    }
}
$stmt_agenda->close();
?>

<div class="container mt-4">
    <h4>Definir Agenda para <?= $medico_nome ?></h4>
    <p class="text-muted">Defina a quantidade de pacientes que o(a) médico(a) pode atender por turno.</p>

    <form action="salvar_agenda.php" method="POST">
        <input type="hidden" name="medico_id" value="<?= $medico_id ?>">

        <div class="mb-3">
            <label for="data" class="form-label">Data</label>
            <input type="date" name="data" id="data" class="form-control" value="<?= $data_agenda ?>" required>
        </div>

        <div class="mb-3">
            <label for="limite_manha" class="form-label">Limite de pacientes - Manhã</label>
            <input type="number" name="limite_manha" id="limite_manha" class="form-control" value="<?= $limite_manha ?>" min="0" required>
        </div>

        <div class="mb-3">
            <label for="limite_tarde" class="form-label">Limite de pacientes - Tarde</label>
            <input type="number" name="limite_tarde" id="limite_tarde" class="form-control" value="<?= $limite_tarde ?>" min="0" required>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Agenda</button>
        <a href="listar.php" class="btn btn-secondary">Voltar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataInput = document.getElementById('data');
    const form = document.querySelector('form');

    // A cada mudança na data, recarrega a página com a nova data para buscar limites existentes
    dataInput.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('data', this.value);
        window.location.href = url.href;
    });
});
</script>