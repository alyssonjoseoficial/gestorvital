<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

$nivel = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel, ['administrador', 'financeiro', 'recepcao'])) {
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $exame_id = $_POST['exame_id'] ?? null;
    $valor = $_POST['valor'] ?? 0;
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';

    // Validação básica
    if (!$exame_id || !$valor || !$metodo_pagamento) {
        header("Location: listar_exames.php?erro=dados_incompletos");
        exit();
    }

    if ($conn->connect_error) {
        header("Location: listar_exames.php?erro=conexao_bd");
        exit();
    }

    $conn->begin_transaction();

    try {
        // ADICIONADO: Busca os nomes do paciente e do exame para o log
        $sql_nomes = "SELECT p.nome AS paciente_nome, s.nome AS servico_nome 
                      FROM exames e 
                      JOIN pacientes p ON e.paciente_id = p.id 
                      JOIN servicos s ON e.servico_id = s.id 
                      WHERE e.id = ?";
        $stmt_nomes = $conn->prepare($sql_nomes);
        $stmt_nomes->bind_param("i", $exame_id);
        $stmt_nomes->execute();
        $result_nomes = $stmt_nomes->get_result();
        $nomes = $result_nomes->fetch_assoc();
        $stmt_nomes->close();

        // Insere o pagamento na tabela `pagamentos`
        $sql_pagamento = "INSERT INTO pagamentos (exame_id, valor, metodo_pagamento, data_pagamento) VALUES (?, ?, ?, NOW())";
        $stmt_pagamento = $conn->prepare($sql_pagamento);
        $stmt_pagamento->bind_param("ids", $exame_id, $valor, $metodo_pagamento);
        $stmt_pagamento->execute();
        
        // ADICIONADO: Obtém o ID do pagamento recém-criado
        $novo_pagamento_id = $conn->insert_id;

        $conn->commit();

        // ADICIONADO: Prepara a mensagem de log
        $paciente_nome = $nomes['paciente_nome'] ?? 'Desconhecido';
        $servico_nome = $nomes['servico_nome'] ?? 'Desconhecido';
        $valor_formatado = number_format($valor, 2, ',', '.');
        $acao_log = "Registrou pagamento de R$ {$valor_formatado} para o exame de '{$servico_nome}' do paciente '{$paciente_nome}' (ID Pagamento: {$novo_pagamento_id}, Exame: {$exame_id})";

        // ADICIONADO: Chama a função para registrar a ação
        registrar_log($acao_log, 'Pagamentos', $novo_pagamento_id);
        
        header("Location: ../exames/listar_exames.php?sucesso=pagamento");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: listar_exames.php?erro=falha_pagamento");
        exit();
    } finally {
        if (isset($stmt_pagamento)) {
            $stmt_pagamento->close();
        }
        $conn->close();
    }
}
?>