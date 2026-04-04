<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao', 'financeiro'])) {
    die('Acesso negado.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id = (int) $_GET['id'];

// ADICIONADO: Busca o nome do convênio antes de excluí-lo
$stmt_nome = $conn->prepare("SELECT nome FROM convenios WHERE id = ?");
$stmt_nome->bind_param('i', $id);
$stmt_nome->execute();
$result_nome = $stmt_nome->get_result();
$convenio_nome = $result_nome->fetch_assoc()['nome'] ?? 'Desconhecido';
$stmt_nome->close();

$stmt = $conn->prepare("DELETE FROM convenios WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // ADICIONADO: Chama a função para registrar a ação
    $acao_log = "Excluiu o convênio: " . $convenio_nome . " (ID: " . $id . ")";
    registrar_log($acao_log, 'Convênios', $id);

    header('Location: listar.php?sucesso=excluido');
    exit;
} else {
    // Caso a exclusão falhe, redireciona com uma mensagem de erro
    header('Location: listar.php?erro=exclusao_falha');
    exit;
}
$stmt->close();