<?php
// Certifica-se de que a sessão já foi iniciada na página principal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// O caminho base do seu projeto na URL do navegador
$caminho_base_do_projeto = '/';

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
// AQUI: O nome da pasta da clínica é obtido dinamicamente da sessão
$id_clinica = $_SESSION['id_clinica'] ?? 'clinica_medica';

$is_admin = $nivel_acesso === 'administrador';
$is_recepcao = $nivel_acesso === 'recepcao';
$is_medico = $nivel_acesso === 'medico';
$is_financeiro = $nivel_acesso === 'financeiro';

$menu_items = [];

// Define os itens de menu usando o caminho base
$caminho_base_clinica = $caminho_base_do_projeto . $id_clinica . '/private/';

if ($is_admin) {
    $menu_items = [
        'Início' => $caminho_base_clinica . 'dashboard.php',
        'Pacientes' => $caminho_base_clinica . 'pacientes/listar.php',
        'Médicos' => $caminho_base_clinica . 'medicos/listar.php',
        'Consultas' => $caminho_base_clinica . 'consultas/listar.php',
        'Exames' => $caminho_base_clinica . 'exames/listar_exames.php',
        'Convênios' => $caminho_base_clinica . 'convenios/listar.php',
        'Usuários' => $caminho_base_clinica . 'usuarios/listar.php',
        'Financeiro' => $caminho_base_clinica . 'financeiro/dashboard_financeiro.php',
        'Serviços' => $caminho_base_clinica . 'administrador/listar_servicos.php',
        'Senha' => $caminho_base_clinica . 'usuarios/alterar_senha.php',
    ];
} elseif ($is_recepcao) {
    $menu_items = [
        'Início' => $caminho_base_clinica . 'dashboard.php',
        'Pacientes' => $caminho_base_clinica . 'pacientes/listar.php',
        'Consultas' => $caminho_base_clinica . 'consultas/listar.php',
        'Exames' => $caminho_base_clinica . 'exames/listar_exames.php',
        'Convênios' => $caminho_base_clinica . 'convenios/listar.php',
        'Alterar Senha' => $caminho_base_clinica . 'usuarios/alterar_senha.php',
    ];
} elseif ($is_medico) {
    $menu_items = [
        'Início' => $caminho_base_clinica . 'dashboard_medico.php',
        'Minha Agenda' => $caminho_base_clinica . 'medicos/agenda_do_dia.php',
        'Exames' => $caminho_base_clinica . 'exames/listar_exames.php',
        'Alterar Senha' => $caminho_base_clinica . 'usuarios/alterar_senha.php',
    ];
} elseif ($is_financeiro) {
    $menu_items = [
        'Início' => $caminho_base_clinica . 'dashboard.php',
        'Financeiro' => $caminho_base_clinica . 'financeiro/dashboard_financeiro.php',
        'Balancete' => $caminho_base_clinica . 'financeiro/relatorio_balancete.php',
        'Convênios' => $caminho_base_clinica . 'convenios/listar.php',
        'Alterar Senha' => $caminho_base_clinica . 'usuarios/alterar_senha.php',
    ];
}

$current_path = strtok($_SERVER['REQUEST_URI'], '?');
?>

<nav class="navbar navbar-expand-lg border-bottom justify-content-center menu-custom">
    <div class="container justify-content-center">
        <ul class="navbar-nav mb-2 mb-lg-0 justify-content-center">
            <?php foreach ($menu_items as $nome => $url): ?>
                <?php
                $is_active = (strpos($current_path, $url) !== false);
                ?>
                <li class="nav-item mx-3">
                    <a class="nav-link <?= $is_active ? 'active' : '' ?>" href="<?= htmlspecialchars($url) ?>">
                        <?= htmlspecialchars($nome) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<style>
    /* Estilo para o menu de navegação, corrigindo o problema de "salto" */
    .menu-custom .nav-link {
        color: #212529; /* Cor padrão para links do menu */
        border-bottom: 2px solid transparent; /* Adiciona a borda invisível para reservar o espaço */
        transition: color 0.3s ease, border-bottom 0.3s ease; /* Adiciona transição suave */
    }

    /* Estilo para o item de menu ativo ou com o mouse por cima */
    .menu-custom .nav-link.active,
    .menu-custom .nav-link:hover {
        color: <?= $cor_primaria ?>;
        border-bottom: 2px solid <?= $cor_primaria ?>; /* A borda colorida aparece sem mover a página */
        
        /* SOLUÇÃO FINAL: Simula o negrito sem alterar a largura do texto */
        text-shadow: 0.5px 0 0.5px currentColor;
    }
</style>