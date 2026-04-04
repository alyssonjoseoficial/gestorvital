<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador', 'recepcao'])) {
    die("Acesso negado. Apenas administradores e recepcionistas podem excluir exames.");
}

$exame_id = $_GET['id'] ?? null;

if (!$exame_id) {
    header('Location: listar_exames.php?mensagem_erro=' . urlencode('ID do exame não fornecido.'));
    exit;
}

try {
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }

    // ADICIONADO: Etapa 1: Obter TODOS os dados do exame antes de excluir o registro
    $sql_busca = "SELECT e.paciente_id, e.medico_id, e.servico_id, e.nome_arquivo, 
                         p.nome AS paciente_nome, m.nome AS medico_nome, s.nome AS servico_nome
                  FROM exames e
                  JOIN pacientes p ON e.paciente_id = p.id
                  JOIN medicos m ON e.medico_id = m.id
                  JOIN servicos s ON e.servico_id = s.id
                  WHERE e.id = ?";
    $stmt_busca = $conn->prepare($sql_busca);
    $stmt_busca->bind_param("i", $exame_id);
    $stmt_busca->execute();
    $result_busca = $stmt_busca->get_result();
    $exame = $result_busca->fetch_assoc();
    $stmt_busca->close();

    if (!$exame) {
        throw new Exception("Exame não encontrado.");
    }

    // Prepara os dados para o log
    $paciente_nome = $exame['paciente_nome'];
    $servico_nome = $exame['servico_nome'];
    $medico_nome = $exame['medico_nome'];
    
    // Apaga o arquivo físico do servidor se ele existir
    if (!empty($exame['nome_arquivo'])) {
        $caminho_arquivo = __DIR__ . "/../../uploads/exames/" . $exame['nome_arquivo'];
        if (file_exists($caminho_arquivo)) {
            unlink($caminho_arquivo);
        }
    }

    // Etapa 2: Excluir o registro do banco de dados
    $sql_exclusao = "DELETE FROM exames WHERE id = ?";
    $stmt_exclusao = $conn->prepare($sql_exclusao);
    $stmt_exclusao->bind_param("i", $exame_id);

    if ($stmt_exclusao->execute()) {
        // ADICIONADO: Registra a ação no log
        $acao_log = "Excluiu o exame (ID {$exame_id}) de '{$servico_nome}' para o paciente '{$paciente_nome}'";
        registrar_log($acao_log, 'Exames', $exame_id);

        $mensagem_sucesso = 'Exame excluído com sucesso!';
        header('Location: listar_exames.php?mensagem_sucesso=' . urlencode($mensagem_sucesso));
        exit;
    } else {
        throw new Exception("Erro ao excluir exame: " . $stmt_exclusao->error);
    }

    $stmt_exclusao->close();
    $conn->close();

} catch (Exception $e) {
    header('Location: listar_exames.php?mensagem_erro=' . urlencode("Erro: " . $e->getMessage()));
    exit;
}
?>