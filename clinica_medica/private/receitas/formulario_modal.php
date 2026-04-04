<?php
require_once __DIR__ . '/../../config/config.php';

$consulta_id = $_GET['consulta_id'] ?? 0;
$receita = null;

if ($consulta_id) {
    // Tentar buscar receita existente
    $stmt_receita = $conn->prepare("SELECT * FROM receitas WHERE consulta_id = ?");
    $stmt_receita->bind_param("i", $consulta_id);
    $stmt_receita->execute();
    $result_receita = $stmt_receita->get_result();
    if ($result_receita->num_rows > 0) {
        $receita = $result_receita->fetch_assoc();
    }
}
?>

<form id="formReceita" method="POST">
    <input type="hidden" name="consulta_id" value="<?= htmlspecialchars($consulta_id) ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($receita['id'] ?? '') ?>">
    
    <div class="mb-3">
        <label for="receita" class="form-label">Descrição da Receita</label>
        <textarea id="receita" name="receita" class="form-control" rows="10" required><?= htmlspecialchars($receita['receita'] ?? '') ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="instrucoes_gerais" class="form-label">Instruções Gerais</label>
        <textarea id="instrucoes_gerais" name="instrucoes_gerais" class="form-control" rows="3"><?= htmlspecialchars($receita['instrucoes_gerais'] ?? '') ?></textarea>
    </div>
</form>