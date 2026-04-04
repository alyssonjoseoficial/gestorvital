<?php
// Inclui as bibliotecas e a conexão com o banco de dados
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
use Dompdf\Dompdf;

session_start();

// Verifica o nível de acesso
$nivel = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel, ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}

// Lógica para pegar o mês e ano do filtro
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

// Query para buscar Receitas
// Usa JOIN para conectar pagamentos -> consultas -> servicos e obter a descrição
$sql_receitas = "SELECT s.nome AS descricao, p.valor 
                 FROM pagamentos p
                 JOIN consultas c ON p.consulta_id = c.id
                 JOIN servicos s ON c.servico_id = s.id
                 WHERE MONTH(p.data_pagamento) = ? AND YEAR(p.data_pagamento) = ?";
$stmt_receitas = $conn->prepare($sql_receitas);
$stmt_receitas->bind_param("ii", $mes, $ano);
$stmt_receitas->execute();
$result_receitas = $stmt_receitas->get_result();
while ($row = $result_receitas->fetch_assoc()) {
    $receitas[] = $row;
    $total_receitas += $row['valor'];
}
$stmt_receitas->close();

// Query para buscar Despesas
$sql_despesas = "SELECT descricao, valor FROM despesas WHERE MONTH(data_despesa) = ? AND YEAR(data_despesa) = ?";
$stmt_despesas = $conn->prepare($sql_despesas);
$stmt_despesas->bind_param("ii", $mes, $ano);
$stmt_despesas->execute();
$result_despesas = $stmt_despesas->get_result();
while ($row = $result_despesas->fetch_assoc()) {
    $despesas[] = $row;
    $total_despesas += $row['valor'];
}
$stmt_despesas->close();
$conn->close();

$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$mes_nome = $meses[$mes];

// Conteúdo HTML para o PDF
$html = '
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 0; font-size: 16px; font-weight: normal; }
        .section { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .section-header { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .total-row { font-weight: bold; }
        .text-end { text-align: right; }
        .bg-success { background-color: #d4edda; }
        .bg-danger { background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Balancete</h1>
        <h2>Período: ' . $mes_nome . ' de ' . $ano . '</h2>
    </div>

    <div class="section">
        <div class="section-header bg-success">Receitas</div>
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="text-end">Valor</th>
                </tr>
            </thead>
            <tbody>';
                foreach ($receitas as $receita) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($receita['descricao']) . '</td>';
                    $html .= '<td class="text-end">R$ ' . number_format($receita['valor'], 2, ',', '.') . '</td>';
                    $html .= '</tr>';
                }
$html .= '
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total de Receitas</td>
                    <td class="text-end">R$ ' . number_format($total_receitas, 2, ',', '.') . '</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <div class="section-header bg-danger">Despesas</div>
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="text-end">Valor</th>
                </tr>
            </thead>
            <tbody>';
                foreach ($despesas as $despesa) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($despesa['descricao']) . '</td>';
                    $html .= '<td class="text-end">R$ ' . number_format($despesa['valor'], 2, ',', '.') . '</td>';
                    $html .= '</tr>';
                }
$html .= '
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total de Despesas</td>
                    <td class="text-end">R$ ' . number_format($total_despesas, 2, ',', '.') . '</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section text-end" style="margin-top: 50px;">
        <p><strong>Balanço Final:</strong> R$ ' . number_format($total_receitas - $total_despesas, 2, ',', '.') . '</p>
    </div>

</body>
</html>
';

// Instanciação e configuração do Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Saída do PDF para o navegador
$dompdf->stream("balancete_" . $mes . "-" . $ano . ".pdf", ["Attachment" => false]);
?>