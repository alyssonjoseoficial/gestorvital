<?php
require_once __DIR__ . '/../../config/config.php';

$consulta_id = $_GET['consulta_id'] ?? 0;
$atestado = null;

if ($consulta_id) {
    // Tentar buscar atestado existente
    $stmt_atestado = $conn->prepare("SELECT * FROM atestados WHERE consulta_id = ?");
    $stmt_atestado->bind_param("i", $consulta_id);
    $stmt_atestado->execute();
    $result_atestado = $stmt_atestado->get_result();
    if ($result_atestado->num_rows > 0) {
        $atestado = $result_atestado->fetch_assoc();
    }
}
?>

<form id="formAtestado" method="POST">
    <input type="hidden" name="consulta_id" value="<?= htmlspecialchars($consulta_id) ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($atestado['id'] ?? '') ?>">
    
    <div class="mb-3">
        <label for="cid" class="form-label">CID (Código Internacional de Doenças)</label>
        <input type="text" id="cid" name="cid" class="form-control" value="<?= htmlspecialchars($atestado['cid'] ?? '') ?>" placeholder="Ex: A09.0">
    </div>
    
    <div class="mb-3">
        <label for="dias_repouso" class="form-label">Dias de Repouso</label>
        <input type="number" id="dias_repouso" name="dias_repouso" class="form-control" value="<?= htmlspecialchars($atestado['dias_repouso'] ?? '') ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="motivo_repouso" class="form-label">Motivo do Repouso / Diagnóstico</label>
        <textarea id="motivo_repouso" name="motivo_repouso" class="form-control" rows="5" required><?= htmlspecialchars($atestado['motivo_repouso'] ?? '') ?></textarea>
    </div>
</form>