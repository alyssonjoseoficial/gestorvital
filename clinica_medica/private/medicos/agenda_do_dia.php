<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
include_once "../includes/menu.php";

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($nivel !== 'medico') {
    die("Acesso negado.");
}

$hoje = date('Y-m-d');
$data_selecionada = $_GET['data'] ?? $hoje;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_selecionada)) {
    $data_selecionada = $hoje;
}

// Buscar o ID do médico vinculado ao usuário logado
$stmt_med = $conn->prepare("
    SELECT m.id
    FROM medicos m
    INNER JOIN medico_usuario mu ON mu.medico_id = m.id
    WHERE mu.usuario_id = ?
    LIMIT 1
");
$stmt_med->bind_param("i", $usuario_id);
$stmt_med->execute();
$result_med = $stmt_med->get_result();
$medico = $result_med->fetch_assoc();

if (!$medico) {
    die("Médico não encontrado para o usuário logado.");
}
$medico_id = $medico['id'];

$sucesso = $_SESSION['sucesso_mensagem'] ?? '';
$erro = $_SESSION['erro_mensagem'] ?? '';
unset($_SESSION['sucesso_mensagem']);
unset($_SESSION['erro_mensagem']);

// NOVO CÓDIGO: Juntar com a tabela de serviços para obter o nome do serviço
$sql = "SELECT c.id, c.paciente_id, p.nome AS paciente_nome, s.nome AS servico_nome, c.turno, c.status,
                 pr.id AS prontuario_id
         FROM consultas c
         JOIN pacientes p ON c.paciente_id = p.id
         LEFT JOIN servicos s ON c.servico_id = s.id
         LEFT JOIN prontuarios pr ON c.id = pr.consulta_id
         WHERE c.medico_id = ? AND c.data_consulta = ?
         ORDER BY c.turno ASC, p.nome ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $medico_id, $data_selecionada);
$stmt->execute();
$result_consultas = $stmt->get_result();
?>

