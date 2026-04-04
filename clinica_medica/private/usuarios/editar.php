<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if ($_SESSION['nivel_acesso'] !== 'administrador') {
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
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];
    $nivel_acesso = $_POST['nivel_acesso'];

    if ($nome === '' || $email === '') {
        $erro = "Nome e E-mail são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif ($senha !== '' && $senha !== $confirma_senha) {
        $erro = "As senhas não conferem.";
    } else {
        // Verifica se email já existe em outro usuário
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $erro = "E-mail já cadastrado para outro usuário.";
        } else {
            if ($senha !== '') {
                $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, senha=?, nivel_acesso=? WHERE id=?");
                $stmt->bind_param('ssssi', $nome, $email, $hash_senha, $nivel_acesso, $id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, nivel_acesso=? WHERE id=?");
                $stmt->bind_param('sssi', $nome, $email, $nivel_acesso, $id);
            }

            if ($stmt->execute()) {
                // ADICIONADO: Chama a função para registrar a ação
                registrar_log('Editou usuário', 'Usuários', $id);
                
                header('Location: listar.php');
                exit;
            } else {
                $erro = "Erro ao atualizar: " . $conn->error;
            }
        }
    }
} else {
    $stmt = $conn->prepare("SELECT nome, email, nivel_acesso FROM usuarios WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows === 0) {
        header('Location: listar.php');
        exit;
    }
    $usuario = $resultado->fetch_assoc();
}
?>

<div class="container mt-4">
    <h4>Editar Usuário</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome *</label>
            <input type="text" name="nome" id="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? $usuario['nome']) ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail *</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? $usuario['email']) ?>">
        </div>
        <div class="mb-3">
            <label for="senha" class="form-label">Senha (deixe em branco para manter)</label>
            <input type="password" name="senha" id="senha" class="form-control">
        </div>
        <div class="mb-3">
            <label for="confirma_senha" class="form-label">Confirmar Senha</label>
            <input type="password" name="confirma_senha" id="confirma_senha" class="form-control">
        </div>
        <div class="mb-3">
            <label for="nivel_acesso" class="form-label">Nível de Acesso *</label>
            <select name="nivel_acesso" id="nivel_acesso" class="form-select" required>
                <option value="">Selecione...</option>
                <option value="administrador" <?= (($_POST['nivel_acesso'] ?? $usuario['nivel_acesso']) === 'administrador') ? 'selected' : '' ?>>Administrador</option>
                <option value="recepcao" <?= (($_POST['nivel_acesso'] ?? $usuario['nivel_acesso']) === 'recepcao') ? 'selected' : '' ?>>Recepção</option>
                <option value="medico" <?= (($_POST['nivel_acesso'] ?? $usuario['nivel_acesso']) === 'medico') ? 'selected' : '' ?>>Médico</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Atualizar</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>