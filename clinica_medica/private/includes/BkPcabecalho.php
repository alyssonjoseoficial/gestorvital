<?php
require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL_PUBLIC . "login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Clínica Médica</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2xreKJRzR3jT7t73zM7O9gL8xKzL4s4g4wA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <span class="navbar-brand">Clínica Médica</span>
        <div>
            <span class="text-white me-3"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
            <a href="<?= BASE_URL_PUBLIC ?>logout.php" class="btn btn-light btn-sm">Sair</a>

        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">