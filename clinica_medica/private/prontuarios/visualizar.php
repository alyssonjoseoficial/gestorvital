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

// Buscar medico_id se for médico
$medico_id = null;
if ($nivel === 'medico') {
    $stmt_med = $conn->prepare("
        SELECT m.id 
        FROM medicos m
        INNER JOIN medico_usuario mu ON mu.medico_id = m.id
        WHERE mu.usuario_id = ?
        LIMIT 1
    ");
    $stmt_med->bind_param("i", $usuario_id);
    $stmt_med->execute();
    $result_med = $stmt_med->get_result();
    $medico = $result_med->fetch_assoc();
    if ($medico) {
        $medico_id = $medico['id'];
    } else {
        die("Médico não encontrado para o usuário logado.");
    }
}

// Receber id do prontuário via GET (prioritário) ou paciente_id via POST (vindo do listar.php)
$id = $_GET['id'] ?? null;

if (!$id) {
    // Tentar pegar paciente_id do POST e buscar o prontuário correspondente
    $paciente_id = $_POST['paciente_id'] ?? null;

    if (!$paciente_id) {
        die("ID do prontuário ou paciente não informado.");
    }

    if ($nivel === 'medico') {
        // Médico só vê prontuário dele para esse paciente
        $stmt = $conn->prepare("
            SELECT id FROM prontuarios 
            WHERE paciente_id = ? AND medico_id = ? 
            ORDER BY data_consulta DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $paciente_id, $medico_id);
    } else {
        // Admin pode ver qualquer prontuário do paciente
        $stmt = $conn->prepare("
            SELECT id FROM prontuarios 
            WHERE paciente_id = ? 
            ORDER BY data_consulta DESC LIMIT 1
        ");
        $stmt->bind_param("i", $paciente_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $prontuario_id_data = $result->fetch_assoc();

    if (!$prontuario_id_data) {
        die("Nenhum prontuário encontrado para este paciente.");
    }

    $id = $prontuario_id_data['id'];

    // Agora podemos redirecionar para evitar reenvio POST ou continuar com $id definido abaixo
    header("Location: " . BASE_URL_PRIVATE . "prontuarios/visualizar.php?id=$id");
    exit;
}

$error = '';
$success = '';

// Atualizar prontuário (edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medico_id'])) {
    // Somente admin ou médico dono do prontuário
    if ($nivel === 'administrador' || ($nivel === 'medico' && $_POST['medico_id'] == $medico_id)) {
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

        $stmt = $conn->prepare("UPDATE prontuarios SET
            queixa_principal = ?, historia_doenca_atual = ?, antecedentes_pessoais = ?, antecedentes_familiares = ?,
            exame_fisico = ?, exames_complementares = ?, diagnostico = ?, prescricao = ?, orientacoes = ?, observacoes = ?,
            atualizado_em = NOW()
            WHERE id = ?");
        $stmt->bind_param(
            "ssssssssssi",
            $queixa_principal, $historia_doenca_atual, $antecedentes_pessoais, $antecedentes_familiares,
            $exame_fisico, $exames_complementares, $diagnostico, $prescricao, $orientacoes, $observacoes,
            $id
        );

        if ($stmt->execute()) {
            // Buscar consulta_id do prontuário para atualizar status
            $stmt_consulta_id = $conn->prepare("SELECT consulta_id FROM prontuarios WHERE id = ?");
            $stmt_consulta_id->bind_param("i", $id);
            $stmt_consulta_id->execute();
            $result_consulta_id = $stmt_consulta_id->get_result();
            $row = $result_consulta_id->fetch_assoc();
            $stmt_consulta_id->close();

            if ($row && isset($row['consulta_id']) && $row['consulta_id']) {
                $consulta_id = $row['consulta_id'];

                $stmt_update_consulta = $conn->prepare("UPDATE consultas SET status = 'realizada' WHERE id = ?");
                $stmt_update_consulta->bind_param("i", $consulta_id);
                if (!$stmt_update_consulta->execute()) {
                    $error = "Prontuário atualizado, mas falha ao atualizar status da consulta: " . $stmt_update_consulta->error;
                }
                $stmt_update_consulta->close();
            } else {
                $error = "Prontuário atualizado, mas consulta vinculada não encontrada.";
            }

            if (!$error) {
                $success = "Prontuário atualizado com sucesso e status da consulta alterado para 'realizada'.";
            }
        } else {
            $error = "Erro ao atualizar prontuário: " . $stmt->error;
        }
    } else {
        $error = "Você não tem permissão para editar este prontuário.";
    }
}

// Buscar prontuário completo
$stmt = $conn->prepare("SELECT p.*, pac.nome AS paciente_nome, m.nome AS medico_nome
    FROM prontuarios p
    JOIN pacientes pac ON p.paciente_id = pac.id
    JOIN medicos m ON p.medico_id = m.id
    WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$prontuario = $stmt->get_result()->fetch_assoc();

if (!$prontuario) {
    die("Prontuário não encontrado.");
}

// Médico só pode ver prontuários dele
if ($nivel === 'medico' && $prontuario['medico_id'] != $medico_id) {
    die("Acesso negado.");
}

// Define se pode editar
$permitido_editar = ($nivel === 'administrador' || ($nivel === 'medico' && $prontuario['medico_id'] == $medico_id));
?>

<div class="container mt-4">
    <h4>Prontuário - <?= htmlspecialchars($prontuario['paciente_nome']) ?></h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="medico_id" value="<?= htmlspecialchars($prontuario['medico_id']) ?>">

        <div class="mb-3">
            <label>Paciente:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($prontuario['paciente_nome']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label>Médico:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($prontuario['medico_nome']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label>Data da Consulta:</label>
            <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($prontuario['data_consulta'])) ?>" readonly>
        </div>

        <?php
        $campos = [
            'queixa_principal' => 'Queixa Principal',
            'historia_doenca_atual' => 'História da Doença Atual',
            'antecedentes_pessoais' => 'Antecedentes Pessoais',
            'antecedentes_familiares' => 'Antecedentes Familiares',
            'exame_fisico' => 'Exame Físico',
            'exames_complementares' => 'Exames Complementares',
            'diagnostico' => 'Diagnóstico',
            'prescricao' => 'Prescrição',
            'orientacoes' => 'Orientações',
            'observacoes' => 'Observações',
        ];

        foreach ($campos as $campo => $label):
        ?>
            <div class="mb-3">
                <label for="<?= $campo ?>"><?= $label ?>:</label>
                <?php if ($permitido_editar): ?>
                    <textarea id="<?= $campo ?>" name="<?= $campo ?>" class="form-control" rows="3"><?= htmlspecialchars($prontuario[$campo]) ?></textarea>
                <?php else: ?>
                    <div class="border p-2" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($prontuario[$campo])) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-start mt-3">
            <?php if ($permitido_editar): ?>
                <button type="submit" class="btn btn-primary me-2">Salvar Prontuário</button>
            <?php endif; ?>
            <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#solicitarExameModal">
                Solicitar Exame
            </button>
            <a href="listar.php" class="btn btn-secondary">Voltar</a>
        </div>
    </form>
</div>

<div class="modal fade" id="solicitarExameModal" tabindex="-1" aria-labelledby="solicitarExameModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="solicitarExameModalLabel">Solicitar Exame</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formSolicitarExame">
          <input type="hidden" name="consulta_id" value="<?= htmlspecialchars($prontuario['consulta_id']) ?>">
          <div class="mb-3">
            <label for="descricaoExame" class="form-label">Descrição dos Exames</label>
            <textarea class="form-control" id="descricaoExame" name="descricao" rows="5" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="submit" class="btn btn-primary" form="formSolicitarExame">Salvar Solicitação</button>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('formSolicitarExame').addEventListener('submit', function(e) {
    e.preventDefault();

    var form = e.target;
    var formData = new FormData(form);

    fetch('salvar_solicitacao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Oculta o modal e limpa o formulário
            var modal = bootstrap.Modal.getInstance(document.getElementById('solicitarExameModal'));
            modal.hide();
            form.reset();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro ao tentar salvar a solicitação.');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>