<?php
// Arquivo: config/log_auditoria.php

/**
 * Registra uma ação de auditoria no banco de dados.
 *
 * @param string $acao A descrição da ação realizada.
 * @param string $modulo O módulo onde a ação ocorreu (ex: 'Pacientes', 'Consultas').
 * @param int|null $registro_id O ID do registro afetado, se aplicável.
 */
function registrar_log($acao, $modulo, $registro_id = null) {
    global $conn;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $usuario_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';
    $nivel_acesso = $_SESSION['nivel_acesso'] ?? 'Desconhecido';

    // Verificação de segurança para garantir que os dados da sessão existam
    if ($usuario_id === null) {
        return; // Não registra log se o usuário não estiver logado
    }

    // Consulta SQL para inserir o log
    $sql = "INSERT INTO logs_auditoria (usuario_id, usuario_nome, nivel_acesso, acao, modulo, registro_id) VALUES (?, ?, ?, ?, ?, ?)";
    
    // Prepara o statement
    $stmt = $conn->prepare($sql);
    
    // Tipos de parâmetros: i (inteiro), s (string), s (string), s (string), s (string), i (inteiro)
    $stmt->bind_param("issssi", $usuario_id, $usuario_nome, $nivel_acesso, $acao, $modulo, $registro_id);

    // Executa a query
    $stmt->execute();
    
    // Fecha o statement
    $stmt->close();
}