<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
include_once '../includes/menu.php';

// Apenas usuários logados podem acessar
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (!$senha_atual || !$nova_senha || !$confirma_senha) {
        $erro = "Por favor, preencha todos os campos.";
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = "A nova senha e a confirmação não coincidem.";
    } else {
        // Buscar usuário no banco
        $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            // Verificar se a senha atual bate
            if (password_verify($senha_atual, $usuario['senha'])) {
                // Atualizar senha
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt2->bind_param("si", $senha_hash, $_SESSION['usuario_id']);
                if ($stmt2->execute()) {
                    $sucesso = "Senha alterada com sucesso!";
                } else {
                    $erro = "Erro ao atualizar a senha. Tente novamente.";
                }
            } else {
                $erro = "Senha atual incorreta.";
            }
        } else {
            $erro = "Usuário não encontrado.";
        }
    }
}
?>

<div class="container mt-4">
    <h4>Alterar Senha</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="senha_atual" class="form-label">Senha Atual</label>
            <input type="password" name="senha_atual" id="senha_atual" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="nova_senha" class="form-label">Nova Senha</label>
            <input type="password" name="nova_senha" id="nova_senha" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="confirma_senha" class="form-label">Confirmar Nova Senha</label>
            <input type="password" name="confirma_senha" id="confirma_senha" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Alterar Senha</button>
        <a href="../dashboard.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
