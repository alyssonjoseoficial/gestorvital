<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$response = ['prontuario_id' => null];

$consulta_id = $_GET['consulta_id'] ?? 0;

if ($consulta_id > 0) {
    $stmt = $conn->prepare("SELECT id FROM prontuarios WHERE consulta_id = ? LIMIT 1");
    $stmt->bind_param("i", $consulta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response['prontuario_id'] = $row['id'];
    }
}

echo json_encode($response);