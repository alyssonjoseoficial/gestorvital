<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador'])) {
    die("Acesso negado.");
}

$mensagem_sucesso = $_GET['sucesso'] ?? '';
$mensagem_erro = $_GET['erro'] ?? '';

try {
    $sql = "SELECT id, nome, preco, tipo FROM servicos ORDER BY tipo, nome";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

} catch (Exception $e) {
    $mensagem_erro = "Erro ao carregar serviços: " . $e->getMessage();
    $result = false;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Gerenciar Serviços</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Serviços</li>
            </ol>
        </nav>
    </div>

    <?php if ($mensagem_sucesso): ?>
        <div class="alert alert-success" role="alert">
            Serviço excluído com sucesso!
        </div>
    <?php endif; ?>

    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($mensagem_erro) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-3">
        <a href="cadastrar_servico.php" class="btn btn-success">
            <i class="fas fa-plus-circle me-2"></i>Novo Serviço
        </a>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Preço</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($servico = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($servico['id']) ?></td>
                                    <td><?= htmlspecialchars($servico['nome']) ?></td>
                                    <td><?= htmlspecialchars($servico['tipo']) ?></td>
                                    <td>R$ <?= number_format($servico['preco'], 2, ',', '.') ?></td>
                                   <td>
    <a href="editar_servicos.php?id=<?= $servico['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
    <a href="excluir_servico.php?id=<?= $servico['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este serviço?');">Excluir</a>
</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info" role="alert">Nenhum serviço cadastrado.</div>
    <?php endif; ?>
</div>

<?php
// O fechamento dos resultados e statements pode ocorrer aqui, antes de incluir o rodapé
if ($result) {
    $result->free_result();
}
if ($stmt) {
    $stmt->close();
}

// Inclui o rodapé no final da página, que fechará a conexão
include_once __DIR__ . '/../includes/rodape.php';
?>