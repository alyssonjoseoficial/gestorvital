<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador', 'recepcao', 'medico', 'financeiro'])) {
    die("Acesso negado.");
}

$mensagem_sucesso = '';
$mensagem_erro = '';

// Variáveis para armazenar os valores dos filtros
$paciente_nome = $_GET['paciente_nome'] ?? '';
$medico_nome = $_GET['medico_nome'] ?? '';
$tipo_exame_filtro = $_GET['tipo_exame_filtro'] ?? '';
$status = $_GET['status'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$status_pagamento = $_GET['status_pagamento'] ?? '';

try {
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }
    
    // Buscar serviços de Exame para o filtro
    $sql_servicos = "SELECT id, nome FROM servicos WHERE tipo = 'Exame' ORDER BY nome";
    $stmt_servicos = $conn->prepare($sql_servicos);
    $stmt_servicos->execute();
    $servicos_exame_filtro = $stmt_servicos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_servicos->close();

    $sql = "SELECT e.*, 
                   p.nome AS nome_paciente, 
                   m.nome AS nome_medico,
                   s.nome AS nome_servico,
                   s.preco AS preco_servico,
                   COALESCE(pg.valor, s.preco, 0) AS valor_exibido,
                   CASE WHEN pg.id IS NOT NULL THEN 'Pago' ELSE 'Não Pago' END AS status_pagamento_display
            FROM exames e
            INNER JOIN pacientes p ON e.paciente_id = p.id
            INNER JOIN medicos m ON e.medico_id = m.id
            LEFT JOIN servicos s ON e.servico_id = s.id
            LEFT JOIN pagamentos pg ON e.id = pg.exame_id
            WHERE 1=1";

    $params = [];
    $types = '';

    // Adiciona as condições WHERE baseadas nos filtros preenchidos
    if (!empty($paciente_nome)) {
        $sql .= " AND p.nome LIKE ?";
        $params[] = '%' . $paciente_nome . '%';
        $types .= 's';
    }

    if (!empty($medico_nome)) {
        $sql .= " AND m.nome LIKE ?";
        $params[] = '%' . $medico_nome . '%';
        $types .= 's';
    }

    if (!empty($tipo_exame_filtro)) {
        $sql .= " AND s.id = ?";
        $params[] = $tipo_exame_filtro;
        $types .= 'i';
    }

    if (!empty($status)) {
        $sql .= " AND e.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if (!empty($status_pagamento) && $status_pagamento !== 'Todos') {
        if ($status_pagamento == 'Pago') {
            $sql .= " AND pg.id IS NOT NULL";
        } else {
            $sql .= " AND pg.id IS NULL";
        }
    }

    if (!empty($data_inicio)) {
        $sql .= " AND e.data_exame >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }

    if (!empty($data_fim)) {
        $sql .= " AND e.data_exame <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }
    
    // Ordenação dos resultados
    $sql .= " ORDER BY e.data_exame DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

} catch (Exception $e) {
    $mensagem_erro = "Erro ao carregar exames: " . $e->getMessage();
    $result = false;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Exames</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Exames</li>
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

    <form action="" method="GET" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <input type="text" class="form-control" name="paciente_nome" placeholder="Nome do Paciente" value="<?= htmlspecialchars($paciente_nome) ?>">
            </div>
            <div class="col-md-6 col-lg-3">
                <input type="text" class="form-control" name="medico_nome" placeholder="Nome do Médico" value="<?= htmlspecialchars($medico_nome) ?>">
            </div>
            <div class="col-md-6 col-lg-3">
                <select class="form-select" name="tipo_exame_filtro">
                    <option value="">Tipo de Exame (Todos)</option>
                    <?php foreach ($servicos_exame_filtro as $servico): ?>
                        <option value="<?= htmlspecialchars($servico['id']) ?>" <?= ($tipo_exame_filtro == $servico['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($servico['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <select class="form-select" name="status">
                    <option value="">Status (Todos)</option>
                    <option value="Agendado" <?= ($status == 'Agendado') ? 'selected' : '' ?>>Agendado</option>
                    <option value="Realizado" <?= ($status == 'Realizado') ? 'selected' : '' ?>>Realizado</option>
                    <option value="Com Laudo" <?= ($status == 'Com Laudo') ? 'selected' : '' ?>>Com Laudo</option>
                    <option value="Cancelado" <?= ($status == 'Cancelado') ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6 col-lg-3">
                <select class="form-select" name="status_pagamento">
                    <option value="Todos" <?= ($status_pagamento == 'Todos') ? 'selected' : '' ?>>Status Pagamento (Todos)</option>
                    <option value="Pago" <?= ($status_pagamento == 'Pago') ? 'selected' : '' ?>>Pago</option>
                    <option value="Não Pago" <?= ($status_pagamento == 'Não Pago') ? 'selected' : '' ?>>Não Pago</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="data_inicio" class="form-label visually-hidden">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="data_fim" class="form-label visually-hidden">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            <div class="col-md-6 col-lg-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 me-2">Buscar</button>
                <a href="listar_exames.php" class="btn btn-secondary w-100">Limpar Filtros</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Lista de Exames</h5>
        <a href="cadastrar_exame.php" class="btn btn-success">
            <i class="fas fa-plus-circle me-2"></i>Novo Exame
        </a>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Tipo de Exame</th>
                        <th>Valor</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($exame = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($exame['id']) ?></td>
                            <td><?= htmlspecialchars($exame['nome_paciente']) ?></td>
                            <td><?= htmlspecialchars($exame['nome_medico']) ?></td>
                            <td><?= htmlspecialchars($exame['nome_servico']) ?></td>
                            <td>R$ <?= number_format($exame['valor_exibido'] ?? 0, 2, ',', '.') ?></td>
                            <td><?= date('d/m/Y', strtotime($exame['data_exame'])) ?></td>
                            <td>
                                <span class="badge 
                                    <?php
                                    switch ($exame['status']) {
                                        case 'Agendado': echo 'bg-warning text-dark'; break;
                                        case 'Realizado': echo 'bg-info text-dark'; break;
                                        case 'Com Laudo': echo 'bg-success'; break;
                                        case 'Cancelado': echo 'bg-danger'; break;
                                        default: echo 'bg-secondary';
                                    }
                                    ?>">
                                    <?= htmlspecialchars($exame['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge 
                                    <?php
                                    if ($exame['status_pagamento_display'] == 'Pago') {
                                        echo 'bg-success';
                                    } else {
                                        echo 'bg-warning text-dark';
                                    }
                                    ?>">
                                    <?= htmlspecialchars($exame['status_pagamento_display']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="editar_exame.php?id=<?= htmlspecialchars($exame['id']) ?>" class="btn btn-sm btn-primary">Editar</a>
                                <?php if (!empty($exame['resultado'])): ?>
                                    <a href="gerar_laudo_pdf.php?id=<?= htmlspecialchars($exame['id']) ?>" class="btn btn-sm btn-info" target="_blank">PDF</a>
                                <?php endif; ?>
                                <?php if ($exame['status_pagamento_display'] !== 'Pago'): ?>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPagamento" 
                                    data-id="<?= htmlspecialchars($exame['id']) ?>"
                                    data-valor="<?= htmlspecialchars($exame['preco_servico'] ?? 0) ?>">
                                    <i class="fas fa-dollar-sign"></i> Pagar
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-secondary" disabled>Pago</button>
                                <?php endif; ?>
                                <a href="excluir_exame.php?id=<?= htmlspecialchars($exame['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este exame? Esta ação é irreversível e também removerá o arquivo anexo.');">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info" role="alert">Nenhum exame encontrado.</div>
    <?php endif; ?>

</div>

<div class="modal fade" id="modalPagamento" tabindex="-1" aria-labelledby="modalPagamentoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagamentoLabel">Efetuar Pagamento de Exame</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPagamento" action="../financeiro/efetuar_pagamento_exame.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="exame_id" id="exame_id">
                    <div class="mb-3">
                        <label for="valorPagamento" class="form-label">Valor</label>
                        <input type="number" class="form-control" id="valorPagamento" name="valor" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="metodoPagamento" class="form-label">Método de Pagamento</label>
                        <select class="form-select" id="metodoPagamento" name="metodo_pagamento" required>
                            <option value="">Selecione</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="PIX">PIX</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-success">Confirmar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var modalPagamento = document.getElementById('modalPagamento');
    modalPagamento.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var exameId = button.getAttribute('data-id');
        var valor = button.getAttribute('data-valor');

        var modalExameId = modalPagamento.querySelector('#exame_id');
        var modalValor = modalPagamento.querySelector('#valorPagamento');
        
        modalExameId.value = exameId;
        modalValor.value = valor;
    });
</script>

<?php
// O fechamento dos resultados e statements pode ocorrer aqui, antes de incluir o rodapé
if ($result) {
    $result->free_result();
}
if ($stmt) {
    $stmt->close();
}

// O require_once do rodapé vem aqui. Ele, por sua vez, fechará a conexão com o banco de dados.
include_once __DIR__ . '/../includes/rodape.php';
?>