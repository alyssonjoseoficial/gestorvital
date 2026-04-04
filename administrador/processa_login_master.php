<?php
try {
    // Inclui o arquivo de configuração central
    require_once __DIR__ . '/../config/config_central.php';

    // O caminho base para a raiz do projeto na URL do navegador
    $caminho_base_do_projeto = '/';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $usuario = $_POST['usuario'] ?? '';
        $senha_digitada = $_POST['senha'] ?? '';

        $conn_central = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CENTRAL);
        if ($conn_central->connect_error) {
            header("Location: login_master.php?erro=conexao");
            exit;
        }

        $stmt = $conn_central->prepare("SELECT id, nome, senha, nivel_acesso FROM usuarios_master WHERE nome = ?");
        if (!$stmt) {
            header("Location: login_master.php?erro=sql");
            exit;
        }
        
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user_info = $result->fetch_assoc();
            
            if (password_verify($senha_digitada, $user_info['senha']) && $user_info['nivel_acesso'] === 'master') {
                $_SESSION['usuario_id'] = $user_info['id'];
                $_SESSION['usuario_nome'] = $user_info['nome'];
                $_SESSION['nivel_acesso'] = $user_info['nivel_acesso'];

                header("Location: " . $caminho_base_do_projeto . "administrador/dashboard.php");
                exit;
            }
        }
        
        if (isset($stmt)) $stmt->close();
        if (isset($conn_central)) $conn_central->close();
        header("Location: login_master.php?erro=1");
        exit;
    }
} catch (Exception $e) {
    // Se ocorrer um erro, ele será capturado aqui e exibido
    echo "<h1>Erro Fatal no processa_login_master.php</h1>";
    echo "<p><strong>Mensagem de Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Caminho do Arquivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Linha:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    echo "<hr>";
    echo "<h2>Stack Trace:</h2>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}
?>