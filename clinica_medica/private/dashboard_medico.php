<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/config.php";
include_once "includes/cabecalho.php";
include_once "includes/menu.php";

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$id_clinica = $_SESSION['id_clinica'] ?? '';

// Corrigido: Definindo o caminho base da clínica para gerar os links
$caminho_base_do_projeto = '/'; // A pasta raiz do domínio
$caminho_base_clinica = $caminho_base_do_projeto . $id_clinica . '/private/';

if ($nivel !== 'medico') {
    die("Acesso negado.");
}

$cards = [
    'Agenda' => ['link' => 'medicos/agenda_do_dia.php', 'icon' => '📅'],
    'Exames' => ['link' => 'exames/listar_exames.php', 'icon' => '🔬'],
    'Alterar Senha' => ['link' => 'usuarios/alterar_senha.php', 'icon' => '🔒'],
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
    <h3 class="text-center">Bem-vindo, Dr(a). <?= htmlspecialchars($usuario_nome) ?></h3>
    <div class="row mt-3 justify-content-center">
        <?php foreach ($cards as $nome => $info): ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 d-flex justify-content-center">
                <a href="<?= htmlspecialchars($caminho_base_clinica . $info['link']) ?>" class="text-decoration-none w-100" style="max-width: 220px;">
                    <div class="card shadow-sm h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <div style="font-size: 3rem;"><?= $info['icon'] ?></div>
                            <h5 class="card-title mt-3"><?= $nome ?></h5>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once "includes/rodape.php"; ?>