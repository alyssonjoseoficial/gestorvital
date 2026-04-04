<?php
ob_start();
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medico_id = trim($_POST['medico_id'] ?? '');
    $paciente_id = trim($_POST['paciente_id'] ?? '');
    $data_consulta = trim($_POST['data_consulta'] ?? '');
    $turno = trim($_POST['turno'] ?? '');
    $hora_consulta = trim($_POST['hora_consulta'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $status = trim($_POST['status'] ?? 'agendada');
    $servico_id = trim($_POST['servico_id'] ?? '');

    if (empty($medico_id) || empty($paciente_id) || empty($data_consulta) || empty($servico_id)) {
        $_SESSION['erro_mensagem'] = 'Erro: Os campos "Médico", "Paciente", "Data" e "Serviço" são obrigatórios.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // ADICIONADO: Busca o nome do paciente e do médico para o log
    $stmt_nomes = $conn->prepare("
        SELECT p.nome AS paciente_nome, m.nome AS medico_nome
        FROM pacientes p, medicos m
        WHERE p.id = ? AND m.id = ?
    ");
    $stmt_nomes->bind_param("ii", $paciente_id, $medico_id);
    $stmt_nomes->execute();
    $result_nomes = $stmt_nomes->get_result();
    $nomes = $result_nomes->fetch_assoc();
    $stmt_nomes->close();

    $paciente_nome = $nomes['paciente_nome'] ?? 'Paciente Desconhecido';
    $medico_nome = $nomes['medico_nome'] ?? 'Médico Desconhecido';

    if (!empty($hora_consulta)) {
        $turno = 'encaixe';
        $sql_insert = "INSERT INTO consultas (medico_id, paciente_id, data_consulta, hora_consulta, turno, status, observacoes, servico_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            $_SESSION['erro_mensagem'] = 'Erro na preparação da consulta de inserção: ' . $conn->error;
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
        $stmt_insert->bind_param("iisssssi", $medico_id, $paciente_id, $data_consulta, $hora_consulta, $turno, $status, $observacoes, $servico_id);

    } else {
        if (empty($turno)) {
            $_SESSION['erro_mensagem'] = 'Erro: O turno deve ser selecionado para agendamentos normais.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
        
        $sql_vagas = "SELECT (SELECT limite_pacientes FROM agenda_medicos WHERE medico_id = ? AND data = ? AND turno = ?) - (SELECT COUNT(*) FROM consultas WHERE medico_id = ? AND data_consulta = ? AND turno = ?) AS vagas_restantes";
        $stmt_vagas = $conn->prepare($sql_vagas);
        if (!$stmt_vagas) {
            $_SESSION['erro_mensagem'] = 'Erro na preparação da consulta de vagas: ' . $conn->error;
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
        $stmt_vagas->bind_param("isssis", $medico_id, $data_consulta, $turno, $medico_id, $data_consulta, $turno);
        $stmt_vagas->execute();
        $resultado_vagas = $stmt_vagas->get_result();
        $vagas = $resultado_vagas->fetch_assoc()['vagas_restantes'];
        $stmt_vagas->close();

        if ($vagas <= 0) {
            $_SESSION['erro_mensagem'] = 'Erro: Não há vagas disponíveis neste turno para o médico.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
        
        $sql_insert = "INSERT INTO consultas (medico_id, paciente_id, data_consulta, turno, status, observacoes, servico_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            $_SESSION['erro_mensagem'] = 'Erro na preparação da consulta de inserção: ' . $conn->error;
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
        $stmt_insert->bind_param("iissssi", $medico_id, $paciente_id, $data_consulta, $turno, $status, $observacoes, $servico_id);
    }

    if ($stmt_insert->execute()) {
        $nova_consulta_id = $conn->insert_id;
        
        // ADICIONADO: Chama a função para registrar a ação
        $acao_log = 'Agendou consulta (ID ' . $nova_consulta_id . ') para o paciente ' . $paciente_nome . ' com o médico ' . $medico_nome . ' no dia ' . date('d/m/Y', strtotime($data_consulta));
        registrar_log($acao_log, 'Consultas', $nova_consulta_id);

        $_SESSION['sucesso_mensagem'] = 'Consulta agendada com sucesso!';
        header('Location: ../consultas/listar.php');
        exit();
    } else {
        $_SESSION['erro_mensagem'] = 'Erro ao agendar a consulta: ' . $stmt_insert->error;
        header('Location: ../consultas/listar.php');
        exit();
    }

    $stmt_insert->close();
    $conn->close();
}
ob_end_flush();
?>