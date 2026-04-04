<?php
require_once __DIR__ . '/../../config/config.php';

$consulta_id = $_GET['consulta_id'] ?? 0;
$prontuario = null;

if ($consulta_id) {
    // Tenta buscar o prontuário a partir da consulta_id
    $stmt_prontuario = $conn->prepare("SELECT * FROM prontuarios WHERE consulta_id = ?");
    $stmt_prontuario->bind_param("i", $consulta_id);
    $stmt_prontuario->execute();
    $result_prontuario = $stmt_prontuario->get_result();
    if ($result_prontuario->num_rows > 0) {
        $prontuario = $result_prontuario->fetch_assoc();
    }
}
?>

<form id="formProntuario" method="POST">
    <input type="hidden" name="consulta_id" value="<?= htmlspecialchars($consulta_id) ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($prontuario['id'] ?? '') ?>">
    
    <div class="mb-3">
        <label for="queixa_principal" class="form-label">Queixa Principal</label>
        <textarea id="queixa_principal" name="queixa_principal" class="form-control" rows="3" required><?= htmlspecialchars($prontuario['queixa_principal'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="historia_doenca_atual" class="form-label">História da Doença Atual</label>
        <textarea id="historia_doenca_atual" name="historia_doenca_atual" class="form-control" rows="5"><?= htmlspecialchars($prontuario['historia_doenca_atual'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="antecedentes_pessoais" class="form-label">Antecedentes Pessoais</label>
        <textarea id="antecedentes_pessoais" name="antecedentes_pessoais" class="form-control" rows="5"><?= htmlspecialchars($prontuario['antecedentes_pessoais'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="antecedentes_familiares" class="form-label">Antecedentes Familiares</label>
        <textarea id="antecedentes_familiares" name="antecedentes_familiares" class="form-control" rows="5"><?= htmlspecialchars($prontuario['antecedentes_familiares'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="exame_fisico" class="form-label">Exame Físico</label>
        <textarea id="exame_fisico" name="exame_fisico" class="form-control" rows="5"><?= htmlspecialchars($prontuario['exame_fisico'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="exames_complementares" class="form-label">Exames Complementares</label>
        <textarea id="exames_complementares" name="exames_complementares" class="form-control" rows="5"><?= htmlspecialchars($prontuario['exames_complementares'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="diagnostico" class="form-label">Diagnóstico</label>
        <textarea id="diagnostico" name="diagnostico" class="form-control" rows="3"><?= htmlspecialchars($prontuario['diagnostico'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="prescricao" class="form-label">Prescrição</label>
        <textarea id="prescricao" name="prescricao" class="form-control" rows="5"><?= htmlspecialchars($prontuario['prescricao'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="orientacoes" class="form-label">Orientações</label>
        <textarea id="orientacoes" name="orientacoes" class="form-control" rows="5"><?= htmlspecialchars($prontuario['orientacoes'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="observacoes" class="form-label">Observações</label>
        <textarea id="observacoes" name="observacoes" class="form-control" rows="5"><?= htmlspecialchars($prontuario['observacoes'] ?? '') ?></textarea>
    </div>
    
    <?php if ($prontuario && isset($prontuario['id'])): ?>
        <hr>
        <div class="mb-4">
            <h4>Evolução</h4>
            <div id="historicoEvolucao" class="mb-3">
                </div>
            
            <div class="input-group">
                <textarea id="novaEvolucao" class="form-control" rows="3" placeholder="Digite uma nova evolução..."></textarea>
                <button type="button" class="btn btn-success" id="btnSalvarEvolucao">Salvar Evolução</button>
            </div>
        </div>
    <?php endif; ?>
</form>