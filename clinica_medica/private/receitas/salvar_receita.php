<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receita_id = $_POST['id'] ?? null;
    $consulta_id = $_POST['consulta_id'] ?? null;
    $receita = $_POST['receita'] ?? '';
    $instrucoes_gerais = $_POST['instrucoes_gerais'] ?? '';
    $data_receita = date('Y-m-d');

    if (!$consulta_id) {
        $response['message'] = "ID da consulta não fornecido.";
        echo json_encode($response);
        exit();
    }

    // Busca o ID do médico e paciente e seus nomes para o log
    $stmt_info = $conn->prepare("
        SELECT c.medico_id, c.paciente_id, m.nome AS medico_nome, p.nome AS paciente_nome
        FROM consultas c
        JOIN medicos m ON c.medico_id = m.id
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
    $medico_nome = $consulta_info['medico_nome'];

    if ($receita_id) {
        // ADICIONADO: Busca os dados originais da receita para o log de auditoria
        $stmt_old = $conn->prepare("SELECT receita, instrucoes_gerais FROM receitas WHERE id = ?");
        $stmt_old->bind_param("i", $receita_id);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        $receita_antiga = $result_old->fetch_assoc();
        $stmt_old->close();
        
        // Lógica para ATUALIZAR uma receita existente
        $stmt = $conn->prepare("
            UPDATE receitas
            SET receita = ?, instrucoes_gerais = ?, atualizado_em = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $receita, $instrucoes_gerais, $receita_id);

    } else {
        // Lógica para INSERIR uma nova receita
        $stmt = $conn->prepare("
            INSERT INTO receitas
            (consulta_id, medico_id, paciente_id, receita, instrucoes_gerais, data_receita)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissss", $consulta_id, $medico_id, $paciente_id, $receita, $instrucoes_gerais, $data_receita);
    }
    
    if ($stmt->execute()) {
        $response['success'] = true;
        
        // ADICIONADO: Lógica de registro de auditoria
        if ($receita_id) {
            // Log para ATUALIZAÇÃO
            $alteracoes = [];
            if ($receita_antiga['receita'] !== $receita) {
                $alteracoes[] = 'conteúdo da receita';
            }
            if ($receita_antiga['instrucoes_gerais'] !== $instrucoes_gerais) {
                $alteracoes[] = 'instruções gerais';
            }
            
            $acao_log = "Atualizou receita (ID {$receita_id}) do paciente '{$paciente_nome}' referente à consulta {$consulta_id}";
            if (!empty($alteracoes)) {
                $acao_log .= ". Campos alterados: " . implode(', ', $alteracoes) . ".";
            }
            registrar_log($acao_log, 'Receita', $receita_id);
            $response['message'] = "Receita atualizada com sucesso!";
        } else {
            // Log para CRIAÇÃO
            $nova_receita_id = $conn->insert_id;
            $acao_log = "Criou nova receita (ID {$nova_receita_id}) para o paciente '{$paciente_nome}' referente à consulta {$consulta_id}";
            registrar_log($acao_log, 'Receita', $nova_receita_id);
            $response['message'] = "Receita salva com sucesso!";
        }
    } else {
        $response['message'] = "Erro ao salvar receita: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = "Método de requisição inválido.";
}

echo json_encode($response);
$conn->close();