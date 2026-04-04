<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id = (int) $_GET['id'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $crm = trim($_POST['crm']);
    $especialidade = trim($_POST['especialidade']);
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);
    $horario_atendimento = trim($_POST['horario_atendimento']);

    if ($nome === '' || $crm === '') {
        $erro = "Nome e CRM são obrigatórios.";
    } else {
        $stmt = $conn->prepare("UPDATE medicos SET nome=?, crm=?, especialidade=?, telefone=?, email=?, horario_atendimento=? WHERE id=?");
        $stmt->bind_param('ssssssi', $nome, $crm, $especialidade, $telefone, $email, $horario_atendimento, $id);
        
        if ($stmt->execute()) {
            // ADICIONADO: Chama a função para registrar a ação
            registrar_log('Editou médico: ' . $nome, 'Médicos', $id);
            
            header('Location: listar.php');
            exit;
        } else {
            $erro = "Erro ao atualizar: " . $conn->error;
        }
    }
} else {
    $stmt = $conn->prepare("SELECT nome, crm, especialidade, telefone, email, horario_atendimento, criado_em FROM medicos WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows === 0) {
        header('Location: listar.php');
        exit;
    }
    $medico = $resultado->fetch_assoc();
}
?>

<div class="container mt-4">
    <h4>Editar Médico</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome *</label>
            <input type="text" name="nome" id="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? $medico['nome']) ?>">
        </div>
        <div class="mb-3">
            <label for="crm" class="form-label">CRM *</label>
            <input type="text" name="crm" id="crm" class="form-control" required value="<?= htmlspecialchars($_POST['crm'] ?? $medico['crm']) ?>">
        </div>
        <div class="mb-3">
            <label for="especialidade" class="form-label">Especialidade</label>
            <input type="text" name="especialidade" id="especialidade" class="form-control" value="<?= htmlspecialchars($_POST['especialidade'] ?? $medico['especialidade']) ?>">
        </div>
        <div class="mb-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="text" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($_POST['telefone'] ?? $medico['telefone']) ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $medico['email']) ?>">
        </div>
        <div class="mb-3">
            <label for="horario_atendimento" class="form-label">Horário de Atendimento</label>
            <input type="text" name="horario_atendimento" id="horario_atendimento" class="form-control" placeholder="Ex: 08:00 às 12:00" value="<?= htmlspecialchars($_POST['horario_atendimento'] ?? $medico['horario_atendimento']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Criado em</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($medico['criado_em']) ?>" readonly>
        </div>
        <button type="submit" class="btn btn-primary">Atualizar</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>