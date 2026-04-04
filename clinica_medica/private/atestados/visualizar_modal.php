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

<?php if ($atestado): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Detalhes do Atestado</h4>
        <button type="button" class="btn btn-warning btn-editar-atestado" data-action="edit" data-consulta-id="<?= $consulta_id ?>">
            <i class="bi bi-pencil-square me-1"></i> Editar
        </button>
    </div>
    <hr>
    
    <div class="mb-3">
        <strong>CID:</strong> <?= htmlspecialchars($atestado['cid'] ?? 'Não informado') ?>
    </div>
    
    <div class="mb-3">
        <strong>Dias de Repouso:</strong> <?= htmlspecialchars($atestado['dias_repouso']) ?>
    </div>
    
    <div class="mb-3">
        <strong>Motivo:</strong><br>
        <p><?= nl2br(htmlspecialchars($atestado['motivo_repouso'])) ?></p>
		<div class="d-flex justify-content-end mb-3">
    <a href="../atestados/gerar_atestado_pdf.php?id=<?= $atestado['id'] ?>" target="_blank" class="btn btn-primary">
        <i class="fas fa-print"></i> Imprimir Atestado
    </a>
</div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">Nenhum atestado encontrado para esta consulta.</div>
<?php endif; ?>