<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

$erro = '';
$sucesso = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $crm = trim($_POST['crm'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $horario_atendimento = trim($_POST['horario_atendimento'] ?? '');

    if ($nome === '' || $crm === '' || $email === '') {
        $erro = "Os campos Nome, CRM e Email são obrigatórios.";
    } else {
        // Iniciar transação para garantir integridade
        $conn->begin_transaction();

        try {
            // Inserir médico
            $stmt = $conn->prepare("INSERT INTO medicos (nome, crm, especialidade, telefone, email, horario_atendimento, criado_em) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $nome, $crm, $especialidade, $telefone, $email, $horario_atendimento);
            $stmt->execute();
            $id_medico = $stmt->insert_id;

            // Criar usuário para o médico com senha padrão 'medico123'
            $senha_padrao = password_hash('medico123', PASSWORD_DEFAULT);
            $nivel_acesso = 'medico';

            $stmt2 = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso, criado_em) VALUES (?, ?, ?, ?, NOW())");
            $stmt2->bind_param("ssss", $nome, $email, $senha_padrao, $nivel_acesso);
            $stmt2->execute();

            $conn->commit();

            $sucesso = "Médico e usuário criados com sucesso! Senha padrão: medico123";
            
            // ADICIONADO: Chama a função para registrar a ação
            registrar_log('Cadastrou novo médico: ' . $nome, 'Médicos', $id_medico);

            // Limpar campos para o form
            $_POST = [];
        } catch (Exception $e) {
            $conn->rollback();
            $erro = "Erro ao cadastrar médico e usuário: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <h4>Cadastrar Novo Médico</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome *</label>
            <input type="text" name="nome" id="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="crm" class="form-label">CRM *</label>
            <input type="text" name="crm" id="crm" class="form-control" required value="<?= htmlspecialchars($_POST['crm'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="especialidade" class="form-label">Especialidade</label>
            <input type="text" name="especialidade" id="especialidade" class="form-control" value="<?= htmlspecialchars($_POST['especialidade'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="text" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email *</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="horario_atendimento" class="form-label">Horário de Atendimento</label>
            <input type="text" name="horario_atendimento" id="horario_atendimento" class="form-control" value="<?= htmlspecialchars($_POST['horario_atendimento'] ?? '') ?>">
        </div>

        <?php if ($sucesso): ?>
            <div class="alert alert-info">
                <strong>Usuário criado:</strong> <?= htmlspecialchars($email) ?><br>
                <strong>Senha provisória:</strong> medico123
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Cadastrar Médico</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>