<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prontuario_id = $_POST['id'] ?? null; // ID do prontuário para edição
    $consulta_id = $_POST['consulta_id'] ?? null;
    $queixa_principal = $_POST['queixa_principal'] ?? '';
    $historia_doenca_atual = $_POST['historia_doenca_atual'] ?? '';
    $antecedentes_pessoais = $_POST['antecedentes_pessoais'] ?? '';
    $antecedentes_familiares = $_POST['antecedentes_familiares'] ?? '';
    $exame_fisico = $_POST['exame_fisico'] ?? '';
    $exames_complementares = $_POST['exames_complementares'] ?? '';
    $diagnostico = $_POST['diagnostico'] ?? '';
    $prescricao = $_POST['prescricao'] ?? '';
    $orientacoes = $_POST['orientacoes'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';

    // Buscar o ID do médico e paciente a partir da consulta
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

    if ($prontuario_id) {
        // ADICIONADO: Busca os dados originais do prontuário para o log de auditoria
        $stmt_old = $conn->prepare("SELECT * FROM prontuarios WHERE id = ?");
        $stmt_old->bind_param("i", $prontuario_id);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        $prontuario_antigo = $result_old->fetch_assoc();
        $stmt_old->close();

        // Lógica para ATUALIZAR um prontuário existente
        $stmt = $conn->prepare("
            UPDATE prontuarios
            SET queixa_principal = ?, historia_doenca_atual = ?, antecedentes_pessoais = ?,
                antecedentes_familiares = ?, exame_fisico = ?, exames_complementares = ?,
                diagnostico = ?, prescricao = ?, orientacoes = ?, observacoes = ?,
                atualizado_em = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssssssssi",
            $queixa_principal, $historia_doenca_atual, $antecedentes_pessoais,
            $antecedentes_familiares, $exame_fisico, $exames_complementares,
            $diagnostico, $prescricao, $orientacoes, $observacoes, $prontuario_id
        );
        
    } else {
        // Lógica para INSERIR um novo prontuário
        $stmt = $conn->prepare("
            INSERT INTO prontuarios
            (consulta_id, medico_id, paciente_id, data_consulta, queixa_principal, historia_doenca_atual, antecedentes_pessoais, antecedentes_familiares, exame_fisico, exames_complementares, diagnostico, prescricao, orientacoes, observacoes, criado_em)
            VALUES (?, ?, ?, (SELECT data_consulta FROM consultas WHERE id = ?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "iiiissssssssss",
            $consulta_id, $medico_id, $paciente_id, $consulta_id,
            $queixa_principal, $historia_doenca_atual, $antecedentes_pessoais,
            $antecedentes_familiares, $exame_fisico, $exames_complementares,
            $diagnostico, $prescricao, $orientacoes, $observacoes
        );
    }
    
    if ($stmt->execute()) {
        $response['success'] = true;
        
        // ADICIONADO: Lógica de registro de auditoria
        if ($prontuario_id) {
            // Log para ATUALIZAÇÃO
            $alteracoes = [];
            $campos_auditados = [
                'queixa_principal', 'historia_doenca_atual', 'antecedentes_pessoais',
                'antecedentes_familiares', 'exame_fisico', 'exames_complementares',
                'diagnostico', 'prescricao', 'orientacoes', 'observacoes'
            ];
            foreach ($campos_auditados as $campo) {
                $valor_antigo = $prontuario_antigo[$campo] ?? '';
                $valor_novo = $_POST[$campo] ?? '';
                if (trim($valor_antigo) !== trim($valor_novo)) {
                    $alteracoes[] = ucfirst(str_replace('_', ' ', $campo));
                }
            }

            $acao_log = "Atualizou o prontuário (ID {$prontuario_id}) do paciente '{$paciente_nome}' referente à consulta {$consulta_id}";
            if (!empty($alteracoes)) {
                $acao_log .= ". Campos alterados: " . implode(', ', $alteracoes) . ".";
            }
            registrar_log($acao_log, 'Prontuários', $prontuario_id);
            $response['message'] = "Prontuário atualizado com sucesso!";
        } else {
            // Log para CRIAÇÃO
            $novo_prontuario_id = $conn->insert_id;
            $acao_log = "Criou novo prontuário (ID {$novo_prontuario_id}) para o paciente '{$paciente_nome}' referente à consulta {$consulta_id}";
            registrar_log($acao_log, 'Prontuários', $novo_prontuario_id);
            $response['message'] = "Prontuário salvo com sucesso!";
        }
        
        // Atualizar status da consulta para 'realizada'
        $stmt_status = $conn->prepare("UPDATE consultas SET status = 'realizada' WHERE id = ?");
        $stmt_status->bind_param("i", $consulta_id);
        $stmt_status->execute();
        $stmt_status->close();
    } else {
        $response['message'] = "Erro ao salvar prontuário: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = "Método de requisição inválido.";
}

echo json_encode($response);
$conn->close();