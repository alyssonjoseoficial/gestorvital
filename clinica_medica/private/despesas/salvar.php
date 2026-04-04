<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    $_SESSION['erro_mensagem'] = "Acesso negado.";
    header("Location: cadastrar.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_despesa = $_POST['data_despesa'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $categoria = $_POST['categoria'] ?? NULL;

    if (empty($data_despesa) || empty($descricao) || empty($valor)) {
        $_SESSION['erro_mensagem'] = "Todos os campos obrigatórios devem ser preenchidos.";
        header("Location: cadastrar.php");
        exit();
    }

    try {
        $sql = "INSERT INTO despesas (data_despesa, descricao, valor, categoria) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssds", $data_despesa, $descricao, $valor, $categoria);
        
        if ($stmt->execute()) {
            // ADICIONADO: Obtém o ID da despesa recém-criada
            $nova_despesa_id = $conn->insert_id;

            // ADICIONADO: Prepara a mensagem de log
            $valor_formatado = number_format($valor, 2, ',', '.');
            $acao_log = "Registrou despesa de R$ {$valor_formatado} com a descrição: '{$descricao}'";
            
            // ADICIONADO: Chama a função para registrar a ação
            registrar_log($acao_log, 'Despesas', $nova_despesa_id);

            $_SESSION['sucesso_mensagem'] = "Despesa registrada com sucesso!";
        } else {
            throw new Exception("Erro ao registrar a despesa: " . $stmt->error);
        }

    } catch (Exception $e) {
        $_SESSION['erro_mensagem'] = $e->getMessage();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
        header("Location: cadastrar.php");
        exit();
    }
}
?>