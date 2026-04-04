<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

$termo_busca = $_GET['query'] ?? '';
$termo_busca = trim($termo_busca);

if (empty($termo_busca) || strlen($termo_busca) < 3) {
    echo json_encode([]);
    exit();
}

// Prepara o termo de busca para a consulta SQL
$termo_like = "%" . $termo_busca . "%";

// Prepara a consulta SQL para buscar pacientes por nome ou CPF
$sql = "SELECT id, nome, cpf FROM pacientes WHERE nome LIKE ? OR cpf LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $termo_like, $termo_like);
$stmt->execute();
$resultado = $stmt->get_result();

$pacientes = [];
while ($paciente = $resultado->fetch_assoc()) {
    $pacientes[] = $paciente;
}
$stmt->close();
$conn->close();

echo json_encode($pacientes);
?>