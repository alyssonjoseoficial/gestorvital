<?php
require_once __DIR__ . '/../../config/config.php';

$consulta_id = $_GET['consulta_id'] ?? 0;

$solicitacoes = [];
if ($consulta_id) {
    $stmt = $conn->prepare("SELECT id, descricao, data_solicitacao FROM solicitacoes_exame WHERE consulta_id = ? ORDER BY data_solicitacao DESC");
    $stmt->bind_param("i", $consulta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $solicitacoes[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Histórico de Solicitações de Exame</h4>
    <button type="button" class="btn btn-success btn-sm btn-nova-solicitacao" data-consulta-id="<?= htmlspecialchars($consulta_id) ?>">
        <i class="fas fa-plus"></i> Nova Solicitação
    </button>
</div>

<?php if (empty($solicitacoes)): ?>
    <div class="alert alert-info">Nenhuma solicitação de exame encontrada para esta consulta.</div>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($solicitacoes as $solicitacao): ?>
            <li class="list-group-item mb-3 shadow-sm rounded">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">Solicitado em: <?= htmlspecialchars(date('d/m/Y', strtotime($solicitacao['data_solicitacao']))) ?></h6>
                </div>
                <p class="mb-1"><?= nl2br(htmlspecialchars($solicitacao['descricao'])) ?></p>
                <div class="d-flex justify-content-end mt-3">
                    <a href="../prontuarios/gerar_exame_pdf.php?id=<?= $solicitacao['id'] ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-print"></i> Imprimir Pedido
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>