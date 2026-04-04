<?php
// Inicia a sessão se ainda não tiver sido iniciada
session_start();

require_once __DIR__ . '/../../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../../config/log_auditoria.php';

if ($_SESSION['nivel_acesso'] !== 'administrador') {
    die('Acesso negado.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id = (int) $_GET['id'];

// Impedir que o administrador exclua a si mesmo (opcional, mas recomendado)
if ($id === $_SESSION['usuario_id']) {
    // Definir mensagem de erro na sessão para exibição no listar.php
    $_SESSION['erro_mensagem'] = "Você não pode excluir seu próprio usuário.";
    header('Location: listar.php');
    exit;
}

$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // ADICIONADO: Chama a função para registrar a ação de exclusão
    registrar_log('Excluiu usuário', 'Usuários', $id);
    
    // Define a mensagem de sucesso na sessão para ser exibida no listar.php
    $_SESSION['sucesso_mensagem'] = "Usuário excluído com sucesso!";
} else {
    // Define a mensagem de erro em caso de falha na exclusão
    $_SESSION['erro_mensagem'] = "Erro ao excluir usuário: " . $stmt->error;
}

header('Location: listar.php');
exit;