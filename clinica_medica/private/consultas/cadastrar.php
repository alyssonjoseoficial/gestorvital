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

$erro = $_SESSION['erro_mensagem'] ?? '';
unset($_SESSION['erro_mensagem']);
$sucesso = $_SESSION['sucesso_mensagem'] ?? '';
unset($_SESSION['sucesso_mensagem']);
$medico_id_padrao = $_GET['medico_id'] ?? null;

// Buscar pacientes e médicos para os selects
$medicos = $conn->query("SELECT id, nome FROM medicos ORDER BY nome ASC");

// CÓDIGO ATUALIZADO: Buscar APENAS os serviços de "Consulta"
$servicos = $conn->query("SELECT id, nome FROM servicos WHERE tipo = 'Consulta' ORDER BY nome ASC");

?>
<div class="container mt-4">
    <h4>Agendar Nova Consulta</h4>

    <?php if ($sucesso): ?>
        <div class="alert alert-success mt-3"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form action="salvar.php" method="POST" id="form-agendar-consulta">
        <div class="mb-3">
            <label for="paciente_nome_autocomplete" class="form-label">Paciente *</label>
            <input type="text" id="paciente_nome_autocomplete" class="form-control" placeholder="Digite o nome ou CPF do paciente" required>
            <input type="hidden" name="paciente_id" id="paciente_id" required>
            <div id="paciente-autocomplete-list" class="list-group position-absolute w-50" style="z-index: 1000;"></div>
            <div class="text-danger mt-1" id="erro-paciente" style="display:none;">Por favor, selecione um paciente da lista.</div>
        </div>

        <div class="mb-3">
            <label for="medico_id" class="form-label">Médico *</label>
            <select name="medico_id" id="medico_id" class="form-select" required>
                <option value="">Selecione um médico</option>
                <?php while ($medico = $medicos->fetch_assoc()): ?>
                    <option value="<?= $medico['id'] ?>" <?= ($medico_id_padrao == $medico['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($medico['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="servico_id" class="form-label">Serviço *</label>
            <select name="servico_id" id="servico_id" class="form-select" required>
                <option value="">Selecione o serviço</option>
                <?php while ($servico = $servicos->fetch_assoc()): ?>
                    <option value="<?= $servico['id'] ?>">
                        <?= htmlspecialchars($servico['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="data_consulta" class="form-label">Data da Consulta *</label>
            <input type="date" name="data_consulta" id="data_consulta" class="form-control" required
                value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
        </div>

        <div class="mb-3 card p-3" id="vagas-info" style="display:none;">
            <p id="vagas-mensagem" class="mb-1">Vagas disponíveis para a data selecionada:</p>
            <ul id="vagas-lista" class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Turno da Manhã: <span id="vagas-manha" class="badge bg-secondary rounded-pill">0</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Turno da Tarde: <span id="vagas-tarde" class="badge bg-secondary rounded-pill">0</span>
                </li>
            </ul>
            <button type="button" id="btn-encaixe" class="btn btn-warning mt-2 w-100" style="display:none;">
                Forçar Encaixe (Horário Manual)
            </button>
        </div>
        
        <div class="mb-3" id="turno-section">
            <label for="turno" class="form-label">Turno *</label>
            <select name="turno" id="turno" class="form-select" required>
                <option value="">Selecione o turno</option>
                <option value="manha">Manhã</option>
                <option value="tarde">Tarde</option>
            </select>
        </div>

        <div class="mb-3" id="encaixe-section" style="display:none;">
            <label for="hora_consulta" class="form-label">Horário do Encaixe *</label>
            <input type="time" name="hora_consulta" id="hora_consulta" class="form-control" step="300">
            <small class="form-text text-muted">A consulta será agendada para este horário, ignorando as vagas.</small>
            <button type="button" id="btn-voltar-turno" class="btn btn-secondary mt-2">Voltar para Vagas</button>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status *</label>
            <select name="status" id="status" class="form-select" required>
                <option value="agendada">Agendada</option>
                <option value="realizada">Realizada</option>
                <option value="cancelada">Cancelada</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="observacoes" class="form-label">Observações</label>
            <textarea name="observacoes" id="observacoes" rows="3" class="form-control"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Consulta</button>
        <a href="listar.php" class="btn btn-secondary">Voltar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-agendar-consulta');
    const medicoSelect = document.getElementById('medico_id');
    const dataInput = document.getElementById('data_consulta');
    const turnoSection = document.getElementById('turno-section');
    const turnoSelect = document.getElementById('turno');
    const vagasInfoDiv = document.getElementById('vagas-info');
    const vagasLista = document.getElementById('vagas-lista');
    const vagasMensagem = document.getElementById('vagas-mensagem');
    const manhaOption = turnoSelect.querySelector('option[value="manha"]');
    const tardeOption = turnoSelect.querySelector('option[value="tarde"]');

    const btnEncaixe = document.getElementById('btn-encaixe');
    const encaixeSection = document.getElementById('encaixe-section');
    const horaEncaixeInput = document.getElementById('hora_consulta');
    const btnVoltarTurno = document.getElementById('btn-voltar-turno');
    
    const pacienteAutocompleteInput = document.getElementById('paciente_nome_autocomplete');
    const pacienteIdInput = document.getElementById('paciente_id');
    const autocompleteList = document.getElementById('paciente-autocomplete-list');
    const erroPacienteDiv = document.getElementById('erro-paciente');

    pacienteAutocompleteInput.addEventListener('input', function() {
        const query = this.value;
        if (query.length < 3) {
            autocompleteList.innerHTML = '';
            pacienteIdInput.value = '';
            erroPacienteDiv.style.display = 'none';
            return;
        }

        fetch(`../../api/buscar_paciente.php?query=${query}`)
            .then(response => response.json())
            .then(data => {
                autocompleteList.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(paciente => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.classList.add('list-group-item', 'list-group-item-action');
                        item.textContent = `${paciente.nome} - CPF: ${paciente.cpf}`;
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            pacienteAutocompleteInput.value = paciente.nome;
                            pacienteIdInput.value = paciente.id;
                            autocompleteList.innerHTML = '';
                            erroPacienteDiv.style.display = 'none';
                        });
                        autocompleteList.appendChild(item);
                    });
                } else {
                    autocompleteList.innerHTML = '<a href="#" class="list-group-item">Nenhum paciente encontrado</a>';
                    pacienteIdInput.value = '';
                }
            })
            .catch(error => console.error('Erro no autocomplete:', error));
    });

    function buscarVagas() {
        const medicoId = medicoSelect.value;
        const data = dataInput.value;

        vagasInfoDiv.style.display = 'none';
        turnoSelect.value = '';
        turnoSelect.disabled = true;
        
        manhaOption.disabled = false;
        tardeOption.disabled = false;
        manhaOption.textContent = "Manhã";
        tardeOption.textContent = "Tarde";
        btnEncaixe.style.display = 'none';
        turnoSection.style.display = 'block';
        encaixeSection.style.display = 'none';
        vagasLista.style.display = 'block';
        vagasMensagem.textContent = 'Vagas disponíveis para a data selecionada:';

        horaEncaixeInput.value = '';
        horaEncaixeInput.removeAttribute('required');

        if (medicoId && data) {
            fetch(`../../api/get_vagas_por_turno.php?medico_id=${medicoId}&data=${data}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na comunicação com a API. Verifique o servidor.');
                    }
                    return response.json();
                })
                .then(vagas => {
                    vagasInfoDiv.style.display = 'block';
                    turnoSelect.disabled = false;

                    const vagasManha = vagas.manha;
                    const vagasTarde = vagas.tarde;

                    document.getElementById('vagas-manha').textContent = vagasManha;
                    document.getElementById('vagas-tarde').textContent = vagasTarde;
                    
                    if (vagasManha <= 0) {
                        manhaOption.disabled = true;
                        manhaOption.textContent = "Manhã (Esgotado)";
                    } else {
                        manhaOption.disabled = false;
                        manhaOption.textContent = `Manhã (${vagasManha} vagas)`;
                    }

                    if (vagasTarde <= 0) {
                        tardeOption.disabled = true;
                        tardeOption.textContent = "Tarde (Esgotado)";
                    } else {
                        tardeOption.disabled = false;
                        tardeOption.textContent = `Tarde (${vagasTarde} vagas)`;
                    }

                    if (vagasManha === 0 && vagasTarde === 0) {
                        vagasMensagem.textContent = 'Agenda para esta data não definida ou esgotada.';
                        vagasLista.style.display = 'none';
                        turnoSelect.disabled = true;
                        btnEncaixe.style.display = 'block';
                    }

                })
                .catch(error => {
                    vagasInfoDiv.style.display = 'block';
                    vagasInfoDiv.innerHTML = `<div class="alert alert-danger">
                        ${error.message}
                    </div>`;
                    turnoSelect.disabled = true;
                });
        }
    }

    btnEncaixe.addEventListener('click', function() {
        turnoSection.style.display = 'none';
        encaixeSection.style.display = 'block';
        horaEncaixeInput.setAttribute('required', 'required');
        
        turnoSelect.value = '';
        turnoSelect.removeAttribute('required');
    });

    btnVoltarTurno.addEventListener('click', function() {
        turnoSection.style.display = 'block';
        encaixeSection.style.display = 'none';
        
        horaEncaixeInput.value = '';
        horaEncaixeInput.removeAttribute('required');

        turnoSelect.setAttribute('required', 'required');
    });

    medicoSelect.addEventListener('change', buscarVagas);
    dataInput.addEventListener('change', buscarVagas);

    if (medicoSelect.value && dataInput.value) {
        buscarVagas();
    }

    // ** LÓGICA DE VALIDAÇÃO FINAL ANTES DO ENVIO DO FORMULÁRIO **
    form.addEventListener('submit', function(e) {
        if (!pacienteIdInput.value) {
            e.preventDefault(); // Impede o envio do formulário
            erroPacienteDiv.style.display = 'block';
            pacienteAutocompleteInput.focus();
        }
    });
});
</script>