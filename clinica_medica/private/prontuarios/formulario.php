<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
include_once "../includes/menu.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($nivel, ['administrador', 'medico'])) {
    die('Acesso negado.');
}

// Buscar médico vinculado ao usuário logado
$stmt_med = $conn->prepare("
    SELECT m.id 
    FROM medicos m
    INNER JOIN medico_usuario mu ON mu.medico_id = m.id
    WHERE mu.usuario_id = ?
");
$stmt_med->bind_param("i", $usuario_id);
$stmt_med->execute();
$result_med = $stmt_med->get_result();
$medico = $result_med->fetch_assoc();

if (!$medico) {
    die("Médico não encontrado para o usuário logado. Verifique vínculo na tabela medico_usuario.");
}

$medico_id = $medico['id'];

// Inicializa variáveis do formulário
$data_consulta = date('Y-m-d');
$paciente_id = '';
$consulta_id = '';
$queixa_principal = '';
$historia_doenca_atual = '';
$antecedentes_pessoais = '';
$antecedentes_familiares = '';
$exame_fisico = '';
$exames_complementares = '';
$diagnostico = '';
$prescricao = '';
$orientacoes = '';
$observacoes = '';

$erro = '';
$sucesso = '';

// Buscar lista de pacientes
$pacientes = $conn->query("SELECT id, nome FROM pacientes ORDER BY nome ASC");

// Buscar lista de consultas agendadas para o médico e pacientes, para poder vincular no prontuário
$consultas = [];
if ($nivel === 'medico') {
    $stmt_consultas = $conn->prepare("
        SELECT c.id, c.paciente_id, c.data_consulta, c.turno, pac.nome AS paciente_nome
        FROM consultas c
        JOIN pacientes pac ON c.paciente_id = pac.id
        WHERE c.medico_id = ? AND c.status = 'agendada'
        ORDER BY c.data_consulta, c.turno
    ");
    $stmt_consultas->bind_param("i", $medico_id);
    $stmt_consultas->execute();
    $result_consultas = $stmt_consultas->get_result();
    while ($row = $result_consultas->fetch_assoc()) {
        $consultas[] = $row;
    }
    $stmt_consultas->close();
} else {
    // Administrador pode ver todas consultas agendadas
    $result_consultas = $conn->query("
        SELECT c.id, c.paciente_id, c.data_consulta, c.turno, pac.nome AS paciente_nome
        FROM consultas c
        JOIN pacientes pac ON c.paciente_id = pac.id
        WHERE c.status = 'agendada'
        ORDER BY c.data_consulta, c.turno
    ");
    while ($row = $result_consultas->fetch_assoc()) {
        $consultas[] = $row;
    }
}

// Processar POST para inserir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_consulta_post = $_POST['data_consulta'] ?? '';
    $paciente_id_post = $_POST['paciente_id'] ?? '';
    $consulta_id = $_POST['consulta_id'] ?? '';
    $queixa_principal = $_POST['queixa_principal'] ?? '';
    $historia_doenca_atual = $_POST['historia_doenca_atual'] ?? '';
    $antecedentes_pessoais = $_POST['antecedentes_pessoais'] ?? '';
    $antecedentes_familiares = $_POST['antecedentes_familiares'] ?? '';
    $exame_fisico = $_POST['exame_fisico'] ?? '';
    $exames_complementares = $_POST['exames_complementares'] ?? '';
    $diagnostico = $_POST['diagnostico'] ?? '';
    $prescricao = $_POST['prescricao'] ?? '';
    $orientacoes = $_POST['orientacoes'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';

    if (!$data_consulta_post || !$paciente_id_post) {
        $erro = "Data da consulta e paciente são obrigatórios.";
    } elseif (!$consulta_id) {
        $erro = "Selecione a consulta vinculada para este prontuário.";
    } else {
        // Inserir prontuário vinculando o consulta_id recebido
        $stmt = $conn->prepare("
            INSERT INTO prontuarios 
            (consulta_id, medico_id, paciente_id, data_consulta, queixa_principal, historia_doenca_atual, antecedentes_pessoais, antecedentes_familiares, exame_fisico, exames_complementares, diagnostico, prescricao, orientacoes, observacoes, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "iiisssssssssss",
            $consulta_id,
            $medico_id,
            $paciente_id_post,
            $data_consulta_post,
            $queixa_principal,
            $historia_doenca_atual,
            $antecedentes_pessoais,
            $antecedentes_familiares,
            $exame_fisico,
            $exames_complementares,
            $diagnostico,
            $prescricao,
            $orientacoes,
            $observacoes
        );

        if ($stmt->execute()) {
            $sucesso = "Prontuário criado com sucesso e vinculado à consulta.";
            // Resetar campos
            $data_consulta = date('Y-m-d');
            $paciente_id = '';
            $consulta_id = '';
            $queixa_principal = '';
            $historia_doenca_atual = '';
            $antecedentes_pessoais = '';
            $antecedentes_familiares = '';
            $exame_fisico = '';
            $exames_complementares = '';
            $diagnostico = '';
            $prescricao = '';
            $orientacoes = '';
            $observacoes = '';
        } else {
            $erro = "Erro ao criar prontuário: " . $stmt->error;
        }
    }
}
?>

<div class="container mt-4">
    <h4>Cadastrar Prontuário</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="consulta_id" class="form-label">Consulta Vinculada *</label>
            <select id="consulta_id" name="consulta_id" class="form-select" required>
                <option value="">Selecione uma consulta agendada</option>
                <?php foreach ($consultas as $consulta): ?>
                    <option value="<?= $consulta['id'] ?>" 
                            data-paciente-id="<?= $consulta['paciente_id'] ?>"
                            data-data-consulta="<?= $consulta['data_consulta'] ?>">
                        <?= htmlspecialchars($consulta['paciente_nome']) ?> - <?= date('d/m/Y', strtotime($consulta['data_consulta'])) ?> - Turno: <?= ($consulta['turno'] === 'manha') ? 'Manhã' : 'Tarde' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="hidden" name="paciente_id" id="paciente_id">
        <input type="hidden" name="data_consulta" id="data_consulta">

        <div class="mb-3">
            <label for="queixa_principal" class="form-label">Queixa Principal</label>
            <textarea id="queixa_principal" name="queixa_principal" class="form-control" rows="3"><?= htmlspecialchars($queixa_principal) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="historia_doenca_atual" class="form-label">História da Doença Atual</label>
            <textarea id="historia_doenca_atual" name="historia_doenca_atual" class="form-control" rows="3"><?= htmlspecialchars($historia_doenca_atual) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="antecedentes_pessoais" class="form-label">Antecedentes Pessoais</label>
            <textarea id="antecedentes_pessoais" name="antecedentes_pessoais" class="form-control" rows="3"><?= htmlspecialchars($antecedentes_pessoais) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="antecedentes_familiares" class="form-label">Antecedentes Familiares</label>
            <textarea id="antecedentes_familiares" name="antecedentes_familiares" class="form-control" rows="3"><?= htmlspecialchars($antecedentes_familiares) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="exame_fisico" class="form-label">Exame Físico</label>
            <textarea id="exame_fisico" name="exame_fisico" class="form-control" rows="3"><?= htmlspecialchars($exame_fisico) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="exames_complementares" class="form-label">Exames Complementares</label>
            <textarea id="exames_complementares" name="exames_complementares" class="form-control" rows="3"><?= htmlspecialchars($exames_complementares) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="diagnostico" class="form-label">Diagnóstico</label>
            <textarea id="diagnostico" name="diagnostico" class="form-control" rows="3"><?= htmlspecialchars($diagnostico) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="prescricao" class="form-label">Prescrição</label>
            <textarea id="prescricao" name="prescricao" class="form-control" rows="3"><?= htmlspecialchars($prescricao) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="orientacoes" class="form-label">Orientações</label>
            <textarea id="orientacoes" name="orientacoes" class="form-control" rows="3"><?= htmlspecialchars($orientacoes) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="observacoes" class="form-label">Observações</label>
            <textarea id="observacoes" name="observacoes" class="form-control" rows="3"><?= htmlspecialchars($observacoes) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Cadastrar</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const consultaSelect = document.getElementById('consulta_id');
    const pacienteIdInput = document.getElementById('paciente_id');
    const dataConsultaInput = document.getElementById('data_consulta');

    consultaSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const pacienteId = selectedOption.dataset.pacienteId;
            const dataConsulta = selectedOption.dataset.dataConsulta;
            
            pacienteIdInput.value = pacienteId;
            dataConsultaInput.value = dataConsulta;
        } else {
            pacienteIdInput.value = '';
            dataConsultaInput.value = '';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>