<?php
session_start();
// O caminho de inclusão foi ajustado para a nova estrutura de pastas
require_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$id_clinica = $_SESSION['id_clinica'] ?? '';

// CORRIGIDO: Definindo o caminho base da clínica para gerar os links
$caminho_base_do_projeto = '/'; // A pasta raiz do domínio
$caminho_base_clinica = $caminho_base_do_projeto . $id_clinica . '/private/';

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!in_array($nivel, ['administrador', 'financeiro'])) {
    die("Acesso negado.");
}

// Define os cards e links específicos para o módulo financeiro
$cards = [
    'Pagamentos' => [
        // CORRIGIDO: Usando o caminho base para criar o link absoluto
        'link' => 'financeiro/relatorio_diario.php',
        'icon' => '💳',
    ],
    'Cadastro de Despesas' => [
        // CORRIGIDO: Usando o caminho base para criar o link absoluto
        'link' => 'despesas/cadastrar.php',
        'icon' => '💵',
    ],
    'Relatório de Despesas' => [
        // CORRIGIDO: Usando o caminho base para criar o link absoluto
        'link' => 'despesas/relatorio.php',
        'icon' => '📄',
    ],
    'Fluxo de Caixa' => [
        // CORRIGIDO: Usando o caminho base para criar o link absoluto
        'link' => 'financeiro/relatorio_fluxo_de_caixa.php',
        'icon' => '📊',
    ],
    'Balancete (Receitas/Despesas)' => [
        // CORRIGIDO: Usando o caminho base para criar o link absoluto
        'link' => 'financeiro/relatorio_balancete.php',
        'icon' => '⚖️',
    ],
];
?>
<style>
    body {
        background-color: #f0f2f5; /* Fundo cinza claro para contraste */
    }

    .card {
        border: none;
        border-radius: 1rem;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); /* Sombra mais destacada */
    }

    .card:hover {
        transform: translateY(-5px); /* Efeito de "levantar" ao passar o mouse */
        box-shadow: 0 8px 16px rgba(0,0,0,0.2); /* Sombra mais forte no hover */
    }
    
    .card-body {
        padding: 2rem 1rem;
    }

    .card-icon {
        font-size: 3.5rem; /* Ícones maiores para destaque */
    }
    
    .dashboard-heading {
        color: <?= htmlspecialchars($cor_primaria) ?>;
        font-weight: 500;
        margin-bottom: 2rem;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Módulo Financeiro</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $caminho_base_clinica . 'dashboard.php' ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Financeiro</li>
            </ol>
        </nav>
    </div>

    <div class="row mt-3 justify-content-center">
        <?php foreach ($cards as $nome => $info): ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 d-flex justify-content-center">
                <a href="<?= htmlspecialchars($caminho_base_clinica . $info['link']) ?>" class="text-decoration-none w-100" style="max-width: 220px;">
                    <div class="card shadow-sm h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <div style="font-size: 3rem;"><?= htmlspecialchars($info['icon']) ?></div>
                            <h5 class="card-title mt-3"><?= htmlspecialchars($nome) ?></h5>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/rodape.php'; ?>