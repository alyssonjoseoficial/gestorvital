<?php
// O caminho de inclusão para o config.php foi corrigido para a nova estrutura de pastas
require_once __DIR__ . '/../config/config.php';

// ATENÇÃO: session_start() DEVE SER A PRIMEIRA COISA a ser executada depois do 'require_once'
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REMOVIDO: O código de teste de conexão. Ele causava o erro "Acesso negado".

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$id_clinica = $_SESSION['id_clinica'] ?? '';

// CORRIGIDO: O caminho base do seu projeto na URL do navegador
$caminho_base_do_projeto = '/'; // A pasta raiz do domínio
$caminho_base_clinica = $caminho_base_do_projeto . $id_clinica . '/private/';

// ALTERADO: Redireciona o usuário 'master' para a página de gerenciamento na pasta raiz
if ($nivel === 'master') {
    header("Location: " . $caminho_base_do_projeto . "administrador/gerenciar_identidade.php");
    exit;
}

// ADICIONADO: Inclui o novo nível de acesso 'master' na verificação
if (!in_array($nivel, ['master', 'administrador', 'recepcao', 'medico', 'financeiro'])) {
    die("Acesso negado.");
}

if ($nivel === 'medico') {
    header("Location: dashboard_medico.php"); // ajuste se quiser
    exit;
}

// MODIFICADO: Definir itens de menu e cards para cada nível de acesso
$menu_items = [
    'Início' => $caminho_base_clinica . 'dashboard.php',
];
$cards = [];

// Lógica para o Administrador
if ($nivel === 'administrador') {
    $menu_items['Pacientes'] = $caminho_base_clinica . 'pacientes/listar.php';
    $menu_items['Médicos'] = $caminho_base_clinica . 'medicos/listar.php';
    $menu_items['Consultas'] = $caminho_base_clinica . 'consultas/listar.php';
    $menu_items['Convênios'] = $caminho_base_clinica . 'convenios/listar.php';
    $menu_items['Usuários'] = $caminho_base_clinica . 'usuarios/listar.php';
    $menu_items['Financeiro'] = $caminho_base_clinica . 'financeiro/dashboard_financeiro.php';
    $menu_items['Alterar Senha'] = $caminho_base_clinica . 'usuarios/alterar_senha.php';

    $cards['Pacientes'] = ['link' => $caminho_base_clinica . 'pacientes/listar.php', 'icon' => 'fa-solid fa-user-group'];
    $cards['Médicos'] = ['link' => $caminho_base_clinica . 'medicos/listar.php', 'icon' => 'fa-solid fa-stethoscope'];
    $cards['Consultas'] = ['link' => $caminho_base_clinica . 'consultas/listar.php', 'icon' => 'fa-solid fa-calendar-alt'];
    $cards['Exames'] = ['link' => $caminho_base_clinica . 'exames/listar_exames.php', 'icon' => 'fa-solid fa-microscope'];
    $cards['Convênios'] = ['link' => $caminho_base_clinica . 'convenios/listar.php', 'icon' => 'fa-solid fa-handshake'];
    $cards['Usuários'] = ['link' => $caminho_base_clinica . 'usuarios/listar.php', 'icon' => 'fa-solid fa-users-gear'];
    $cards['Financeiro'] = ['link' => $caminho_base_clinica . 'financeiro/dashboard_financeiro.php', 'icon' => 'fa-solid fa-chart-line'];
    $cards['Serviços'] = ['link' => $caminho_base_clinica . 'administrador/listar_servicos.php', 'icon' => 'fa-solid fa-clipboard-list'];
    $cards['Alterar Senha'] = ['link' => $caminho_base_clinica . 'usuarios/alterar_senha.php', 'icon' => 'fa-solid fa-lock'];

} elseif ($nivel === 'recepcao') {
    $menu_items['Pacientes'] = $caminho_base_clinica . 'pacientes/listar.php';
    $menu_items['Consultas'] = $caminho_base_clinica . 'consultas/listar.php';
    $cards['Exames'] = ['link' => $caminho_base_clinica . 'exames/listar_exames.php', 'icon' => 'fa-solid fa-microscope'];
    $menu_items['Convênios'] = $caminho_base_clinica . 'convenios/listar.php';
    $menu_items['Alterar Senha'] = $caminho_base_clinica . 'usuarios/alterar_senha.php';

    $cards['Pacientes'] = ['link' => $caminho_base_clinica . 'pacientes/listar.php', 'icon' => 'fa-solid fa-user-group'];
    $cards['Consultas'] = ['link' => $caminho_base_clinica . 'consultas/listar.php', 'icon' => 'fa-solid fa-calendar-alt'];
    $cards['Convênios'] = ['link' => $caminho_base_clinica . 'convenios/listar.php', 'icon' => 'fa-solid fa-handshake'];
    $cards['Alterar Senha'] = ['link' => $caminho_base_clinica . 'usuarios/alterar_senha.php', 'icon' => 'fa-solid fa-lock'];

} elseif ($nivel === 'financeiro') {
    $menu_items['Financeiro'] = $caminho_base_clinica . 'financeiro/dashboard_financeiro.php';
    $menu_items['Convênios'] = $caminho_base_clinica . 'convenios/listar.php';
    $menu_items['Alterar Senha'] = $caminho_base_clinica . 'usuarios/alterar_senha.php';

    $cards['Financeiro'] = ['link' => $caminho_base_clinica . 'financeiro/dashboard_financeiro.php', 'icon' => 'fa-solid fa-chart-line'];
    $cards['Convênios'] = ['link' => $caminho_base_clinica . 'convenios/listar.php', 'icon' => 'fa-solid fa-handshake'];
    $cards['Alterar Senha'] = ['link' => $caminho_base_clinica . 'usuarios/alterar_senha.php', 'icon' => 'fa-solid fa-lock'];
}
?>

<?php include_once __DIR__ . "/includes/cabecalho.php"; ?>
<?php include_once __DIR__ . "/includes/menu.php"; ?>

<style>
    body {
        background-color: #f0f2f5;
    }

    .card {
        border: none;
        border-radius: 1rem;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    
    .card-body {
        padding: 2rem 1rem;
    }

    .card-icon {
        font-size: 3.5rem;
    }
    
    .dashboard-heading {
        color: <?= htmlspecialchars($cor_primaria) ?>;
        font-weight: 500;
        margin-bottom: 2rem;
    }
</style>

<div class="container mt-4">
    <h3 class="text-center dashboard-heading">Bem-vindo ao Sistema da Clínica Médica</h3>
    <div class="row mt-3 justify-content-center">
        <?php foreach ($cards as $nome => $info): ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 d-flex justify-content-center">
                <a href="<?= htmlspecialchars($info['link']) ?>" class="text-decoration-none w-100" style="max-width: 220px;">
                    <div class="card h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <div class="card-icon" style="color: <?= htmlspecialchars($cor_primaria) ?>;">
                                <i class="<?= htmlspecialchars($info['icon']) ?>"></i>
                            </div>
                            <h5 class="card-title mt-3"><?= htmlspecialchars($nome) ?></h5>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include_once __DIR__ . "/includes/rodape.php"; ?>