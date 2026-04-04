<?php
require_once "../../config/config.php";
require_once "../../config/log_auditoria.php"; // Adicionado: Inclui a função de log

$id = $_GET['id'] ?? null;

if ($id) {
    // É uma boa prática buscar o nome do paciente antes de excluí-lo,
    // para que o log possa ter uma descrição mais útil.
    $stmt_nome = $conn->prepare("SELECT nome FROM pacientes WHERE id = ?");
    $stmt_nome->bind_param("i", $id);
    $stmt_nome->execute();
    $result_nome = $stmt_nome->get_result();
    $paciente = $result_nome->fetch_assoc();
    $nome_paciente = $paciente['nome'] ?? 'Nome Desconhecido';
    $stmt_nome->close();
    
    // Agora, exclui o paciente
    $stmt = $conn->prepare("DELETE FROM pacientes WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Adicionado: Chama a função para registrar a ação
        registrar_log('Excluiu paciente: ' . $nome_paciente, 'Pacientes', $id);
    }
}

header("Location: listar.php");
exit;