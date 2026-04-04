<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atestado_id = $_POST['id'] ?? null;
    $consulta_id = $_POST['consulta_id'] ?? null;
    $cid = $_POST['cid'] ?? '';
    $dias_repouso = $_POST['dias_repouso'] ?? 0;
    $motivo_repouso = $_POST['motivo_repouso'] ?? '';
    $data_atestado = date('Y-m-d');

    if (!$consulta_id) {
        $response['message'] = "ID da consulta não fornecido.";
        echo json_encode($response);
        exit();
    }

    $stmt_info = $conn->prepare("
        SELECT c.medico_id, c.paciente_id, p.nome AS paciente_nome
        FROM consultas c
        JOIN pacientes p ON c.paciente_id = p.id
        WHERE c.id = ?
    ");
    $stmt_info->bind_param("i", $consulta_id);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    $consulta_info = $result_info->fetch_assoc();
    $stmt_info->close();

    if (!$consulta_info) {
        $response['message'] = "Consulta não encontrada.";
        echo json_encode($response);
        exit();
    }
    
    $medico_id = $consulta_info['medico_id'];
    $paciente_id = $consulta_info['paciente_id'];
    $paciente_nome = $consulta_info['paciente_nome'];

    if ($atestado_id) {
        // Lógica para ATUALIZAR um atestado existente
        $stmt = $conn->prepare("
            UPDATE atestados
            SET cid = ?, dias_repouso = ?, motivo_repouso = ?, atualizado_em = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $cid, $dias_repouso, $motivo_repouso, $atestado_id);
    } else {
        // Lógica para INSERIR um novo atestado
        $stmt = $conn->prepare("
            INSERT INTO atestados
            (consulta_id, medico_id, paciente_id, cid, dias_repouso, motivo_repouso, data_atestado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iississ", $consulta_id, $medico_id, $paciente_id, $cid, $dias_repouso, $motivo_repouso, $data_atestado);
    }
    
    if ($stmt->execute()) {
        $acao_log = '';
        $id_log = 0;

        if ($atestado_id) {
            // Se for uma atualização
            $acao_log = 'Editou atestado para o paciente ' . $paciente_nome;
            $id_log = $atestado_id;
        } else {
            // Se for um novo atestado
            $novo_atestado_id = $conn->insert_id;
            $acao_log = 'Criou atestado para o paciente ' . $paciente_nome;
            $id_log = $novo_atestado_id;
        }

        // ADICIONADO: Chama a função para registrar a ação
        registrar_log($acao_log, 'Atestados', $id_log);

        $response['success'] = true;
        $response['message'] = "Atestado salvo com sucesso!";
    } else {
        $response['message'] = "Erro ao salvar atestado: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = "Método de requisição inválido.";
}

echo json_encode($response);
$conn->close();