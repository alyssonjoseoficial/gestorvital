<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

// Apenas usuários com os níveis de acesso "administrador" ou "financeiro" podem acessar esta página
if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}

date_default_timezone_set('America/Sao_Paulo');
$data_inicio_filtro = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim_filtro = $_GET['data_fim'] ?? date('Y-m-d');
$categoria_filtro = $_GET['categoria'] ?? '';

// Monta a consulta SQL base e os filtros
// ADICIONADO: Seleciona o ID da despesa para usar nas ações de edição e exclusão
$sql = "SELECT id, data_despesa, descricao, valor, categoria
        FROM despesas
        WHERE data_despesa BETWEEN ? AND ?";

$params = [$data_inicio_filtro, $data_fim_filtro];
$types = "ss";

// Adicionar filtro de categoria
if (!empty($categoria_filtro)) {
    $sql .= " AND categoria = ?";
    $params[] = $categoria_filtro;
    $types .= "s";
}

$sql .= " ORDER BY data_despesa ASC, id ASC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$total_despesas = 0;
?>

<div class="container mt-4">
    <h4>Relatório de Despesas</h4>

    <form method="GET" action="relatorio.php" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio_filtro) ?>">
            </div>
            <div class="col-md-3">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim_filtro) ?>">
            </div>
            <div class="col-md-3">
                <label for="categoria" class="form-label">Categoria</label>
                <select name="categoria" id="categoria" class="form-select">
                    <option value="">Todas</option>
                    <option value="contas fixas" <?= $categoria_filtro === 'contas fixas' ? 'selected' : '' ?>>Contas Fixas</option>
                    <option value="insumos medicos" <?= $categoria_filtro === 'insumos medicos' ? 'selected' : '' ?>>Insumos Médicos</option>
                    <option value="salarios" <?= $categoria_filtro === 'salarios' ? 'selected' : '' ?>>Salários</option>
                    <option value="manutencao" <?= $categoria_filtro === 'manutencao' ? 'selected' : '' ?>>Manutenção</option>
                    <option value="marketing" <?= $categoria_filtro === 'marketing' ? 'selected' : '' ?>>Marketing</option>
                    <option value="outros" <?= $categoria_filtro === 'outros' ? 'selected' : '' ?>>Outros</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <p>Mostrando despesas de **<?= date('d/m/Y', strtotime($data_inicio_filtro)) ?>** até **<?= date('d/m/Y', strtotime($data_fim_filtro)) ?>**</p>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($despesa = $result->fetch_assoc()): ?>
                        <?php $total_despesas += $despesa['valor']; ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($despesa['data_despesa'])) ?></td>
                            <td><?= htmlspecialchars($despesa['descricao']) ?></td>
                            <td><?= htmlspecialchars(ucwords($despesa['categoria'])) ?></td>
                            <td>R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></td>
                            <td>
                                <a href="editar.php?id=<?= $despesa['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="excluir.php?id=<?= $despesa['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta despesa?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-danger mt-3">
            <strong>Total de Despesas no Período:</strong> R$ <?= number_format($total_despesas, 2, ',', '.') ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-3">
            Nenhuma despesa registrada para o período e filtros selecionados.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>