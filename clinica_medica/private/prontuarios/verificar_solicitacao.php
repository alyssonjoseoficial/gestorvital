<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$consulta_id = $_GET['consulta_id'] ?? 0;

if (!$consulta_id) {
    echo json_encode(['existe_solicitacao' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM solicitacoes_exame WHERE consulta_id = ? LIMIT 1");
$stmt->bind_param("i", $consulta_id);
$stmt->execute();
$result = $stmt->get_result();

$existe_solicitacao = $result->num_rows > 0;

$stmt->close();
$conn->close();

echo json_encode(['existe_solicitacao' => $existe_solicitacao]);
?>