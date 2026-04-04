<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador', 'recepcao', 'medico'])) {
    die("Acesso negado.");
}

$exame_id = $_GET['id'] ?? null;
if (!$exame_id) {
    header('Location: listar_exames.php');
    exit;
}

$exame = null;
$pacientes = [];
$medicos = [];
$servicos_exame = [];
$mensagem_sucesso = '';
$mensagem_erro = '';

try {
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }

    // Buscar o exame atual
    $sql_exame = "SELECT * FROM exames WHERE id = ?";
    $stmt_exame = $conn->prepare($sql_exame);
    $stmt_exame->bind_param("i", $exame_id);
    $stmt_exame->execute();
    $result_exame = $stmt_exame->get_result();
    $exame = $result_exame->fetch_assoc();
    $stmt_exame->close();

    if (!$exame) {
        throw new Exception("Exame não encontrado.");
    }

    // ADICIONADO: Armazena os dados originais para comparação
    $dados_antigos = $exame;

    // Buscar a lista de pacientes e médicos para o formulário
    $sql_pacientes = "SELECT id, nome FROM pacientes ORDER BY nome ASC";
    $result_pacientes = $conn->query($sql_pacientes);
    if ($result_pacientes) {
        while ($row = $result_pacientes->fetch_assoc()) {
            $pacientes[] = $row;
        }
    }

    $sql_medicos = "SELECT id, nome FROM medicos ORDER BY nome ASC";
    $result_medicos = $conn->query($sql_medicos);
    if ($result_medicos) {
        while ($row = $result_medicos->fetch_assoc()) {
            $medicos[] = $row;
        }
    }

    // Buscar a lista de serviços de Exame para o dropdown
    $sql_servicos = "SELECT id, nome FROM servicos WHERE tipo = 'Exame' ORDER BY nome";
    $result_servicos = $conn->query($sql_servicos);
    if ($result_servicos) {
        while ($row = $result_servicos->fetch_assoc()) {
            $servicos_exame[] = $row;
        }
    }

} catch (Exception $e) {
    $mensagem_erro = "Erro ao carregar dados: " . $e->getMessage();
}

