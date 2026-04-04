<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

// Apenas usuários com os níveis de acesso "administrador" ou "financeiro" podem acessar esta página
if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}

// Mensagens de feedback
$sucesso = $_SESSION['sucesso_mensagem'] ?? '';
$erro = $_SESSION['erro_mensagem'] ?? '';
unset($_SESSION['sucesso_mensagem']);
unset($_SESSION['erro_mensagem']);
?>

<div class="container mt-4">
    <h4>Cadastrar Nova Despesa</h4>

    <?php if ($sucesso): ?>
        <div class="alert alert-success mt-3"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form action="salvar.php" method="POST">
        <div class="mb-3">
            <label for="data_despesa" class="form-label">Data da Despesa *</label>
            <input type="date" name="data_despesa" id="data_despesa" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="mb-3">
            <label for="descricao" class="form-label">Descrição *</label>
            <input type="text" name="descricao" id="descricao" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="valor" class="form-label">Valor *</label>
            <input type="number" step="0.01" name="valor" id="valor" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="categoria" class="form-label">Categoria</label>
            <select name="categoria" id="categoria" class="form-select">
                <option value="">Selecione...</option>
                <option value="contas fixas">Contas Fixas</option>
                <option value="insumos medicos">Insumos Médicos</option>
                <option value="salarios">Salários</option>
                <option value="manutencao">Manutenção</option>
                <option value="marketing">Marketing</option>
                <option value="outros">Outros</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Salvar Despesa</button>
        <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>