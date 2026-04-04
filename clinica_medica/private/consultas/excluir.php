<?php
require_once __DIR__ . '/../../config/config.php';

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica o nível de acesso
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

// Verifica se o ID foi passado e é válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id = (int) $_GET['id'];

// Executa a exclusão de forma segura
$stmt = $conn->prepare("DELETE FROM consultas WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: listar.php?msg=excluido');
    exit;
} else {
    $stmt->close();
    die('Erro ao excluir a consulta.');
}
