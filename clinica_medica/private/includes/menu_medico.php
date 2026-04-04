<?php
// Define os itens do menu para o módulo médico com caminhos absolutos
$menu_items = [
    'Início' => 'dashboard_medico.php',
    'Agenda' => 'medicos/agenda_do_dia.php',
    'Prontuários' => 'prontuarios/listar.php',
    'Alterar Senha' => 'usuarios/alterar_senha.php',
];
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom justify-content-center">
    <div class="container justify-content-center">
        <ul class="navbar-nav mb-2 mb-lg-0 justify-content-center">
            <?php foreach ($menu_items as $nome => $url): ?>
                <li class="nav-item mx-3">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], $url) !== false ? 'active fw-bold' : '' ?>" href="<?= BASE_URL_PRIVATE . $url ?>">
                        <?= $nome ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>