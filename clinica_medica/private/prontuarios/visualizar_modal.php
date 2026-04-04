<?php
require_once __DIR__ . '/../../config/config.php';

$consulta_id = $_GET['consulta_id'] ?? 0;
$prontuario = null;
$consulta_info = null;

if ($consulta_id) {
    // Buscar informações da consulta e paciente
    $stmt_consulta = $conn->prepare("
        SELECT c.id, c.paciente_id, p.nome AS paciente_nome, p.data_nascimento, p.telefone
        FROM consultas c
        JOIN pacientes p ON c.paciente_id = p.id
        WHERE c.id = ?
    ");
    $stmt_consulta->bind_param("i", $consulta_id);
    $stmt_consulta->execute();
    $result_consulta = $stmt_consulta->get_result();
    $consulta_info = $result_consulta->fetch_assoc();

    // Tentar buscar prontuário existente
    $stmt_prontuario = $conn->prepare("SELECT * FROM prontuarios WHERE consulta_id = ?");
    $stmt_prontuario->bind_param("i", $consulta_id);
    $stmt_prontuario->execute();
    $result_prontuario = $stmt_prontuario->get_result();
    if ($result_prontuario->num_rows > 0) {
        $prontuario = $result_prontuario->fetch_assoc();
    }
}
?>

<?php if ($prontuario && $consulta_info): ?>
    <input type="hidden" id="baseUrlPrivate" value="<?= htmlspecialchars(BASE_URL_PRIVATE) ?>">
    
    <div class="tab-pane fade show active" id="prontuario-content" role="tabpanel" aria-labelledby="prontuario-tab">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Detalhes do Prontuário</h4>
            <button type="button" class="btn btn-warning btn-editar" data-action="edit" data-consulta-id="<?= $consulta_id ?>">
                <i class="bi bi-pencil-square me-1"></i> Editar
            </button>
        </div>
        <hr>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <strong>Paciente:</strong> <?= htmlspecialchars($consulta_info['paciente_nome']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Data de Nascimento:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($consulta_info['data_nascimento']))) ?>
            </div>
        </div>
        <div class="mb-3">
            <strong>Telefone:</strong> <?= htmlspecialchars($consulta_info['telefone']) ?>
        </div>
        <hr>
        
        <div class="mb-3">
            <strong>Queixa Principal:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['queixa_principal'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>História da Doença Atual:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['historia_doenca_atual'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Antecedentes Pessoais:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['antecedentes_pessoais'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Antecedentes Familiares:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['antecedentes_familiares'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Exame Físico:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['exame_fisico'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Exames Complementares:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['exames_complementares'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Diagnóstico:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['diagnostico'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Prescrição:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['prescricao'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Orientações:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['orientacoes'])) ?></p>
        </div>
        <div class="mb-3">
            <strong>Observações:</strong><br>
            <p><?= nl2br(htmlspecialchars($prontuario['observacoes'])) ?></p>
        </div>
    </div>

    <div class="tab-pane fade" id="solicitar-exame-content" role="tabpanel" aria-labelledby="solicitar-exame-tab">
        <form id="formSolicitarExame">
            <input type="hidden" name="consulta_id" value="<?= htmlspecialchars($consulta_id) ?>">
            <div class="mb-3">
                <label for="descricaoExame" class="form-label">Descrição dos Exames</label>
                <textarea class="form-control" id="descricaoExame" name="descricao" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Solicitação</button>
        </form>
    </div>

    <div class="tab-pane fade" id="receita-content" role="tabpanel" aria-labelledby="receita-tab">
        <h5>Formulário de Receita</h5>
        <p>Conteúdo da sua funcionalidade de Receita vai aqui.</p>
    </div>

    <div class="tab-pane fade" id="historico-content" role="tabpanel" aria-labelledby="historico-tab">
        <h5>Histórico de Prontuários</h5>
        <p>Lista dos prontuários anteriores do paciente vai aqui.</p>
    </div>

    <div class="tab-pane fade" id="atestado-content" role="tabpanel" aria-labelledby="atestado-tab">
        <h5>Formulário de Atestado</h5>
        <p>Formulário para emissão de atestado vai aqui.</p>
    </div>

    <script>
    document.getElementById('formSolicitarExame').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var form = e.target;
        var formData = new FormData(form);
        
        // Pega o caminho base do seu site para montar a URL correta
        var baseUrl = document.getElementById('baseUrlPrivate').value;

        fetch(baseUrl + 'prontuarios/salvar_solicitacao.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Oculta o modal e limpa o formulário
                var modal = bootstrap.Modal.getInstance(document.getElementById('prontuarioModal'));
                modal.hide();
                // Opcional: recarregar a página para mostrar os dados atualizados
                // window.location.reload(); 
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao tentar salvar a solicitação.');
        });
    });
    </script>
<?php else: ?>
    <div class="alert alert-danger">Nenhum prontuário encontrado para esta consulta.</div>
<?php endif; ?>