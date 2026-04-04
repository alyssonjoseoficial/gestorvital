<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'medico'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consulta_id = $_POST['consulta_id'] ?? null;
    $descricao = $_POST['descricao'] ?? null;

    if (empty($consulta_id) || empty($descricao)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos. Ambos os campos "consulta_id" e "descricao" são obrigatórios.']);
        exit;
    }

    try {
        // ADICIONADO: Busca o nome do paciente e médico para o log de auditoria
        $stmt_info = $conn->prepare("
            SELECT p.nome AS paciente_nome, m.nome AS medico_nome
            FROM consultas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN medicos m ON c.medico_id = m.id
            WHERE c.id = ?
        ");
        $stmt_info->bind_param("i", $consulta_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $info_solicitacao = $result_info->fetch_assoc();
        $stmt_info->close();
        
        $paciente_nome = $info_solicitacao['paciente_nome'] ?? 'Desconhecido';
        $medico_nome = $info_solicitacao['medico_nome'] ?? 'Desconhecido';

        // Lógica para INSERT (sempre cria um novo registro)
        $sql = "INSERT INTO solicitacoes_exame (consulta_id, descricao, data_solicitacao) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Erro ao preparar a declaração SQL (INSERT): " . $conn->error);
        }
        $data_solicitacao = date('Y-m-d');
        $stmt->bind_param("iss", $consulta_id, $descricao, $data_solicitacao);

        if ($stmt->execute()) {
            // ADICIONADO: Obtém o ID da solicitação recém-criada
            $nova_solicitacao_id = $conn->insert_id;
            
            // ADICIONADO: Prepara a mensagem de log
            $acao_log = "Criou solicitação de exame (ID {$nova_solicitacao_id}) para o paciente '{$paciente_nome}' referente à consulta do médico '{$medico_nome}'.";
            
            // ADICIONADO: Chama a função para registrar a ação
            registrar_log($acao_log, 'Solicitação Exame', $nova_solicitacao_id);

            echo json_encode(['success' => true, 'message' => 'Solicitação de exame salva com sucesso.']);
        } else {
            throw new Exception("Erro ao executar a declaração SQL: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Exceção capturada: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ocorreu um erro no servidor: ' . $e->getMessage()]);
    }
    
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
?>