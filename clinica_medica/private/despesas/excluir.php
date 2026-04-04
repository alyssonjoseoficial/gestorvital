<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'financeiro'])) {
    die('Acesso negado.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['erro_mensagem'] = 'ID da despesa inválido.';
    header('Location: relatorio.php');
    exit;
}

$id = (int) $_GET['id'];

// Antes de excluir, busca os dados da despesa para o log
$stmt_despesa = $conn->prepare("SELECT descricao, valor FROM despesas WHERE id = ?");
$stmt_despesa->bind_param('i', $id);
$stmt_despesa->execute();
$result_despesa = $stmt_despesa->get_result();
$despesa = $result_despesa->fetch_assoc();
$stmt_despesa->close();

if (!$despesa) {
    $_SESSION['erro_mensagem'] = 'Despesa não encontrada.';
    header('Location: relatorio.php');
    exit;
}

$descricao_despesa = $despesa['descricao'];
$valor_despesa = number_format($despesa['valor'], 2, ',', '.');

$conn->begin_transaction();

try {
    // Exclui a despesa
    $stmt_delete = $conn->prepare("DELETE FROM despesas WHERE id = ?");
    $stmt_delete->bind_param('i', $id);
    if (!$stmt_delete->execute()) {
        throw new Exception("Erro ao excluir a despesa.");
    }

    $conn->commit();
    
    // Registra a ação no log
    $acao_log = "Excluiu a despesa (ID: {$id}) de R$ {$valor_despesa} com a descrição: '{$descricao_despesa}'";
    registrar_log($acao_log, 'Despesas', $id);

    $_SESSION['sucesso_mensagem'] = 'Despesa excluída com sucesso!';
    header('Location: relatorio.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['erro_mensagem'] = $e->getMessage();
    header('Location: relatorio.php');
    exit;
} finally {
    if (isset($stmt_delete)) {
        $stmt_delete->close();
    }
    $conn->close();
}
?>