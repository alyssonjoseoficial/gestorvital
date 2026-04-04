<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao', 'medico'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

$medico_id = $_GET['medico_id'] ?? null;

if (!$medico_id || !is_numeric($medico_id)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do médico inválido.']);
    exit();
}

$sql = "SELECT c.id, p.nome AS paciente_nome, m.nome AS medico_nome, c.data_consulta, c.hora_consulta, c.status FROM consultas c INNER JOIN pacientes p ON c.paciente_id = p.id INNER JOIN medicos m ON c.medico_id = m.id WHERE c.medico_id = ? ORDER BY c.data_consulta ASC, c.hora_consulta ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$resultado = $stmt->get_result();

$consultas = [];
while ($consulta = $resultado->fetch_assoc()) {
    $title = $consulta['paciente_nome'];
    $color = '';
    switch ($consulta['status']) {
        case 'agendada': $color = '#0d6efd'; break;
        case 'realizada': $color = '#198754'; break;
        case 'cancelada': $color = '#dc3545'; break;
        default: $color = '#6c757d'; break;
    }
    $consultas[] = [
        'id' => $consulta['id'],
        'title' => $title,
        'start' => $consulta['data_consulta'] . 'T' . $consulta['hora_consulta'],
        'backgroundColor' => $color,
        'borderColor' => $color
    ];
}

header('Content-Type: application/json');
echo json_encode($consultas);
?>