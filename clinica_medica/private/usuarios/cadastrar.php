<?php
// Inicia a sessão no topo, antes de qualquer output
session_start();

// Inclui o arquivo de configuração
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if ($_SESSION['nivel_acesso'] !== 'administrador') {
    die('Acesso negado.');
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];
    $nivel_acesso = $_POST['nivel_acesso'];

    if ($nome === '' || $email === '' || $senha === '' || $confirma_senha === '' || $nivel_acesso === '') {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif ($senha !== $confirma_senha) {
        $erro = "As senhas não conferem.";
    } else {
        // Verifica se email já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $erro = "E-mail já cadastrado.";
        } else {
            $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso, criado_em) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param('ssss', $nome, $email, $hash_senha, $nivel_acesso);
            
            if ($stmt->execute()) {
                $novo_usuario_id = $conn->insert_id; // Pega o ID do novo usuário
                
                // ADICIONADO: Chama a função para registrar a ação
                registrar_log('Cadastrou novo usuário', 'Usuários', $novo_usuario_id);
                
                $_SESSION['sucesso_mensagem'] = "Usuário cadastrado com sucesso!";
                
                // Redireciona APÓS o processamento, mas ANTES de qualquer output HTML
                header('Location: listar.php');
                exit;
            } else {
                $erro = "Erro ao cadastrar: " . $conn->error;
            }
        }
    }
}

// Inclui os arquivos de layout SOMENTE APÓS o processamento do formulário
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';
?>

<div class="container mt-4">
    <h4>Cadastrar Usuário</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="cadastrar.php">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome *</label>
            <input type="text" name="nome" id="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail *</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="senha" class="form-label">Senha *</label>
            <input type="password" name="senha" id="senha" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="confirma_senha" class="form-label">Confirmar Senha *</label>
            <input type="password" name="confirma_senha" id="confirma_senha" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="nivel_acesso" class="form-label">Nível de Acesso *</label>
            <select name="nivel_acesso" id="nivel_acesso" class="form-select" required>
                <option value="">Selecione...</option>
                <option value="administrador" <?= (($_POST['nivel_acesso'] ?? '') === 'administrador') ? 'selected' : '' ?>>Administrador</option>
                <option value="recepcao" <?= (($_POST['nivel_acesso'] ?? '') === 'recepcao') ? 'selected' : '' ?>>Recepção</option>
                <option value="medico" <?= (($_POST['nivel_acesso'] ?? '') === 'medico') ? 'selected' : '' ?>>Médico</option>
                <option value="financeiro" <?= (($_POST['nivel_acesso'] ?? '') === 'financeiro') ? 'selected' : '' ?>>Financeiro</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>