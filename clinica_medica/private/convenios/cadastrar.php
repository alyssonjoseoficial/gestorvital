<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao', 'financeiro'])) {
    die('Acesso negado.');
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($nome === '') {
        $erro = "O campo Nome é obrigatório.";
    } else {
        $stmt = $conn->prepare("INSERT INTO convenios (nome, descricao, criado_em) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $nome, $descricao);

        if ($stmt->execute()) {
            // ADICIONADO: Obtém o ID do convênio recém-criado
            $novo_convenio_id = $conn->insert_id;
            
            // ADICIONADO: Chama a função para registrar a ação
            $acao_log = 'Cadastrou o convênio: ' . $nome;
            registrar_log($acao_log, 'Convênios', $novo_convenio_id);
            
            $sucesso = "Convênio cadastrado com sucesso!";
            $_POST = [];
        } else {
            $erro = "Erro ao cadastrar convênio: " . $conn->error;
        }
    }
}
?>

<div class="container mt-4">
    <h4>Cadastrar Novo Convênio</h4>

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
            <label for="descricao" class="form-label">Descrição</label>
            <textarea name="descricao" id="descricao" rows="4" class="form-control"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Cadastrar Convênio</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>