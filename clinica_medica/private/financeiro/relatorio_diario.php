<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

// Apenas usuários com os níveis de acesso "administrador" ou "financeiro" podem acessar esta página
if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}

// Define a data atual como padrão para os filtros
date_default_timezone_set('America/Sao_Paulo');
$data_inicio_filtro = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim_filtro = $_GET['data_fim'] ?? date('Y-m-d');
$metodo_pagamento_filtro = $_GET['metodo_pagamento'] ?? '';
$servico_id_filtro = $_GET['servico_id'] ?? '';

// Monta a consulta SQL base e os filtros
$sql = "SELECT p.valor, p.metodo_pagamento, p.observacoes, p.data_pagamento,
               c.id AS consulta_id,
               pac.nome AS paciente_nome,
               s.nome AS servico_nome
        FROM pagamentos p
        LEFT JOIN consultas c ON p.consulta_id = c.id
        LEFT JOIN pacientes pac ON c.paciente_id = pac.id
        LEFT JOIN servicos s ON c.servico_id = s.id
        WHERE p.data_pagamento BETWEEN ? AND ?";

$params = [$data_inicio_filtro, $data_fim_filtro];
$types = "ss";

// Adicionar filtros dinâmicos
if (!empty($metodo_pagamento_filtro)) {
    $sql .= " AND p.metodo_pagamento = ?";
    $params[] = $metodo_pagamento_filtro;
    $types .= "s";
}
if (!empty($servico_id_filtro)) {
    $sql .= " AND c.servico_id = ?";
    $params[] = $servico_id_filtro;
    $types .= "i";
}

$sql .= " ORDER BY p.data_pagamento ASC, p.id ASC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total_recebido = 0;

// Buscar a lista de serviços para o filtro
$sql_servicos = "SELECT id, nome FROM servicos ORDER BY nome";
$servicos_result = $conn->query($sql_servicos);
$servicos = [];
if ($servicos_result->num_rows > 0) {
    while ($row = $servicos_result->fetch_assoc()) {
        $servicos[] = $row;
    }
}
?>

<div class="container mt-4">
    <h4>Relatório de Recebimentos</h4>

    <form method="GET" action="relatorio_diario.php" class="mb-4">
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
                <label for="metodo_pagamento" class="form-label">Método de Pagamento</label>
                <select name="metodo_pagamento" id="metodo_pagamento" class="form-select">
                    <option value="">Todos</option>
                    <option value="dinheiro" <?= $metodo_pagamento_filtro === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                    <option value="cartao_credito" <?= $metodo_pagamento_filtro === 'cartao_credito' ? 'selected' : '' ?>>Cartão de Crédito</option>
                    <option value="cartao_debito" <?= $metodo_pagamento_filtro === 'cartao_debito' ? 'selected' : '' ?>>Cartão de Débito</option>
                    <option value="pix" <?= $metodo_pagamento_filtro === 'pix' ? 'selected' : '' ?>>PIX</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="servico_id" class="form-label">Serviço</label>
                <select name="servico_id" id="servico_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($servicos as $servico): ?>
                        <option value="<?= htmlspecialchars($servico['id']) ?>" <?= $servico_id_filtro == $servico['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($servico['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <p>Mostrando recebimentos de **<?= date('d/m/Y', strtotime($data_inicio_filtro)) ?>** até **<?= date('d/m/Y', strtotime($data_fim_filtro)) ?>**</p>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Data</th>
                        <th>Paciente</th>
                        <th>Serviço</th>
                        <th>Método de Pagamento</th>
                        <th>Valor</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pagamento = $result->fetch_assoc()): ?>
                        <?php $total_recebido += $pagamento['valor']; ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($pagamento['data_pagamento'])) ?></td>
                            <td><?= htmlspecialchars($pagamento['paciente_nome'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($pagamento['servico_nome'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($pagamento['metodo_pagamento']) ?></td>
                            <td>R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($pagamento['observacoes']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info mt-3">
            <strong>Total Recebido no Período:</strong> R$ <?= number_format($total_recebido, 2, ',', '.') ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-3">
            Nenhum recebimento registrado para o período e filtros selecionados.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>