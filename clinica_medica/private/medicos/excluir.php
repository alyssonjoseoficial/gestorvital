<?php
// Inicia a sessão se ainda não tiver sido iniciada
session_start();

require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id = (int) $_GET['id'];

// É uma boa prática buscar o nome do médico antes de excluí-lo
// para que o log tenha uma descrição mais útil.
$stmt_nome = $conn->prepare("SELECT nome FROM medicos WHERE id = ?");
$stmt_nome->bind_param("i", $id);
$stmt_nome->execute();
$result_nome = $stmt_nome->get_result();
$medico = $result_nome->fetch_assoc();
$nome_medico = $medico['nome'] ?? 'Nome Desconhecido';
$stmt_nome->close();

// Agora, exclui o médico
$stmt = $conn->prepare("DELETE FROM medicos WHERE id=?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // ADICIONADO: Chama a função para registrar a ação
    registrar_log('Excluiu médico: ' . $nome_medico, 'Médicos', $id);
    
    // Define a mensagem de sucesso na sessão para ser exibida no listar.php
    $_SESSION['sucesso_mensagem'] = "Médico excluído com sucesso!";
} else {
    // Define a mensagem de erro em caso de falha na exclusão
    $_SESSION['erro_mensagem'] = "Erro ao excluir médico: " . $stmt->error;
}

header('Location: listar.php');
exit;