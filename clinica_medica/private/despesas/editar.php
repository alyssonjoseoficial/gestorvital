<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/log_auditoria.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: relatorio.php');
    exit;
}

$id = (int) $_GET['id'];
$erro = '';
$sucesso = '';

// Buscar dados atuais da despesa
$stmt = $conn->prepare("SELECT data_despesa, descricao, valor, categoria FROM despesas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$despesa = $result->fetch_assoc();
$stmt->close();

if (!$despesa) {
    die('Despesa não encontrada.');
}

// Salva os dados originais para comparação no log
$data_despesa_antiga = $despesa['data_despesa'];
$descricao_antiga = $despesa['descricao'];
$valor_antigo = $despesa['valor'];
$categoria_antiga = $despesa['categoria'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_despesa_nova = trim($_POST['data_despesa'] ?? '');
    $descricao_nova = trim($_POST['descricao'] ?? '');
    $valor_novo = trim($_POST['valor'] ?? '');
    $categoria_nova = trim($_POST['categoria'] ?? NULL);

    if (empty($data_despesa_nova) || empty($descricao_nova) || empty($valor_novo)) {
        $erro = "Todos os campos obrigatórios devem ser preenchidos.";
    } else {
        $stmt_update = $conn->prepare("UPDATE despesas SET data_despesa = ?, descricao = ?, valor = ?, categoria = ? WHERE id = ?");
        $stmt_update->bind_param("ssdss", $data_despesa_nova, $descricao_nova, $valor_novo, $categoria_nova, $id);

        if ($stmt_update->execute()) {
            // Prepara a mensagem de log, comparando os valores
            $alteracoes = [];
            if ($data_despesa_nova !== $data_despesa_antiga) {
                $alteracoes[] = "Data de '" . date('d/m/Y', strtotime($data_despesa_antiga)) . "' para '" . date('d/m/Y', strtotime($data_despesa_nova)) . "'";
            }
            if ($descricao_nova !== $descricao_antiga) {
                $alteracoes[] = "Descrição de '{$descricao_antiga}' para '{$descricao_nova}'";
            }
            if ($valor_novo !== $valor_antigo) {
                $alteracoes[] = "Valor de R$ " . number_format($valor_antigo, 2, ',', '.') . " para R$ " . number_format($valor_novo, 2, ',', '.');
            }
            if ($categoria_nova !== $categoria_antiga) {
                $alteracoes[] = "Categoria de '{$categoria_antiga}' para '{$categoria_nova}'";
            }

            $acao_log = 'Editou a despesa (ID ' . $id . ')';
            if (!empty($alteracoes)) {
                $acao_log .= ': ' . implode(', ', $alteracoes);
            }
            
            registrar_log($acao_log, 'Despesas', $id);
            
            $sucesso = "Despesa atualizada com sucesso!";
            
            // Atualiza o array $despesa para que os novos valores sejam exibidos
            $despesa['data_despesa'] = $data_despesa_nova;
            $despesa['descricao'] = $descricao_nova;
            $despesa['valor'] = $valor_novo;
            $despesa['categoria'] = $categoria_nova;

        } else {
            $erro = "Erro ao atualizar despesa: " . $conn->error;
        }
    }
}
?>

<div class="container mt-4">
    <h4>Editar Despesa</h4>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="data_despesa" class="form-label">Data da Despesa *</label>
            <input type="date" name="data_despesa" id="data_despesa" class="form-control" required value="<?= htmlspecialchars($despesa['data_despesa']) ?>">
        </div>

        <div class="mb-3">
            <label for="descricao" class="form-label">Descrição *</label>
            <textarea name="descricao" id="descricao" rows="3" class="form-control" required><?= htmlspecialchars($despesa['descricao']) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="valor" class="form-label">Valor *</label>
            <input type="number" step="0.01" name="valor" id="valor" class="form-control" required value="<?= htmlspecialchars($despesa['valor']) ?>">
        </div>
        
        <div class="mb-3">
            <label for="categoria" class="form-label">Categoria</label>
            <select name="categoria" id="categoria" class="form-select">
                <option value="">Selecione...</option>
                <option value="contas fixas" <?= $despesa['categoria'] === 'contas fixas' ? 'selected' : '' ?>>Contas Fixas</option>
                <option value="insumos medicos" <?= $despesa['categoria'] === 'insumos medicos' ? 'selected' : '' ?>>Insumos Médicos</option>
                <option value="salarios" <?= $despesa['categoria'] === 'salarios' ? 'selected' : '' ?>>Salários</option>
                <option value="manutencao" <?= $despesa['categoria'] === 'manutencao' ? 'selected' : '' ?>>Manutenção</option>
                <option value="marketing" <?= $despesa['categoria'] === 'marketing' ? 'selected' : '' ?>>Marketing</option>
                <option value="outros" <?= $despesa['categoria'] === 'outros' ? 'selected' : '' ?>>Outros</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="relatorio.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>