<div class="container mt-4">
    <h3>Agenda de Consultas para <?= date('d/m/Y', strtotime($data_selecionada)) ?></h3>

    <?php if ($sucesso): ?>
        <div class="alert alert-success mt-3"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <label for="data_agenda" class="form-label">Selecionar Outra Data:</label>
        <input type="date" id="data_agenda" class="form-control" value="<?= htmlspecialchars($data_selecionada) ?>">
    </div>

    <?php if ($result_consultas->num_rows === 0): ?>
        <p>Você não tem consultas agendadas para esta data.</p>
    <?php else: ?>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Serviço</th>
                    <th>Turno</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($consulta = $result_consultas->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($consulta['paciente_nome']) ?></td>
                        <td><?= htmlspecialchars($consulta['servico_nome'] ?? 'N/A') ?></td>
                        <td><?= ($consulta['turno'] === 'manha') ? 'Manhã' : 'Tarde' ?></td>
                        <td>
                            <?php if ($consulta['status'] == 'agendada'): ?>
                                <span class="badge bg-info">Agendada</span>
                            <?php elseif ($consulta['status'] == 'cancelada'): ?>
                                <span class="badge bg-danger">Cancelada</span>
                            <?php elseif ($consulta['status'] == 'realizada'): ?>
                                <span class="badge bg-success">Realizada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary btn-prontuario"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalProntuario"
                                    data-consulta-id="<?= $consulta['id'] ?>"
                                    data-paciente-id="<?= $consulta['paciente_id'] ?>"
                                    data-prontuario-id="<?= $consulta['prontuario_id'] ?>">
                                Ações
                            </button>
                            <?php if ($consulta['status'] !== 'realizada'): ?>
                                <button type="button" class="btn btn-sm btn-success alterar-status-btn"
                                        data-consulta-id="<?= $consulta['id'] ?>">Marcar como Realizada</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalProntuario" tabindex="-1" aria-labelledby="modalProntuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProntuarioLabel">Ações para a Consulta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="prontuarioTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="prontuario-tab" data-bs-toggle="tab" data-bs-target="#prontuario" type="button" role="tab" aria-controls="prontuario" aria-selected="true">Prontuário</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="solicitar-exame-tab" data-bs-toggle="tab" data-bs-target="#solicitar-exame" type="button" role="tab" aria-controls="solicitar-exame" aria-selected="false">Solicitar Exame</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="receita-tab" data-bs-toggle="tab" data-bs-target="#receita" type="button" role="tab" aria-controls="receita" aria-selected="false">Receita</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="atestado-tab" data-bs-toggle="tab" data-bs-target="#atestado" type="button" role="tab" aria-controls="atestado" aria-selected="false">Atestado</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button" role="tab" aria-controls="historico" aria-selected="false">Histórico</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="prontuarioTabContent">
                    <div class="tab-pane fade show active" id="prontuario" role="tabpanel" aria-labelledby="prontuario-tab">
                        <p>Carregando conteúdo do prontuário...</p>
                    </div>
                    <div class="tab-pane fade" id="solicitar-exame" role="tabpanel" aria-labelledby="solicitar-exame-tab">
                         <p>Carregando formulário de solicitação de exame...</p>
                    </div>
                    <div class="tab-pane fade" id="receita" role="tabpanel" aria-labelledby="receita-tab">
                        <p>Carregando conteúdo da receita...</p>
                    </div>
                    <div class="tab-pane fade" id="atestado" role="tabpanel" aria-labelledby="atestado-tab">
                        <p>Carregando conteúdo do atestado...</p>
                    </div>
                    <div class="tab-pane fade" id="historico" role="tabpanel" aria-labelledby="historico-tab">
                        <p>Carregando histórico...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarProntuario">Salvar</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataInput = document.getElementById('data_agenda');
    dataInput.addEventListener('change', function() {
        const novaData = this.value;
        if (novaData) {
            window.location.href = `agenda_do_dia.php?data=${novaData}`;
        }
    });

    const modalProntuario = document.getElementById('modalProntuario');
    const prontuarioTabPane = document.getElementById('prontuario');
    const receitaTabPane = document.getElementById('receita');
    const atestadoTabPane = document.getElementById('atestado');
    const historicoTabPane = document.getElementById('historico');
    const solicitarExameTabPane = document.getElementById('solicitar-exame');
    const prontuarioTabButton = document.getElementById('prontuario-tab');
    const receitaTabButton = document.getElementById('receita-tab');
    const atestadoTabButton = document.getElementById('atestado-tab');
    const historicoTabButton = document.getElementById('historico-tab');
    const solicitarExameTabButton = document.getElementById('solicitar-exame-tab');
    const btnSalvarProntuario = document.getElementById('btnSalvarProntuario');

    let currentConsultaId = null;
    let currentPacienteId = null;
    let currentProntuarioId = null;
    let currentReceitaId = null;
    let currentAtestadoId = null;

    function loadTabContent(tabId, url, callback = null) {
        const tabPane = document.getElementById(tabId);
        tabPane.innerHTML = '<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao carregar o conteúdo.');
                }
                return response.text();
            })
            .then(html => {
                tabPane.innerHTML = html;
                const scripts = tabPane.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    if (script.src) {
                        newScript.src = script.src;
                    } else {
                        newScript.textContent = script.textContent;
                    }
                    document.body.appendChild(newScript);
                    newScript.onload = () => newScript.remove();
                });
                if (callback) callback();
            })
            .catch(error => {
                tabPane.innerHTML = '<div class="alert alert-danger">Erro ao carregar o conteúdo.</div>';
                console.error('Erro ao carregar o conteúdo:', error);
            });
    }

    modalProntuario.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        currentConsultaId = button.getAttribute('data-consulta-id');
        currentPacienteId = button.getAttribute('data-paciente-id');

        const pacienteNome = button.closest('tr').querySelector('td').textContent;
        document.getElementById('modalProntuarioLabel').textContent = `Ações - ${pacienteNome}`;

        const prontuarioTab = new bootstrap.Tab(prontuarioTabButton);
        prontuarioTab.show();
        loadProntuarioContent();
    });

    function loadProntuarioContent() {
        fetch('../prontuarios/verificar_prontuario.php?consulta_id=' + currentConsultaId)
            .then(response => response.json())
            .then(data => {
                currentProntuarioId = data.prontuario_id;
                let urlProntuario = '';
                if (currentProntuarioId) {
                    urlProntuario = `../prontuarios/visualizar_modal.php?consulta_id=${currentConsultaId}`;
                    prontuarioTabButton.textContent = 'Prontuário';
                    btnSalvarProntuario.style.display = 'none';
                } else {
                    urlProntuario = `../prontuarios/formulario_modal.php?consulta_id=${currentConsultaId}`;
                    prontuarioTabButton.textContent = 'Criar Prontuário';
                    btnSalvarProntuario.style.display = 'inline-block';
                }
                loadTabContent('prontuario', urlProntuario);
            });
    }

    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabButton => {
        tabButton.addEventListener('shown.bs.tab', function(event) {
            const targetTabId = event.target.getAttribute('data-bs-target').substring(1);

            if (targetTabId === 'prontuario') {
                loadProntuarioContent();
                return;
            } else if (targetTabId === 'solicitar-exame') {
                const url = `../prontuarios/visualizar_solicitacao.php?consulta_id=${currentConsultaId}`;
                btnSalvarProntuario.style.display = 'none';
                loadTabContent(targetTabId, url);
            } else if (targetTabId === 'receita') {
                fetch('../receitas/verificar_receita.php?consulta_id=' + currentConsultaId)
                    .then(response => response.json())
                    .then(data => {
                        currentReceitaId = data.receita_id;
                        if (currentReceitaId) {
                            url = `../receitas/visualizar_modal.php?consulta_id=${currentConsultaId}`;
                            loadTabContent(targetTabId, url, () => {
                                btnSalvarProntuario.style.display = 'none';
                            });
                        } else {
                            url = `../receitas/formulario_modal.php?consulta_id=${currentConsultaId}`;
                            loadTabContent(targetTabId, url, () => {
                                btnSalvarProntuario.style.display = 'inline-block';
                            });
                        }
                    });
            } else if (targetTabId === 'atestado') {
                fetch('../atestados/verificar_atestado.php?consulta_id=' + currentConsultaId)
                    .then(response => response.json())
                    .then(data => {
                        currentAtestadoId = data.atestado_id;
                        if (currentAtestadoId) {
                            url = `../atestados/visualizar_modal.php?consulta_id=${currentConsultaId}`;
                            loadTabContent(targetTabId, url, () => {
                                btnSalvarProntuario.style.display = 'none';
                            });
                        } else {
                            url = `../atestados/formulario_modal.php?consulta_id=${currentConsultaId}`;
                            loadTabContent(targetTabId, url, () => {
                                btnSalvarProntuario.style.display = 'inline-block';
                            });
                        }
                    });
            } else if (targetTabId === 'historico') {
                url = `../pacientes/historico_paciente_modal.php?paciente_id=${currentPacienteId}`;
                loadTabContent(targetTabId, url, () => {
                    btnSalvarProntuario.style.display = 'none';
                });
            }
        });
    });

    // Novo ouvinte de evento para o botão de "Nova Solicitação"
    document.addEventListener('click', function(event) {
        const target = event.target.closest('.btn-nova-solicitacao');
        if (target) {
            const consultaId = target.getAttribute('data-consulta-id');
            const urlForm = `../prontuarios/solicitar_exame_modal.php?consulta_id=${consultaId}`;
            const targetTabId = 'solicitar-exame';
            const tabPane = document.getElementById(targetTabId);

            tabPane.innerHTML = '<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            fetch(urlForm)
                .then(response => response.text())
                .then(html => {
                    tabPane.innerHTML = html;
                    const scripts = tabPane.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.body.appendChild(newScript);
                        newScript.onload = () => newScript.remove();
                    });
                })
                .catch(error => {
                    tabPane.innerHTML = '<div class="alert alert-danger">Erro ao carregar o formulário.</div>';
                    console.error('Erro ao carregar o conteúdo:', error);
                });
        }
    });

    modalProntuario.addEventListener('click', function(event) {
        if (event.target.closest('.btn-editar')) {
            const urlForm = `../prontuarios/formulario_modal.php?consulta_id=${currentConsultaId}`;
            prontuarioTabButton.textContent = 'Editar Prontuário';
            btnSalvarProntuario.style.display = 'inline-block';
            loadTabContent('prontuario', urlForm);
        }
    });

    modalProntuario.addEventListener('click', function(event) {
        if (event.target.closest('.btn-editar-receita')) {
            const urlForm = `../receitas/formulario_modal.php?consulta_id=${currentConsultaId}`;
            const receitaTab = new bootstrap.Tab(receitaTabButton);
            receitaTab.show();
            btnSalvarProntuario.style.display = 'inline-block';
            loadTabContent('receita', urlForm);
        }
    });

    modalProntuario.addEventListener('click', function(event) {
        if (event.target.closest('.btn-editar-atestado')) {
            const urlForm = `../atestados/formulario_modal.php?consulta_id=${currentConsultaId}`;
            const atestadoTab = new bootstrap.Tab(atestadoTabButton);
            atestadoTab.show();
            btnSalvarProntuario.style.display = 'inline-block';
            loadTabContent('atestado', urlForm);
        }
    });

    btnSalvarProntuario.onclick = function() {
        let form;
        let url;
        if (prontuarioTabPane.classList.contains('active')) {
            form = document.getElementById('formProntuario');
            url = '../prontuarios/salvar_prontuario.php';
        } else if (receitaTabPane.classList.contains('active')) {
            form = document.getElementById('formReceita');
            url = '../receitas/salvar_receita.php';
        } else if (atestadoTabPane.classList.contains('active')) {
            form = document.getElementById('formAtestado');
            url = '../atestados/salvar_atestado.php';
        }

        if (form && url) {
            const formData = new FormData(form);
            saveData(url, formData);
        }
    };

    function saveData(url, formData) {
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro na requisição. Verifique sua conexão.');
            console.error('Erro ao salvar:', error);
        });
    }

    document.querySelectorAll('.alterar-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const consultaId = this.dataset.consultaId;
            if (confirm('Tem certeza que deseja marcar esta consulta como "Realizada"?')) {
                fetch('alterar_status_consulta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${consultaId}&status=realizada`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status da consulta atualizado com sucesso!');
                        window.location.reload();
                    } else {
                        alert('Erro ao atualizar o status: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Erro de comunicação com o servidor.');
                });
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/rodape.php'; ?>