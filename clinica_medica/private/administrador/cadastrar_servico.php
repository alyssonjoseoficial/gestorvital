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

$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $preco = $_POST['preco'] ?? 0;
    $tipo = $_POST['tipo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';

    if (empty($nome) || $preco <= 0 || empty($tipo)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            $sql = "INSERT INTO servicos (nome, preco, tipo, descricao) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdss", $nome, $preco, $tipo, $descricao);
            
            if ($stmt->execute()) {
                $novo_servico_id = $conn->insert_id; // Pega o ID do novo serviço

                // ADICIONADO: Chama a função para registrar a ação
                registrar_log('Cadastrou novo serviço: ' . $nome, 'Serviços', $novo_servico_id);

                $mensagem_sucesso = "Serviço '$nome' cadastrado com sucesso!";
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $mensagem_erro = "Erro ao cadastrar serviço: " . $e->getMessage();
        }
        $stmt->close();
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Cadastrar Novo Serviço</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listar_servicos.php">Serviços</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cadastrar</li>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="cadastrar_servico.php" method="POST">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Serviço</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>
                <div class="mb-3">
                    <label for="preco" class="form-label">Preço (R$)</label>
                    <input type="number" class="form-control" id="preco" name="preco" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="">Selecione...</option>
                        <option value="Consulta">Consulta</option>
                        <option value="Exame">Exame</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição (opcional)</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Cadastrar Serviço</button>
                <a href="listar_servicos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/rodape.php'; ?>