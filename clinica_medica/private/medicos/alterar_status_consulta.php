<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($nivel !== 'medico' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

$consulta_id = $_POST['id'] ?? null;
$novo_status = $_POST['status'] ?? null;

if (!$consulta_id || !$novo_status) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros inválidos.']);
    exit();
}

// Buscar o ID do médico vinculado ao usuário logado
$stmt_med = $conn->prepare("
    SELECT m.id
    FROM medicos m
    INNER JOIN medico_usuario mu ON mu.medico_id = m.id
    WHERE mu.usuario_id = ?
    LIMIT 1
");
$stmt_med->bind_param("i", $usuario_id);
$stmt_med->execute();
$result_med = $stmt_med->get_result();
$medico = $result_med->fetch_assoc();

if (!$medico) {
    http_response_code(403);
    echo json_encode(['error' => 'Médico não encontrado para o usuário logado.']);
    exit();
}
$medico_id = $medico['id'];

// Atualizar o status da consulta, garantindo que o médico só possa alterar as próprias consultas
$sql = "UPDATE consultas SET status = ? WHERE id = ? AND medico_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $novo_status, $consulta_id, $medico_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar o status.']);
}

$stmt->close();
$conn->close();
?>