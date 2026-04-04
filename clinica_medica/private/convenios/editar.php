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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id = (int) $_GET['id'];
$erro = '';
$sucesso = '';

// Buscar dados atuais do convênio
$stmt = $conn->prepare("SELECT nome, descricao FROM convenios WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Convênio não encontrado.');
}

$convenio = $result->fetch_assoc();
$nome_antigo = $convenio['nome'];
$descricao_antiga = $convenio['descricao'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($nome === '') {
        $erro = "O campo Nome é obrigatório.";
    } else {
        $stmt = $conn->prepare("UPDATE convenios SET nome = ?, descricao = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nome, $descricao, $id);

        if ($stmt->execute()) {
            // ADICIONADO: Monta a mensagem de log com base nas alterações
            $alteracoes = [];
            if ($nome !== $nome_antigo) {
                $alteracoes[] = "Nome de '{$nome_antigo}' para '{$nome}'";
            }
            if ($descricao !== $descricao_antiga) {
                $alteracoes[] = "Descrição de '{$descricao_antiga}' para '{$descricao}'";
            }
            
            // ADICIONADO: Chama a função para registrar a ação
            $acao_log = 'Editou o convênio (ID ' . $id . ')';
            if (!empty($alteracoes)) {
                $acao_log .= ': ' . implode(', ', $alteracoes);
            }
            registrar_log($acao_log, 'Convênios', $id);

            $sucesso = "Convênio atualizado com sucesso!";
            $convenio['nome'] = $nome;
            $convenio['descricao'] = $descricao;
        } else {
            $erro = "Erro ao atualizar convênio: " . $conn->error;
        }
    }
}
?>

<div class="container mt-4">
    <h4>Editar Convênio</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome *</label>
            <input type="text" name="nome" id="nome" class="form-control" required value="<?= htmlspecialchars($convenio['nome']) ?>">
        </div>

        <div class="mb-3">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea name="descricao" id="descricao" rows="4" class="form-control"><?= htmlspecialchars($convenio['descricao']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>