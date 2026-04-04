<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

$nivel = $_SESSION['nivel_acesso'] ?? '';
if ($nivel !== 'administrador') {
    header("Location: ../../public/index.php");
    exit();
}

$mensagem = '';
$sucesso = false;

// Lógica para adicionar/editar/excluir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'excluir' && isset($_POST['id'])) {
        $id_excluir = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if ($id_excluir) {
            $stmt = $conn->prepare("DELETE FROM servicos WHERE id = ?");
            $stmt->bind_param("i", $id_excluir);
            if ($stmt->execute()) {
                $mensagem = "Serviço excluído com sucesso.";
                $sucesso = true;
            } else {
                $mensagem = "Erro ao excluir o serviço: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $preco = filter_var($_POST['preco'] ?? 0, FILTER_VALIDATE_FLOAT);

        if (!empty($nome) && $preco !== false) {
            if ($id) { // Edição
                $stmt = $conn->prepare("UPDATE servicos SET nome = ?, preco = ? WHERE id = ?");
                $stmt->bind_param("sdi", $nome, $preco, $id);
            } else { // Adição
                $stmt = $conn->prepare("INSERT INTO servicos (nome, preco) VALUES (?, ?)");
                $stmt->bind_param("sd", $nome, $preco);
            }

            if ($stmt->execute()) {
                $mensagem = "Serviço " . ($id ? "atualizado" : "adicionado") . " com sucesso.";
                $sucesso = true;
            } else {
                $mensagem = "Erro ao salvar o serviço: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $mensagem = "Preencha todos os campos corretamente.";
        }
    }
}

// Lógica para buscar os serviços
$servicos = [];
$result = $conn->query("SELECT id, nome, preco FROM servicos ORDER BY nome ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $servicos[] = $row;
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Cadastro de Preços de Serviços</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServico">
            Adicionar Serviço
        </button>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($servicos)): ?>
        <div class="alert alert-info">Nenhum serviço cadastrado ainda.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Preço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $servico): ?>
                        <tr>
                            <td><?= htmlspecialchars($servico['nome']) ?></td>
                            <td>R$ <?= number_format($servico['preco'], 2, ',', '.') ?></td>
                            <td>
                                <button class="btn btn-sm btn-info editar-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalServico"
                                        data-id="<?= $servico['id'] ?>"
                                        data-nome="<?= htmlspecialchars($servico['nome']) ?>"
                                        data-preco="<?= htmlspecialchars($servico['preco']) ?>">
                                    Editar
                                </button>
                                <form action="precos_procedimentos.php" method="POST" class="d-inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este serviço?');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= $servico['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalServico" tabindex="-1" aria-labelledby="modalServicoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formServico" action="precos_procedimentos.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalServicoLabel">Adicionar Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="servicoId">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Serviço</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="preco" class="form-label">Preço (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="preco" name="preco" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalServico = document.getElementById('modalServico');
        modalServico.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var form = document.getElementById('formServico');
            var modalTitle = modalServico.querySelector('.modal-title');
            var servicoIdInput = document.getElementById('servicoId');
            var nomeInput = document.getElementById('nome');
            var precoInput = document.getElementById('preco');

            if (button.classList.contains('editar-btn')) {
                // Modo Edição
                modalTitle.textContent = 'Editar Serviço';
                var id = button.getAttribute('data-id');
                var nome = button.getAttribute('data-nome');
                var preco = button.getAttribute('data-preco');
                servicoIdInput.value = id;
                nomeInput.value = nome;
                precoInput.value = preco;
            } else {
                // Modo Adicionar
                modalTitle.textContent = 'Adicionar Serviço';
                form.reset();
                servicoIdInput.value = '';
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>