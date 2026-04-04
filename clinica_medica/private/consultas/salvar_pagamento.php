<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consulta_id = $_POST['consulta_id'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';
    $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
    $observacoes = $_POST['observacoes'] ?? '';

    if (empty($consulta_id) || empty($valor) || empty($metodo_pagamento) || !is_numeric($valor)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // 1. Inserir o novo pagamento na tabela `pagamentos`
        $sql_pagamento = "INSERT INTO pagamentos (consulta_id, valor, metodo_pagamento, data_pagamento, observacoes) VALUES (?, ?, ?, ?, ?)";
        $stmt_pagamento = $conn->prepare($sql_pagamento);
        if (!$stmt_pagamento) {
            throw new Exception("Erro na preparação da consulta de pagamento: " . $conn->error);
        }
        $stmt_pagamento->bind_param("idsss", $consulta_id, $valor, $metodo_pagamento, $data_pagamento, $observacoes);
        
        if (!$stmt_pagamento->execute()) {
            throw new Exception("Erro ao inserir pagamento: " . $stmt_pagamento->error);
        }

        // ADICIONADO: Obtém o ID do pagamento recém-criado
        $novo_pagamento_id = $conn->insert_id;

        // 2. Atualizar o status da consulta para "pago"
        $sql_consulta = "UPDATE consultas SET status_pagamento = 'pago' WHERE id = ?";
        $stmt_consulta = $conn->prepare($sql_consulta);
        if (!$stmt_consulta) {
            throw new Exception("Erro na preparação da consulta de atualização: " . $conn->error);
        }
        $stmt_consulta->bind_param("i", $consulta_id);
        
        if (!$stmt_consulta->execute()) {
            throw new Exception("Erro ao atualizar status da consulta: " . $stmt_consulta->error);
        }

        $conn->commit();

        // ADICIONADO: Buscar o nome do paciente e do médico para o log
        $stmt_nomes = $conn->prepare("
            SELECT p.nome AS paciente_nome, m.nome AS medico_nome
            FROM consultas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN medicos m ON c.medico_id = m.id
            WHERE c.id = ?
        ");
        $stmt_nomes->bind_param("i", $consulta_id);
        $stmt_nomes->execute();
        $result_nomes = $stmt_nomes->get_result();
        $nomes = $result_nomes->fetch_assoc();
        $stmt_nomes->close();
        
        $paciente_nome = $nomes['paciente_nome'] ?? 'Paciente Desconhecido';
        $medico_nome = $nomes['medico_nome'] ?? 'Médico Desconhecido';

        // ADICIONADO: Chama a função para registrar o log
        $valor_formatado = number_format($valor, 2, ',', '.');
        $acao_log = "Registrou pagamento de R$ {$valor_formatado} para a consulta do paciente {$paciente_nome} com o médico {$medico_nome}.";
        registrar_log($acao_log, 'Pagamentos', $novo_pagamento_id);

        echo json_encode(['success' => true, 'message' => 'Pagamento registrado e status da consulta atualizado com sucesso.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    if (isset($stmt_pagamento)) {
        $stmt_pagamento->close();
    }
    if (isset($stmt_consulta)) {
        $stmt_consulta->close();
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}

$conn->close();
?>