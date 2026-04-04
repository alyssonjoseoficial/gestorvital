<?php
// Certifica-se de que a sessão foi iniciada antes de usar as variáveis de sessão.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// O caminho base do seu projeto na URL do navegador
$caminho_base_do_projeto = '/gestorvital/';

if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'master') {
    header("Location: " . $caminho_base_do_projeto . "login.php");
    exit;
}

// Título do cabeçalho da área master
$titulo = "Painel Master";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .navbar-master {
            background-color: #212529 !important; /* Cor de fundo escura */
        }
        .navbar-master .navbar-brand,
        .navbar-master .nav-link,
        .navbar-master .btn {
            color: #ffffff !important; /* Cor da fonte branca */
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-master">
    <div class="container-fluid">
        <a class="navbar-brand" href="../administrador/dashboard.php">
        <i class="fas fa-tools me-2"></i><?= $titulo ?>
        </a>
        <div>
            <span class="me-3 text-white"><?= htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
            <a href="logout_master.php" class="btn btn-sm btn-outline-light">Sair</a>
         </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 px-md-4">