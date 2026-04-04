<?php
session_start();
// O config.php já inclui a conexão com o banco de dados ($conn)
require_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

$nivel = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel, ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}

// 1. Lógica para pegar o mês e ano do filtro
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Inicializa arrays para armazenar os dados
$receitas = [];
$despesas = [];
$total_receitas = 0;
$total_despesas = 0;

// Agora usamos a conexão global $conn do config.php
if ($conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn->connect_error);
}

// 2. Query para buscar Receitas (agora com a descrição do serviço)
// Usamos LEFT JOIN para garantir que todos os pagamentos sejam incluídos
$sql_receitas = "SELECT COALESCE(s.nome, p.metodo_pagamento) AS descricao, p.valor 
                 FROM pagamentos p
                 LEFT JOIN consultas c ON p.consulta_id = c.id
                 LEFT JOIN servicos s ON c.servico_id = s.id
                 WHERE MONTH(p.data_pagamento) = ? AND YEAR(p.data_pagamento) = ?";
$stmt_receitas = $conn->prepare($sql_receitas);
$stmt_receitas->bind_param("ii", $mes, $ano); // 'ii' indica dois inteiros
$stmt_receitas->execute();
$result_receitas = $stmt_receitas->get_result();

while ($row = $result_receitas->fetch_assoc()) {
    $receitas[] = $row;
    $total_receitas += $row['valor'];
}

$stmt_receitas->close();

// 3. Query para buscar Despesas (usando a tabela 'despesas' e a coluna 'descricao')
$sql_despesas = "SELECT descricao, valor FROM despesas WHERE MONTH(data_despesa) = ? AND YEAR(data_despesa) = ?";
$stmt_despesas = $conn->prepare($sql_despesas);
$stmt_despesas->bind_param("ii", $mes, $ano); // 'ii' indica dois inteiros
$stmt_despesas->execute();
$result_despesas = $stmt_despesas->get_result();

while ($row = $result_despesas->fetch_assoc()) {
    $despesas[] = $row;
    $total_despesas += $row['valor'];
}

$stmt_despesas->close();
// REMOVIDO: A linha $conn->close() foi removida daqui.

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Relatório de Balancete (Receitas e Despesas)</h4>
        <div>
            
            <nav aria-label="breadcrumb" style="display: inline-block;">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="dashboard_financeiro.php">Financeiro</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Balancete</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="get" action="relatorio_balancete.php" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="mes" class="form-label">Mês</label>
                <select id="mes" name="mes" class="form-select">
                    <?php
                    $meses = [
                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                    ];
                    foreach ($meses as $num => $nome) {
                        echo "<option value=\"$num\"" . ($mes == $num ? " selected" : "") . ">$nome</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="ano" class="form-label">Ano</label>
                <select id="ano" name="ano" class="form-select">
                    <?php
                    for ($i = date('Y'); $i >= date('Y') - 5; $i--) { // Exibe os últimos 5 anos
                        echo "<option value=\"$i\"" . ($ano == $i ? " selected" : "") . ">$i</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                
        </div>
    </form>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Receitas</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($receitas)): ?>
                            <li class="list-group-item text-center text-muted">Nenhuma receita encontrada para este período.</li>
                        <?php else: ?>
                            <?php foreach ($receitas as $receita): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($receita['descricao']) ?>
                                    <span class="badge bg-success">R$ <?= number_format($receita['valor'], 2, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer text-end">
                    <strong>Total de Receitas: <span class="text-success">R$ <?= number_format($total_receitas, 2, ',', '.') ?></span></strong>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Despesas</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($despesas)): ?>
                            <li class="list-group-item text-center text-muted">Nenhuma despesa encontrada para este período.</li>
                        <?php else: ?>
                            <?php foreach ($despesas as $despesa): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($despesa['descricao']) ?>
                                    <span class="badge bg-danger">R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer text-end">
                    <strong>Total de Despesas: <span class="text-danger">R$ <?= number_format($total_despesas, 2, ',', '.') ?></span></strong>
                </div>
            </div>
        </div>
    </div>
    <a href="gerar_balancete_pdf.php?mes=<?= $mes ?>&ano=<?= $ano ?>" target="_blank" class="btn btn-secondary me-2">
                 <i class="fas fa-print"></i> Imprimir Balancete
             </a>
</div>
</div>
</div>

<?php
// Inclui o rodapé no final da página, que fechará a conexão
include_once __DIR__ . '/../includes/rodape.php';
?>