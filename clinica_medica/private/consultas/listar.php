<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

$sucesso = $_SESSION['sucesso_mensagem'] ?? '';
$erro = $_SESSION['erro_mensagem'] ?? '';
unset($_SESSION['sucesso_mensagem']);
unset($_SESSION['erro_mensagem']);

// Variáveis para armazenar os valores dos filtros
$medico_id = $_GET['medico_id'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

// Busca a lista de médicos para o filtro
$sql_medicos = "SELECT id, nome FROM medicos ORDER BY nome ASC";
$resultado_medicos = $conn->query($sql_medicos);
$medicos = $resultado_medicos->fetch_all(MYSQLI_ASSOC);

try {
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }

    // Consulta SQL base
    $sql = "SELECT c.id, p.nome AS paciente_nome, m.nome AS medico_nome, s.nome AS servico_nome, c.data_consulta, c.hora_consulta, c.turno, c.status, c.observacoes, c.status_pagamento
            FROM consultas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN medicos m ON c.medico_id = m.id
            LEFT JOIN servicos s ON c.servico_id = s.id
            WHERE 1=1"; // Cláusula 1=1 para facilitar a adição de outras condições

    $params = [];
    $types = '';

    // Adiciona as condições WHERE baseadas nos filtros
    if (!empty($medico_id)) {
        $sql .= " AND c.medico_id = ?";
        $params[] = $medico_id;
        $types .= 'i';
    }

    if (!empty($data_inicio)) {
        $sql .= " AND c.data_consulta >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }

    if (!empty($data_fim)) {
        $sql .= " AND c.data_consulta <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    // Ordenação dos resultados
    $sql .= " ORDER BY c.data_consulta DESC, c.hora_consulta ASC";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Erro na preparação da query: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

} catch (Exception $e) {
    $erro = "Erro ao carregar consultas: " . $e->getMessage();
    $resultado = false;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Listar Consultas</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Consultas</li>
            </ol>
        </nav>
    </div>

    <?php if ($sucesso): ?>
        <div class="alert alert-success mt-3"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <a href="cadastrar.php" class="btn btn-primary mb-3">Agendar Nova Consulta</a>

    <form action="" method="GET" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label for="medico_id" class="form-label visually-hidden">Médico</label>
                <select id="medico_id" name="medico_id" class="form-select">
                    <option value="">Filtrar por Médico</option>
                    <?php foreach ($medicos as $medico): ?>
                        <option value="<?= htmlspecialchars($medico['id']) ?>" <?= ($medico_id == $medico['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($medico['nome']) ?>
                        </option>
                    <?php endforeach; ?>
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
                <button type="submit" class="btn btn-primary me-2 w-100"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="listar.php" class="btn btn-secondary w-100"><i class="fas fa-eraser"></i> Limpar</a>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Paciente</th>
                    <th>Médico</th>
                    <th>Serviço</th>
                    <th>Data</th>
                    <th>Turno/Horário</th>
                    <th>Status</th>
                    <th>Pagamento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($consulta = $resultado->fetch_assoc()): ?>
                        <tr class="<?= ($consulta['turno'] == 'encaixe') ? 'table-warning' : '' ?>">
                            <td><?= htmlspecialchars($consulta['id']) ?></td>
                            <td><?= htmlspecialchars($consulta['paciente_nome']) ?></td>
                            <td><?= htmlspecialchars($consulta['medico_nome']) ?></td>
                            <td><?= htmlspecialchars($consulta['servico_nome'] ?? 'N/A') ?></td>
                            <td><?= date('d/m/Y', strtotime($consulta['data_consulta'])) ?></td>
                            <td>
                                <?php if ($consulta['turno'] == 'encaixe'): ?>
                                    <span class="badge bg-danger">Encaixe</span>
                                    <?= htmlspecialchars(date('H:i', strtotime($consulta['hora_consulta']))) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($consulta['turno']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($consulta['status']) ?></td>
                            <td>
                                <?php if ($consulta['status_pagamento'] == 'pago'): ?>
                                    <span class="badge bg-success">Pago</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="editar.php?id=<?= $consulta['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="excluir.php?id=<?= $consulta['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta consulta?');">Excluir</a>
                                <?php if ($consulta['status'] == 'realizada' && $consulta['status_pagamento'] != 'pago'): ?>
                                    <button type="button" class="btn btn-success btn-sm btn-pagamento" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPagamento"
                                            data-consulta-id="<?= $consulta['id'] ?>"
                                            data-paciente-nome="<?= htmlspecialchars($consulta['paciente_nome']) ?>"
                                            data-servico-nome="<?= htmlspecialchars($consulta['servico_nome'] ?? '') ?>">
                                        Registrar Pagamento
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Nenhuma consulta agendada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalPagamento" tabindex="-1" aria-labelledby="modalPagamentoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagamentoLabel">Registrar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Paciente:</strong> <span id="modal-paciente-nome"></span></p>
                <p><strong>Serviço:</strong> <span id="modal-servico-nome"></span></p>
                <form id="formPagamento" method="POST" action="salvar_pagamento.php">
                    <input type="hidden" name="consulta_id" id="modal-consulta-id">
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor *</label>
                        <input type="number" step="0.01" name="valor" id="valor" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="data_pagamento" class="form-label">Data do Pagamento *</label>
                        <input type="date" name="data_pagamento" id="data_pagamento" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="metodo_pagamento" class="form-label">Método de Pagamento *</label>
                        <select name="metodo_pagamento" id="metodo_pagamento" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea name="observacoes" id="observacoes" rows="3" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Pagamento</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalPagamento = document.getElementById('modalPagamento');
    const formPagamento = document.getElementById('formPagamento');
    const dataPagamentoInput = document.getElementById('data_pagamento');

    if (modalPagamento) {
        modalPagamento.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const consultaId = button.getAttribute('data-consulta-id');
            const pacienteNome = button.getAttribute('data-paciente-nome');
            const servicoNome = button.getAttribute('data-servico-nome');

            const modalPacienteNome = modalPagamento.querySelector('#modal-paciente-nome');
            const modalServicoNome = modalPagamento.querySelector('#modal-servico-nome');
            const modalConsultaId = modalPagamento.querySelector('#modal-consulta-id');
            
            modalPacienteNome.textContent = pacienteNome;
            modalServicoNome.textContent = servicoNome;
            modalConsultaId.value = consultaId;

            const hoje = new Date().toISOString().split('T')[0];
            dataPagamentoInput.value = hoje;
        });

        // Adiciona um listener para limpar o modal ao ser fechado
        modalPagamento.addEventListener('hidden.bs.modal', function() {
            formPagamento.reset();
            modalPagamento.querySelector('#modal-paciente-nome').textContent = '';
            modalPagamento.querySelector('#modal-servico-nome').textContent = '';
        });
    }

    if (formPagamento) {
        formPagamento.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const modal = bootstrap.Modal.getInstance(modalPagamento);
                if (modal) {
                    modal.hide();
                }
                
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro na requisição. Por favor, tente novamente.');
            });
        });
    }
});
</script>

<?php
// O fechamento dos resultados e statements pode ocorrer aqui, antes de incluir o rodapé
if ($resultado_medicos) {
    $resultado_medicos->free();
}
if ($stmt) {
    $stmt->close();
}
if ($resultado) {
    $resultado->free();
}

// O require_once do rodapé vem aqui. Ele, por sua vez, fechará a conexão com o banco de dados.
require_once __DIR__ . '/../includes/rodape.php';
?>