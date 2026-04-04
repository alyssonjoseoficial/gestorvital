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

<?php if ($receita): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Detalhes da Receita</h4>
        <button type="button" class="btn btn-warning btn-editar-receita" data-action="edit" data-consulta-id="<?= $consulta_id ?>">
            <i class="bi bi-pencil-square me-1"></i> Editar
        </button>
    </div>
    <hr>
    
    <div class="mb-3">
        <strong>Receita:</strong><br>
        <p><?= nl2br(htmlspecialchars($receita['receita'])) ?></p>
    </div>
    
    <div class="mb-3">
        <strong>Instruções Gerais:</strong><br>
        <p><?= nl2br(htmlspecialchars($receita['instrucoes_gerais'])) ?></p>
		<div class="d-flex justify-content-end mb-3">
    <a href="../receitas/gerar_receita_pdf.php?id=<?= $receita['id'] ?>" target="_blank" class="btn btn-primary">
        <i class="fas fa-print"></i> Imprimir Receita
    </a>
</div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">Nenhuma receita encontrada para esta consulta.</div>
<?php endif; ?>