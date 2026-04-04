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
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$movimentos = [];
$total_recebimentos = 0;
$total_despesas = 0;

// Consulta para buscar recebimentos
$sql_recebimentos = "SELECT p.valor, p.metodo_pagamento, p.data_pagamento, 'Recebimento' as tipo,
                      pac.nome AS paciente_nome, s.nome AS servico_nome
                     FROM pagamentos p
                     LEFT JOIN consultas c ON p.consulta_id = c.id
                     LEFT JOIN pacientes pac ON c.paciente_id = pac.id
                     LEFT JOIN servicos s ON c.servico_id = s.id
                     WHERE p.data_pagamento BETWEEN ? AND ?";
$stmt_recebimentos = $conn->prepare($sql_recebimentos);
$stmt_recebimentos->bind_param("ss", $data_inicio, $data_fim);
$stmt_recebimentos->execute();
$result_recebimentos = $stmt_recebimentos->get_result();

while ($row = $result_recebimentos->fetch_assoc()) {
    $movimentos[] = [
        'data' => $row['data_pagamento'],
        'tipo' => 'Recebimento',
        'descricao' => "Pagamento de " . ($row['servico_nome'] ?? 'N/A') . " por " . ($row['paciente_nome'] ?? 'N/A'),
        'valor' => $row['valor'],
        'metodo' => $row['metodo_pagamento'],
        'estilo' => 'table-success'
    ];
    $total_recebimentos += $row['valor'];
}

// Consulta para buscar despesas
$sql_despesas = "SELECT data_despesa, descricao, valor, categoria, 'Despesa' as tipo
                 FROM despesas
                 WHERE data_despesa BETWEEN ? AND ?";
$stmt_despesas = $conn->prepare($sql_despesas);
$stmt_despesas->bind_param("ss", $data_inicio, $data_fim);
$stmt_despesas->execute();
$result_despesas = $stmt_despesas->get_result();

while ($row = $result_despesas->fetch_assoc()) {
    $movimentos[] = [
        'data' => $row['data_despesa'],
        'tipo' => 'Despesa',
        'descricao' => $row['descricao'],
        'valor' => $row['valor'],
        'metodo' => $row['categoria'],
        'estilo' => 'table-danger'
    ];
    $total_despesas += $row['valor'];
}

// Ordena os movimentos por data
usort($movimentos, function($a, $b) {
    return strtotime($a['data']) - strtotime($b['data']);
});

$saldo_final = $total_recebimentos - $total_despesas;
?>

<div class="container mt-4">
    <h4>Relatório de Fluxo de Caixa</h4>

    <form method="GET" action="relatorio_fluxo_de_caixa.php" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
            </div>
            <div class="col-md-3">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <p>Mostrando movimentos de **<?= date('d/m/Y', strtotime($data_inicio)) ?>** até **<?= date('d/m/Y', strtotime($data_fim)) ?>**</p>

    <?php if (!empty($movimentos)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Detalhes</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimentos as $movimento): ?>
                        <tr class="<?= $movimento['estilo'] ?>">
                            <td><?= date('d/m/Y', strtotime($movimento['data'])) ?></td>
                            <td><?= htmlspecialchars($movimento['tipo']) ?></td>
                            <td><?= htmlspecialchars($movimento['descricao']) ?></td>
                            <td><?= htmlspecialchars($movimento['metodo']) ?></td>
                            <td>R$ <?= number_format($movimento['valor'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="alert alert-success">
                    <strong>Total Recebido:</strong> R$ <?= number_format($total_recebimentos, 2, ',', '.') ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-danger">
                    <strong>Total de Despesas:</strong> R$ <?= number_format($total_despesas, 2, ',', '.') ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-<?= $saldo_final >= 0 ? 'info' : 'danger' ?>">
                    <strong>Saldo Final:</strong> R$ <?= number_format($saldo_final, 2, ',', '.') ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-3">
            Nenhum movimento financeiro registrado para o período selecionado.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>