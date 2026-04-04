<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

$nivel = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel, ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}
?>

<div class="container mt-4">
    <h4>Relatórios Financeiros</h4>
    <p>Conteúdo da página de relatórios financeiros.</p>
</div>

<?php include_once __DIR__ . '/../includes/rodape.php'; ?>