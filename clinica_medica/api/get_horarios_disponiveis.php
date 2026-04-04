<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/config.php';

// Acesso: Apenas recepção e administrador podem usar a API
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

$medico_id = $_GET['medico_id'] ?? null;
$data = $_GET['data'] ?? null;

if (!$medico_id || !is_numeric($medico_id) || !$data) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Parâmetros inválidos.']);
    exit();
}

// Horário de atendimento do médico (pode ser buscado do banco, mas por enquanto usamos um valor fixo)
$hora_inicio_expediente = '08:00';
$hora_fim_expediente = '18:00';
$duracao_consulta = 15; // em minutos

// Lista de todos os horários possíveis
$horarios_totais = [];
$start = new DateTime($data . ' ' . $hora_inicio_expediente);
$end = new DateTime($data . ' ' . $hora_fim_expediente);
while ($start < $end) {
    $horarios_totais[] = $start->format('H:i');
    $start->modify("+{$duracao_consulta} minutes");
}

// Busca as consultas já agendadas para o médico e a data
$sql = "SELECT hora_consulta FROM consultas WHERE medico_id = ? AND data_consulta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $medico_id, $data);
$stmt->execute();
$resultado = $stmt->get_result();

$horarios_ocupados = [];
while ($consulta = $resultado->fetch_assoc()) {
    $horarios_ocupados[] = substr($consulta['hora_consulta'], 0, 5);
}
$stmt->close();

// Remove os horários ocupados da lista total
$horarios_disponiveis = array_values(array_diff($horarios_totais, $horarios_ocupados));

// Retorna o resultado como JSON
echo json_encode(['horarios' => $horarios_disponiveis]);

$conn->close();
?>