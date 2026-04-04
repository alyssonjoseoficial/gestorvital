<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';
// ADICIONADO: Inclui a função de registro de log
require_once __DIR__ . '/../../config/log_auditoria.php';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador'])) {
    die("Acesso negado.");
}

$servico_id = $_GET['id'] ?? null;
if (!$servico_id) {
    // Redireciona se nenhum ID de serviço for fornecido
    header('Location: listar_servicos.php');
    exit;
}

$servico = null;
$mensagem_sucesso = '';
$mensagem_erro = '';

// Tenta carregar os dados do serviço do banco de dados
try {
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }

    $sql_servico = "SELECT * FROM servicos WHERE id = ?";
    $stmt_servico = $conn->prepare($sql_servico);
    $stmt_servico->bind_param("i", $servico_id);
    $stmt_servico->execute();
    $result_servico = $stmt_servico->get_result();
    $servico = $result_servico->fetch_assoc();
    $stmt_servico->close();

    if (!$servico) {
        throw new Exception("Serviço não encontrado.");
    }

} catch (Exception $e) {
    $mensagem_erro = "Erro ao carregar dados do serviço: " . $e->getMessage();
    $servico = null;
}

// Processar o formulário quando enviado para atualizar
if ($_SERVER["REQUEST_METHOD"] == "POST" && $servico) {
    $nome = $_POST['nome'] ?? '';
    $preco = $_POST['preco'] ?? 0;
    $tipo = $_POST['tipo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';

    if (empty($nome) || $preco <= 0 || empty($tipo)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            $sql = "UPDATE servicos SET nome = ?, preco = ?, tipo = ?, descricao = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdssi", $nome, $preco, $tipo, $descricao, $servico_id);
            
            if ($stmt->execute()) {
                // ADICIONADO: Chama a função para registrar a ação
                registrar_log('Editou serviço: ' . $nome, 'Serviços', $servico_id);

                $mensagem_sucesso = "Serviço '$nome' atualizado com sucesso!";
                // Atualiza o objeto $servico com os novos dados
                $servico['nome'] = $nome;
                $servico['preco'] = $preco;
                $servico['tipo'] = $tipo;
                $servico['descricao'] = $descricao;
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $mensagem_erro = "Erro ao atualizar serviço: " . $e->getMessage();
        }
        $stmt->close();
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Editar Serviço: <?= htmlspecialchars($servico['nome'] ?? 'Serviço') ?></h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listar_servicos.php">Serviços</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>
    </div>

    <?php if ($mensagem_sucesso): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($mensagem_sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($mensagem_erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($servico): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="editar_servicos.php?id=<?= htmlspecialchars($servico_id) ?>" method="POST">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Serviço</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($servico['nome']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="preco" class="form-label">Preço (R$)</label>
                        <input type="number" class="form-control" id="preco" name="preco" step="0.01" min="0" value="<?= htmlspecialchars($servico['preco']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Selecione...</option>
                            <option value="Consulta" <?= ($servico['tipo'] == 'Consulta') ? 'selected' : '' ?>>Consulta</option>
                            <option value="Exame" <?= ($servico['tipo'] == 'Exame') ? 'selected' : '' ?>>Exame</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (opcional)</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($servico['descricao']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Atualizar Serviço</button>
                    <a href="listar_servicos.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
// Removida a linha que fechava a conexão.
// O fechamento dos resultados e statements pode ocorrer aqui, se necessário.

// O rodapé será o responsável por fechar a conexão com o banco de dados.
include_once __DIR__ . '/../includes/rodape.php';
?>