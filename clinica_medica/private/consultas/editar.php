<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id = (int) $_GET['id'];
$erro = '';
$sucesso = '';

// Buscar dados atuais da consulta, incluindo o servico_id
$stmt = $conn->prepare("SELECT paciente_id, medico_id, servico_id, data_consulta, turno, status, observacoes FROM consultas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Consulta não encontrada.');
}

$consulta = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = $_POST['paciente_id'] ?? '';
    $medico_id = $_POST['medico_id'] ?? '';
    $servico_id = $_POST['servico_id'] ?? '';
    $data_consulta = $_POST['data_consulta'] ?? '';
    $turno = $_POST['turno'] ?? '';
    $status = $_POST['status'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$paciente_id || !$medico_id || !$data_consulta || !$turno || !$status || !$servico_id) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $stmt = $conn->prepare("UPDATE consultas SET paciente_id=?, medico_id=?, servico_id=?, data_consulta=?, turno=?, status=?, observacoes=? WHERE id=?");
        
        $stmt->bind_param("iiissssi", $paciente_id, $medico_id, $servico_id, $data_consulta, $turno, $status, $observacoes, $id);

        if ($stmt->execute()) {
            $sucesso = "Consulta atualizada com sucesso!";
            // Atualizar dados para exibir no formulário
            $consulta = [
                'paciente_id' => $paciente_id,
                'medico_id' => $medico_id,
                'servico_id' => $servico_id,
                'data_consulta' => $data_consulta,
                'turno' => $turno,
                'status' => $status,
                'observacoes' => $observacoes
            ];
        } else {
            $erro = "Erro ao atualizar: " . $conn->error;
        }
    }
}

// Buscar pacientes e médicos para os selects
$pacientes = $conn->query("SELECT id, nome FROM pacientes ORDER BY nome ASC");
$medicos = $conn->query("SELECT id, nome FROM medicos ORDER BY nome ASC");
// CÓDIGO ATUALIZADO: Buscar APENAS os serviços de "Consulta"
$servicos = $conn->query("SELECT id, nome FROM servicos WHERE tipo = 'Consulta' ORDER BY nome ASC");

?>

<div class="container mt-4">
    <h4>Editar Consulta</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="paciente_id" class="form-label">Paciente *</label>
            <select name="paciente_id" id="paciente_id" class="form-select" required>
                <option value="">Selecione um paciente</option>
                <?php while ($paciente = $pacientes->fetch_assoc()): ?>
                    <option value="<?= $paciente['id'] ?>" <?= (($consulta['paciente_id'] ?? '') == $paciente['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($paciente['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="medico_id" class="form-label">Médico *</label>
            <select name="medico_id" id="medico_id" class="form-select" required>
                <option value="">Selecione um médico</option>
                <?php while ($medico = $medicos->fetch_assoc()): ?>
                    <option value="<?= $medico['id'] ?>" <?= (($consulta['medico_id'] ?? '') == $medico['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($medico['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="servico_id" class="form-label">Serviço *</label>
            <select name="servico_id" id="servico_id" class="form-select" required>
                <option value="">Selecione o serviço</option>
                <?php while ($servico = $servicos->fetch_assoc()): ?>
                    <option value="<?= $servico['id'] ?>" <?= (($consulta['servico_id'] ?? '') == $servico['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($servico['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="data_consulta" class="form-label">Data *</label>
            <input type="date" name="data_consulta" id="data_consulta" class="form-control" required
                value="<?= isset($consulta['data_consulta']) ? htmlspecialchars($consulta['data_consulta']) : '' ?>">
        </div>

        <div class="mb-3">
            <label for="turno" class="form-label">Turno *</label>
            <select name="turno" id="turno" class="form-select" required>
                <option value="">Selecione o turno</option>
                <option value="manha" <?= (($consulta['turno'] ?? '') === 'manha') ? 'selected' : '' ?>>Manhã</option>
                <option value="tarde" <?= (($consulta['turno'] ?? '') === 'tarde') ? 'selected' : '' ?>>Tarde</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status *</label>
            <select name="status" id="status" class="form-select" required>
                <option value="">Selecione o status</option>
                <option value="agendada" <?= (($consulta['status'] ?? '') === 'agendada') ? 'selected' : '' ?>>Agendada</option>
                <option value="realizada" <?= (($consulta['status'] ?? '') === 'realizada') ? 'selected' : '' ?>>Realizada</option>
                <option value="cancelada" <?= (($consulta['status'] ?? '') === 'cancelada') ? 'selected' : '' ?>>Cancelada</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="observacoes" class="form-label">Observações</label>
            <textarea name="observacoes" id="observacoes" rows="3" class="form-control"><?= htmlspecialchars($consulta['observacoes'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>