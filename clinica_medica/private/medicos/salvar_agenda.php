<?php
ob_start();
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../../config/config.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medico_id = $_POST['medico_id'] ?? null;
    $data = $_POST['data'] ?? null;
    $limite_manha = $_POST['limite_manha'] ?? null;
    $limite_tarde = $_POST['limite_tarde'] ?? null;

    if (!$medico_id || !$data) {
        die('ID do médico e data são obrigatórios.');
    }

    // Usamos INSERT ... ON DUPLICATE KEY UPDATE para evitar registros duplicados
    $sql = "INSERT INTO agenda_medicos (medico_id, data, turno, limite_pacientes)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE limite_pacientes = VALUES(limite_pacientes)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Erro na preparação da consulta: ' . $conn->error);
    }

    $conn->begin_transaction();

    try {
        // Insere/atualiza o turno da manhã
        $turno_manha = 'manha';
        $stmt->bind_param("isss", $medico_id, $data, $turno_manha, $limite_manha);
        $stmt->execute();

        // Insere/atualiza o turno da tarde
        $turno_tarde = 'tarde';
        $stmt->bind_param("isss", $medico_id, $data, $turno_tarde, $limite_tarde);
        $stmt->execute();

        $conn->commit();
        $stmt->close();
        $conn->close();

        header('Location: listar.php?sucesso=Agenda salva com sucesso!');
        exit();

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo 'Erro ao salvar a agenda: ' . $e->getMessage();
    }
}

ob_end_flush();
?>