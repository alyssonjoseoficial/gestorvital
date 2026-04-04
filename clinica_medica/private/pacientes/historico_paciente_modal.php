<?php
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../../config/config.php';

$paciente_id = $_GET['paciente_id'] ?? 0;

if ($paciente_id > 0) {
    // Busca todas as consultas do paciente, ordenadas da mais recente para a mais antiga
    $stmt = $conn->prepare("
        SELECT 
            c.id, 
            c.data_consulta, 
            pr.queixa_principal, 
            pr.historia_doenca_atual, 
            pr.antecedentes_pessoais,
            pr.antecedentes_familiares,
            pr.exame_fisico,
            pr.exames_complementares,
            pr.diagnostico,
            pr.prescricao,
            pr.orientacoes,
            pr.observacoes
        FROM consultas c
        LEFT JOIN prontuarios pr ON c.id = pr.consulta_id
        WHERE c.paciente_id = ? AND c.status = 'realizada' AND pr.queixa_principal IS NOT NULL
        ORDER BY c.data_consulta DESC
    ");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $consultas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<?php if ($paciente_id == 0): ?>
    <div class="alert alert-danger" role="alert">
        ID do paciente não fornecido.
    </div>
<?php elseif (empty($consultas)): ?>
    <div class="alert alert-info" role="alert">
        Nenhum histórico de consulta encontrado para este paciente.
    </div>
<?php else: ?>
    <div class="accordion" id="historicoAccordion">
        <?php foreach ($consultas as $index => $consulta): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_<?= $consulta['id'] ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $consulta['id'] ?>" aria-expanded="false" aria-controls="collapse_<?= $consulta['id'] ?>">
                        Consulta em **<?= date('d/m/Y', strtotime($consulta['data_consulta'])) ?>** - Queixa: **<?= htmlspecialchars($consulta['queixa_principal'] ?? 'Não informado') ?>**
                    </button>
                </h2>
                <div id="collapse_<?= $consulta['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?= $consulta['id'] ?>" data-bs-parent="#historicoAccordion">
                    <div class="accordion-body">
                        <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">História da Doença Atual</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['historia_doenca_atual'] ?? 'Não informado')) ?></p>
                        </div>
                        <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">Antecedentes Pessoais</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['antecedentes_pessoais'] ?? 'Não informado')) ?></p>
                        </div>
                        <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">Antecedentes Familiares</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['antecedentes_familiares'] ?? 'Não informado')) ?></p>
                        </div>
                        <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">Exame Físico</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['exame_fisico'] ?? 'Não informado')) ?></p>
                        </div>
                        <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">Exames Complementares</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['exames_complementares'] ?? 'Não informado')) ?></p>
                        </div>
                        <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">Diagnóstico</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['diagnostico'] ?? 'Não informado')) ?></p>
                        </div>
                        <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">Prescrição</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['prescricao'] ?? 'Não informado')) ?></p>
                        </div>
                         <div class="card card-body bg-light mb-3">
                            <h6 class="card-subtitle mb-2 text-muted">Orientações</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['orientacoes'] ?? 'Não informado')) ?></p>
                        </div>
                         <div class="card card-body bg-light">
                            <h6 class="card-subtitle mb-2 text-muted">Observações</h6>
                            <p class="card-text"><?= nl2br(htmlspecialchars($consulta['observacoes'] ?? 'Não informado')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>