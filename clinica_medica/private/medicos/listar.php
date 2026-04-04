<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../includes/menu.php';

if (!in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao'])) {
    die('Acesso negado.');
}

$sql = "SELECT id, nome, crm, especialidade, telefone, email, horario_atendimento FROM medicos ORDER BY nome ASC";
$resultado = $conn->query($sql);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Médicos</h4>
        <a href="cadastrar.php" class="btn btn-success">+ Novo Médico</a>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nome</th>
                <th>CRM</th>
                <th>Especialidade</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>Horário de Atendimento</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($medico = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($medico['nome']) ?></td>
                    <td><?= htmlspecialchars($medico['crm']) ?></td>
                    <td><?= htmlspecialchars($medico['especialidade']) ?></td>
                    <td><?= htmlspecialchars($medico['telefone']) ?></td>
                    <td><?= htmlspecialchars($medico['email']) ?></td>
                    <td><?= htmlspecialchars($medico['horario_atendimento']) ?></td>
                    <td>
                        <a href="editar.php?id=<?= $medico['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                        <a href="excluir.php?id=<?= $medico['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir este médico?')">Excluir</a>
                        <a href="definir_agenda.php?medico_id=<?= $medico['id'] ?>" class="btn btn-success btn-sm">Agenda</a>
                        <button type="button" class="btn btn-info btn-sm btn-agenda"
                                data-bs-toggle="modal" data-bs-target="#modalAgendaTabela"
                                data-medico-id="<?= $medico['id'] ?>"
                                data-medico-nome="<?= htmlspecialchars($medico['nome']) ?>">
                            Consultas
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>

<div class="modal fade" id="modalAgendaTabela" tabindex="-1" aria-labelledby="modalAgendaTabelaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title" id="modalAgendaTabelaLabel">Agenda de Consultas - Dr(a). <span id="medico-nome-agenda-tabela"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 d-flex justify-content-end">
            <a href="#" id="btn-cadastrar-consulta" class="btn btn-success">Cadastrar Nova Consulta</a>
        </div>
        <div id='tabela-consultas-medico'></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var agendaButtons = document.querySelectorAll('.btn-agenda');
    var modalTabela = new bootstrap.Modal(document.getElementById('modalAgendaTabela'));
    var btnCadastrarConsulta = document.getElementById('btn-cadastrar-consulta');

    agendaButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var medicoId = this.getAttribute('data-medico-id');
            var medicoNome = this.getAttribute('data-medico-nome');

            document.getElementById('medico-nome-agenda-tabela').textContent = medicoNome;

            btnCadastrarConsulta.href = '../consultas/cadastrar.php?medico_id=' + medicoId;

            // Faz a chamada AJAX para a API que retorna a tabela
            fetch('../../api/get_consultas_por_medico_table.php?medico_id=' + medicoId)
                .then(response => {
                    if (!response.ok) {
                        return Promise.reject('Erro ao carregar a tabela.');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('tabela-consultas-medico').innerHTML = html;
                    modalTabela.show();
                })
                .catch(error => {
                    console.error('Erro ao buscar as consultas:', error);
                    document.getElementById('tabela-consultas-medico').innerHTML = '<p class="text-danger">Erro ao carregar as consultas.</p>';
                });
        });
    });
});
</script>