// Processar a atualização do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_exame'])) {
    $paciente_id = $_POST['paciente_id'] ?? $exame['paciente_id'];
    $medico_id = $_POST['medico_id'] ?? $exame['medico_id'];
    $servico_id = $_POST['servico_id'] ?? $exame['servico_id'];
    $data_exame = $_POST['data_exame'] ?? $exame['data_exame'];
    $resultado = $_POST['resultado'] ?? $exame['resultado'];
    $status = $_POST['status'] ?? $exame['status'];
    $nome_arquivo = $exame['nome_arquivo'] ?? null;

    if ($paciente_id && $medico_id && $servico_id && !empty($data_exame)) {
        try {
            // Lógica para upload do arquivo
            $arquivo_alterado = false;
            $nome_arquivo_antigo = $dados_antigos['nome_arquivo'];
            
            if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . "/../../uploads/exames/";
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $imageFileType = strtolower(pathinfo($_FILES["arquivo"]["name"], PATHINFO_EXTENSION));
                $nome_arquivo_novo = uniqid('laudo_', true) . '.' . $imageFileType;
                $target_file_final = $target_dir . $nome_arquivo_novo;

                if (move_uploaded_file($_FILES["arquivo"]["tmp_name"], $target_file_final)) {
                    if (!empty($nome_arquivo_antigo) && file_exists($target_dir . $nome_arquivo_antigo)) {
                        unlink($target_dir . $nome_arquivo_antigo);
                    }
                    $nome_arquivo = $nome_arquivo_novo;
                    $arquivo_alterado = true;
                } else {
                    $mensagem_erro .= " Erro ao mover o arquivo.";
                }
            }

            // ADICIONADO: Busca os nomes originais e novos para o log
            $sql_nomes = "
                SELECT
                    p_orig.nome AS paciente_nome_antigo, m_orig.nome AS medico_nome_antigo, s_orig.nome AS servico_nome_antigo,
                    p_novo.nome AS paciente_nome_novo, m_novo.nome AS medico_nome_novo, s_novo.nome AS servico_nome_novo
                FROM exames e
                LEFT JOIN pacientes p_orig ON p_orig.id = ?
                LEFT JOIN medicos m_orig ON m_orig.id = ?
                LEFT JOIN servicos s_orig ON s_orig.id = ?
                LEFT JOIN pacientes p_novo ON p_novo.id = ?
                LEFT JOIN medicos m_novo ON m_novo.id = ?
                LEFT JOIN servicos s_novo ON s_novo.id = ?
                WHERE e.id = ?
            ";
            $stmt_nomes = $conn->prepare($sql_nomes);
            $stmt_nomes->bind_param("iiiiiii", $dados_antigos['paciente_id'], $dados_antigos['medico_id'], $dados_antigos['servico_id'], $paciente_id, $medico_id, $servico_id, $exame_id);
            $stmt_nomes->execute();
            $result_nomes = $stmt_nomes->get_result();
            $nomes = $result_nomes->fetch_assoc();
            $stmt_nomes->close();

            // Atualiza o banco de dados
            $sql = "UPDATE exames SET paciente_id = ?, medico_id = ?, servico_id = ?, data_exame = ?, resultado = ?, status = ?, nome_arquivo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissssi", $paciente_id, $medico_id, $servico_id, $data_exame, $resultado, $status, $nome_arquivo, $exame_id);

            if ($stmt->execute()) {
                // ADICIONADO: Monta a mensagem de log
                $alteracoes = [];
                if ($paciente_id !== $dados_antigos['paciente_id']) {
                    $alteracoes[] = "Paciente de '{$nomes['paciente_nome_antigo']}' para '{$nomes['paciente_nome_novo']}'";
                }
                if ($medico_id !== $dados_antigos['medico_id']) {
                    $alteracoes[] = "Médico de '{$nomes['medico_nome_antigo']}' para '{$nomes['medico_nome_novo']}'";
                }
                if ($servico_id !== $dados_antigos['servico_id']) {
                    $alteracoes[] = "Serviço de '{$nomes['servico_nome_antigo']}' para '{$nomes['servico_nome_novo']}'";
                }
                if ($data_exame !== $dados_antigos['data_exame']) {
                    $alteracoes[] = "Data de '" . date('d/m/Y', strtotime($dados_antigos['data_exame'])) . "' para '" . date('d/m/Y', strtotime($data_exame)) . "'";
                }
                if ($resultado !== $dados_antigos['resultado']) {
                    $alteracoes[] = "Resultado (conteúdo alterado)";
                }
                if ($status !== $dados_antigos['status']) {
                    $alteracoes[] = "Status de '{$dados_antigos['status']}' para '{$status}'";
                }
                if ($arquivo_alterado) {
                    $alteracoes[] = "Laudo anexado/atualizado";
                }

                $acao_log = "Editou o exame (ID {$exame_id})";
                if (!empty($alteracoes)) {
                    $acao_log .= ": " . implode(', ', $alteracoes);
                }
                
                // ADICIONADO: Chama a função para registrar a ação
                registrar_log($acao_log, 'Exames', $exame_id);
                
                $mensagem_sucesso = "Exame atualizado com sucesso!";
                // Atualiza o array $exame para refletir as novas mudanças
                $exame = [
                    'paciente_id' => $paciente_id,
                    'medico_id' => $medico_id,
                    'servico_id' => $servico_id,
                    'data_exame' => $data_exame,
                    'resultado' => $resultado,
                    'status' => $status,
                    'nome_arquivo' => $nome_arquivo
                ];
                
            } else {
                $mensagem_erro = "Erro ao atualizar exame: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $mensagem_erro = "Erro: " . $e->getMessage();
        }
    } else {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios.";
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Editar Exame</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listar_exames.php">Exames</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>
    </div>

    <?php if (!empty($mensagem_sucesso)): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($mensagem_sucesso) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($mensagem_erro)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($mensagem_erro) ?>
        </div>
    <?php endif; ?>

    <form action="editar_exame.php?id=<?= htmlspecialchars($exame_id) ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="paciente_id" class="form-label">Paciente</label>
            <select class="form-select" id="paciente_id" name="paciente_id" required>
                <option value="">Selecione um paciente</option>
                <?php foreach ($pacientes as $paciente): ?>
                    <option value="<?= htmlspecialchars($paciente['id']) ?>"
                        <?= $exame['paciente_id'] == $paciente['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($paciente['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="medico_id" class="form-label">Médico</label>
            <select class="form-select" id="medico_id" name="medico_id" required>
                <option value="">Selecione um médico</option>
                <?php foreach ($medicos as $medico): ?>
                    <option value="<?= htmlspecialchars($medico['id']) ?>"
                        <?= $exame['medico_id'] == $medico['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($medico['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="servico_id" class="form-label">Tipo de Exame</label>
            <select class="form-select" id="servico_id" name="servico_id" required>
                <option value="">Selecione o tipo de exame</option>
                <?php foreach ($servicos_exame as $servico): ?>
                    <option value="<?= htmlspecialchars($servico['id']) ?>"
                        <?= $exame['servico_id'] == $servico['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($servico['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="data_exame" class="form-label">Data do Exame</label>
            <input type="date" class="form-control" id="data_exame" name="data_exame" 
                    value="<?= htmlspecialchars($exame['data_exame']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="resultado" class="form-label">Resultado</label>
            <textarea class="form-control" id="resultado" name="resultado" rows="5"><?= htmlspecialchars($exame['resultado']) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="Agendado" <?= $exame['status'] === 'Agendado' ? 'selected' : '' ?>>Agendado</option>
                <option value="Realizado" <?= $exame['status'] === 'Realizado' ? 'selected' : '' ?>>Realizado</option>
                <option value="Com Laudo" <?= $exame['status'] === 'Com Laudo' ? 'selected' : '' ?>>Com Laudo</option>
                <option value="Cancelado" <?= $exame['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="arquivo" class="form-label">Anexar Laudo/Arquivo (PDF, JPG, PNG)</label>
            <input class="form-control" type="file" id="arquivo" name="arquivo">
            <?php if (!empty($exame['nome_arquivo'])): ?>
                <div class="mt-2">
                    <p>Arquivo atual: 
                        <a href="../../uploads/exames/<?= htmlspecialchars($exame['nome_arquivo']) ?>" target="_blank">
                            <?= htmlspecialchars($exame['nome_arquivo']) ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($exame['resultado'])): ?>
            <div class="mb-3">
                <a href="gerar_laudo_pdf.php?id=<?= htmlspecialchars($exame_id) ?>" class="btn btn-info" target="_blank">
                    <i class="fas fa-file-pdf"></i> Gerar PDF do Laudo
                </a>
            </div>
        <?php endif; ?>

        <button type="submit" name="atualizar_exame" class="btn btn-primary">Atualizar</button>
        <a href="listar_exames.php" class="btn btn-secondary">Voltar</a>
    </form>
</div>

<?php 
if ($conn) {
    $conn->close();
}
include_once __DIR__ . '/../includes/rodape.php'; 
?>