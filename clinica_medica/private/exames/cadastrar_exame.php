<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador', 'recepcao'])) {
    die("Acesso negado.");
}

$pacientes = [];
$medicos = [];
$servicos_exame = [];
$mensagem_sucesso = '';
$mensagem_erro = '';

// Buscar a lista de pacientes, médicos e serviços para o formulário
try {
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }

    // Buscar pacientes
    $sql_pacientes = "SELECT id, nome FROM pacientes ORDER BY nome ASC";
    $result_pacientes = $conn->query($sql_pacientes);
    if ($result_pacientes) {
        while ($row = $result_pacientes->fetch_assoc()) {
            $pacientes[] = $row;
        }
    }

    // Buscar médicos
    $sql_medicos = "SELECT id, nome FROM medicos ORDER BY nome ASC";
    $result_medicos = $conn->query($sql_medicos);
    if ($result_medicos) {
        while ($row = $result_medicos->fetch_assoc()) {
            $medicos[] = $row;
        }
    }
    
    // Buscar serviços de Exame
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

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_exame'])) {
    $paciente_id = $_POST['paciente_id'] ?? null;
    $medico_id = $_POST['medico_id'] ?? null;
    $servico_id = $_POST['servico_id'] ?? null;
    $data_exame = $_POST['data_exame'] ?? '';

    if ($paciente_id && $medico_id && $servico_id && !empty($data_exame)) {
        try {
            // Prevenir SQL Injection usando prepared statements
            $sql = "INSERT INTO exames (paciente_id, medico_id, servico_id, data_exame) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $paciente_id, $medico_id, $servico_id, $data_exame);

            if ($stmt->execute()) {
                // ADICIONADO: Obtém o ID do exame recém-criado
                $novo_exame_id = $conn->insert_id;

                // ADICIONADO: Busca os nomes para o log
                $stmt_nomes = $conn->prepare("
                    SELECT p.nome AS paciente_nome, m.nome AS medico_nome, s.nome AS servico_nome
                    FROM pacientes p, medicos m, servicos s
                    WHERE p.id = ? AND m.id = ? AND s.id = ?
                ");
                $stmt_nomes->bind_param("iii", $paciente_id, $medico_id, $servico_id);
                $stmt_nomes->execute();
                $result_nomes = $stmt_nomes->get_result();
                $nomes = $result_nomes->fetch_assoc();
                $stmt_nomes->close();

                $paciente_nome = $nomes['paciente_nome'] ?? 'Desconhecido';
                $medico_nome = $nomes['medico_nome'] ?? 'Desconhecido';
                $servico_nome = $nomes['servico_nome'] ?? 'Desconhecido';

                // ADICIONADO: Prepara a mensagem de log
                $acao_log = "Cadastrou exame (ID {$novo_exame_id}) de '{$servico_nome}' para o paciente '{$paciente_nome}' com o médico '{$medico_nome}' em " . date('d/m/Y', strtotime($data_exame));
                
                // ADICIONADO: Chama a função para registrar a ação
                registrar_log($acao_log, 'Exames', $novo_exame_id);

                $mensagem_sucesso = "Exame cadastrado com sucesso!";
            } else {
                $mensagem_erro = "Erro ao cadastrar exame: " . $stmt->error;
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
        <h4>Cadastrar Novo Exame</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listar_exames.php">Exames</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cadastrar</li>
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

    <form action="cadastrar_exame.php" method="POST">
        <div class="mb-3">
            <label for="paciente_id" class="form-label">Paciente</label>
            <select class="form-select" id="paciente_id" name="paciente_id" required>
                <option value="">Selecione um paciente</option>
                <?php foreach ($pacientes as $paciente): ?>
                    <option value="<?= htmlspecialchars($paciente['id']) ?>">
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
                    <option value="<?= htmlspecialchars($medico['id']) ?>">
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
                    <option value="<?= htmlspecialchars($servico['id']) ?>">
                        <?= htmlspecialchars($servico['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="data_exame" class="form-label">Data do Exame</label>
            <input type="date" class="form-control" id="data_exame" name="data_exame" required>
        </div>
        
        <button type="submit" name="cadastrar_exame" class="btn btn-primary">Cadastrar</button>
        <a href="listar_exames.php" class="btn btn-secondary">Voltar</a>
    </form>
</div>

<?php 
if ($conn) {
    $conn->close();
}
include_once __DIR__ . '/../includes/rodape.php'; 
?>