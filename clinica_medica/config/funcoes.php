<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuario_logado() {
    return isset($_SESSION['usuario_id']);
}

function nivel_acesso() {
    return $_SESSION['nivel_acesso'] ?? null;
}

// Verifica se o usuário está logado, caso contrário redireciona para login
function protege_pagina() {
    if (!usuario_logado()) {
        header("Location: ../public/login.php");
        exit;
    }
}

// Verifica se usuário tem permissão para acessar o módulo
// $niveis_permitidos: array de níveis que podem acessar, ex: ['admin', 'recepcao']
function verifica_permissao(array $niveis_permitidos) {
    protege_pagina();
    if (!in_array(nivel_acesso(), $niveis_permitidos)) {
        echo "<div class='alert alert-danger m-4'>Acesso negado. Você não tem permissão para acessar esta página.</div>";
        exit;
    }
}
