<?php
// Inclui o cabeçalho master
require_once __DIR__ . '/includes/master_cabecalho.php';

// Redireciona para o login se o usuário não estiver autenticado
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'master') {
    header('Location: login_master.php');
    exit;
}

// O caminho base para a raiz do projeto
$caminho_base_do_projeto = '/gestorvital/';

?>

<div class="container py-5">
    <div class="row">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-3">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']); ?>!</h1>
            <p class="lead text-muted mb-5">Selecione uma opção para gerenciar o sistema.</p>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow border-0 text-center">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <i class="fas fa-palette fa-3x text-primary mb-3"></i>
                    <h5 class="card-title fw-bold">Gerenciar Identidades</h5>
                    <p class="card-text text-muted">Altere logos, cores e dados de contato das clínicas.</p>
                    <a href="gerenciar_identidade.php" class="btn btn-dark mt-auto w-100">Acessar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow border-0 text-center">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <i class="fas fa-clone fa-3x text-success mb-3"></i>
                    <h5 class="card-title fw-bold">Clonar Clínicas</h5>
                    <p class="card-text text-muted">Crie novas clínicas a partir de um modelo existente.</p>
                    <a href="clonar_tudo.php" class="btn btn-dark mt-auto w-100">Acessar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow border-0 text-center">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <i class="fas fa-sync-alt fa-3x text-info mb-3"></i>
                    <h5 class="card-title fw-bold">Atualizar Caminhos</h5>
                    <p class="card-text text-muted">Atualize os caminhos de arquivos das clínicas.</p>
                    <a href="atualizar_caminhos.php" class="btn btn-dark mt-auto w-100">Acessar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/master_rodape.php'; ?>