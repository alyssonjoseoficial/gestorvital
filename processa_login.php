<?php
session_start();

// Conexão com o banco de dados CENTRAL que armazena os IDs das clínicas
require_once __DIR__ . '/config/config_central.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Obtém o ID da clínica a partir do formulário
    $id_clinica = $_POST['id_clinica'] ?? null;

    if (empty($id_clinica)) {
        $_SESSION['erro_login'] = "ID da clínica não fornecido. Por favor, acesse através da página da sua clínica.";
        // CORRIGIDO: Redireciona para a página de login na raiz do domínio
        header("Location: /login.php");
        exit;
    }

    // 2. Conecta ao banco de dados central para buscar as credenciais
    $conn_central = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CENTRAL);
    if ($conn_central->connect_error) {
        $_SESSION['erro_login'] = "Erro na conexão com o sistema central. Tente mais tarde.";
        header("Location: /login.php?id=" . $id_clinica);
        exit;
    }

    $stmt_central = $conn_central->prepare("SELECT db_host, db_user, db_pass, db_name FROM clinicas WHERE codigo_clinica = ?");
    $stmt_central->bind_param("s", $id_clinica);
    $stmt_central->execute();
    $resultado_central = $stmt_central->get_result();
    $credenciais = $resultado_central->fetch_assoc();
    $conn_central->close();

    if (!$credenciais) {
        $_SESSION['erro_login'] = "Clínica não encontrada. Verifique o endereço.";
        header("Location: /login.php?id=" . $id_clinica);
        exit;
    }

    // 3. Conecta ao banco de dados específico da clínica
    $conn_clinica = new mysqli($credenciais['db_host'], $credenciais['db_user'], $credenciais['db_pass'], $credenciais['db_name']);
    if ($conn_clinica->connect_error) {
        $_SESSION['erro_login'] = "Erro na conexão com o banco de dados da clínica. Tente novamente mais tarde.";
        header("Location: /login.php?id=" . $id_clinica);
        exit;
    }

    // Incluímos o arquivo de log da clínica correta
    $log_file = __DIR__ . '/' . $id_clinica . '/private/config/log_auditoria.php';
    if (file_exists($log_file)) {
        require_once $log_file;
    }

    // 4. Lógica de autenticação
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email = ?";
    $stmt_login = $conn_clinica->prepare($sql);
    $stmt_login->bind_param("s", $email);
    $stmt_login->execute();
    $resultado_login = $stmt_login->get_result();
    $usuario = $resultado_login->fetch_assoc();
    $conn_clinica->close();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
        $_SESSION['id_clinica'] = $id_clinica;

        // Registro do log de auditoria
        if (function_exists('log_auditoria')) {
            log_auditoria("Login", "Login bem-sucedido do usuário: " . $email, $usuario['id']);
        }

        // CORRIGIDO: Redireciona para o caminho absoluto a partir da raiz do domínio
        header("Location: /" . $id_clinica . "/private/dashboard.php");
        exit;
    } else {
        $_SESSION['erro_login'] = "E-mail ou senha inválidos.";
        header("Location: /login.php?id=" . $id_clinica);
        exit;
    }
}
?>