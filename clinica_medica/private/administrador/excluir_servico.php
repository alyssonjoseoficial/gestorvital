<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador'])) {
    die("Acesso negado.");
}

$servico_id = $_GET['id'] ?? null;
if (!$servico_id) {
    header('Location: listar_servicos.php');
    exit;
}

// Busca o nome do serviço antes de excluí-lo para registrar no log
$stmt_nome = $conn->prepare("SELECT nome FROM servicos WHERE id = ?");
$stmt_nome->bind_param("i", $servico_id);
$stmt_nome->execute();
$result_nome = $stmt_nome->get_result();
$servico = $result_nome->fetch_assoc();
$nome_servico = $servico['nome'] ?? 'Serviço Desconhecido';
$stmt_nome->close();

// Exclui o serviço
$stmt_excluir = $conn->prepare("DELETE FROM servicos WHERE id = ?");
$stmt_excluir->bind_param("i", $servico_id);

if ($stmt_excluir->execute()) {
    // ADICIONADO: Chama a função para registrar a ação
    registrar_log('Excluiu serviço: ' . $nome_servico, 'Serviços', $servico_id);
    $_SESSION['sucesso_mensagem'] = "Serviço excluído com sucesso!";
} else {
    $_SESSION['erro_mensagem'] = "Erro ao excluir o serviço: " . $stmt_excluir->error;
}
$stmt_excluir->close();

header('Location: listar_servicos.php');
exit;