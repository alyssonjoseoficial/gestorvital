<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/config.php';

// Verifica a conexão com o banco de dados
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha na conexão com o banco de dados.']);
    exit();
}

if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

$medico_id = $_GET['medico_id'] ?? null;
$data = $_GET['data'] ?? null;

if (!$medico_id || !is_numeric($medico_id) || !$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros inválidos.']);
    exit();
}

$limites = ['manha' => 0, 'tarde' => 0];
$agendados = ['manha' => 0, 'tarde' => 0];
$vagas_disponiveis = ['manha' => 0, 'tarde' => 0];

try {
    // 1. Busca os limites de pacientes para cada turno
    $sql_limites = "SELECT turno, limite_pacientes FROM agenda_medicos WHERE medico_id = ? AND data = ?";
    $stmt_limites = $conn->prepare($sql_limites);

    if (!$stmt_limites) {
        throw new Exception("Erro na preparação do SQL de limites: " . $conn->error);
    }
    
    $stmt_limites->bind_param("is", $medico_id, $data);
    $stmt_limites->execute();
    $resultado_limites = $stmt_limites->get_result();

    while ($row = $resultado_limites->fetch_assoc()) {
        $limites[$row['turno']] = (int)$row['limite_pacientes'];
    }
    $stmt_limites->close();

    // 2. Conta as consultas já agendadas para cada turno
    $sql_contagem = "SELECT turno, COUNT(*) AS total_agendado FROM consultas WHERE medico_id = ? AND data_consulta = ? AND status <> 'cancelada' GROUP BY turno";
    $stmt_contagem = $conn->prepare($sql_contagem);

    if (!$stmt_contagem) {
        throw new Exception("Erro na preparação do SQL de contagem: " . $conn->error);
    }

    $stmt_contagem->bind_param("is", $medico_id, $data);
    $stmt_contagem->execute();
    $resultado_contagem = $stmt_contagem->get_result();

    while ($row = $resultado_contagem->fetch_assoc()) {
        $agendados[$row['turno']] = (int)$row['total_agendado'];
    }
    $stmt_contagem->close();

    $vagas_disponiveis = [
        'manha' => $limites['manha'] - $agendados['manha'],
        'tarde' => $limites['tarde'] - $agendados['tarde'],
    ];

    echo json_encode($vagas_disponiveis);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

$conn->close();
?>