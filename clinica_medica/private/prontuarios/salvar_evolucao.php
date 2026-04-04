<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prontuario_id = $_POST['prontuario_id'] ?? null;
    $texto_evolucao = $_POST['texto_evolucao'] ?? '';

    if (!$prontuario_id || empty($texto_evolucao)) {
        $response['message'] = "ID do prontuário ou texto da evolução não fornecido.";
        echo json_encode($response);
        exit();
    }
    
    // ADICIONADO: Busca o nome do paciente para o log de auditoria
    $stmt_paciente = $conn->prepare("
        SELECT p.nome AS paciente_nome
        FROM prontuarios pr
        JOIN pacientes p ON pr.paciente_id = p.id
        WHERE pr.id = ?
    ");
    $stmt_paciente->bind_param("i", $prontuario_id);
    $stmt_paciente->execute();
    $result_paciente = $stmt_paciente->get_result();
    $paciente_info = $result_paciente->fetch_assoc();
    $stmt_paciente->close();
    
    $paciente_nome = $paciente_info['paciente_nome'] ?? 'Paciente Desconhecido';

    $stmt = $conn->prepare("
        INSERT INTO evolucao_prontuarios
        (prontuario_id, texto_evolucao)
        VALUES (?, ?)
    ");
    $stmt->bind_param("is", $prontuario_id, $texto_evolucao);
    
    if ($stmt->execute()) {
        // ADICIONADO: Obtém o ID da evolução recém-criada
        $nova_evolucao_id = $conn->insert_id;
        
        // ADICIONADO: Prepara a mensagem de log
        $acao_log = "Adicionou nova evolução (ID {$nova_evolucao_id}) ao prontuário do paciente '{$paciente_nome}' (Prontuário ID: {$prontuario_id})";
        
        // ADICIONADO: Chama a função para registrar a ação
        registrar_log($acao_log, 'Evolução Prontuário', $nova_evolucao_id);

        $response['success'] = true;
        $response['message'] = "Evolução salva com sucesso!";
    } else {
        $response['message'] = "Erro ao salvar evolução: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = "Método de requisição inválido.";
}

echo json_encode($response);
$conn->close();