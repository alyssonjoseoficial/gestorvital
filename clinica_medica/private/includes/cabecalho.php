<?php
// O caminho para o config.php da clínica está correto.
require_once __DIR__ . '/../../config/config.php';
// O caminho para o helpers.php, na pasta config na raiz do projeto, foi ajustado.
require_once __DIR__ . '/../../../config/helpers.php';

// O caminho base do seu projeto na URL do navegador
$caminho_base_do_projeto = '/';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . $caminho_base_do_projeto . "login.php");
    exit;
}

// Conecta ao banco de dados e busca a identidade da clínica (incluindo a cor)
$nome_clinica = "Clínica Médica";
$url_logo = "logo_padrao.png"; // Valor padrão para evitar erros
$cor_primaria = "#007bff"; // Cor padrão do Bootstrap

try {
    $sql = "SELECT nome_clinica, url_logo, cor_primaria FROM identidade_clinica ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $identidade = $result->fetch_assoc();
        $nome_clinica = htmlspecialchars($identidade['nome_clinica']);
        if (!empty($identidade['url_logo'])) {
            $url_logo = htmlspecialchars($identidade['url_logo']);
        }
        if (!empty($identidade['cor_primaria'])) {
            $cor_primaria = htmlspecialchars($identidade['cor_primaria']);
        }
    }
} catch (Exception $e) {
    error_log("Erro ao buscar identidade da clínica: " . $e->getMessage());
}

// O nome da pasta foi definido manualmente para corresponder ao nome exato no servidor
$nome_pasta_clinica = 'clinica_medica';

$caminho_base_clinica_publica = $caminho_base_do_projeto . $nome_pasta_clinica . '/public/';
$caminho_base_clinica_private = $caminho_base_do_projeto . $nome_pasta_clinica . '/private/';
$caminho_base_clinica_uploads = $caminho_base_do_projeto . $nome_pasta_clinica . '/';

// Determina a cor de contraste para a fonte do cabeçalho
$cor_contraste = get_contrast_color($cor_primaria);

// Função para converter a cor HEX para RGB (necessário para a transparência do gradiente)
// Mantida, caso queira usar para outros gradientes ou opacidades.
function hexToRgb($hex, $alpha = false) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $rgb = "$r, $g, $b";
    if ($alpha) {
        $rgb .= ", $alpha";
    }
    return $rgb;
}

// Vamos usar a cor primária diretamente no gradiente
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - <?= $nome_clinica ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $caminho_base_clinica_publica . 'assets/css/style.css' ?>">
   <style>
    /* Define a variável CSS primária dinamicamente */
    :root {
        --primary-color: <?= $cor_primaria ?>;
    }

    /* Estilos do Navbar */
    .navbar-custom {
        /* AQUI: Gradiente que transiciona em uma área muito pequena */
        background: linear-gradient(to right, #5ccb5f 5%, <?= $cor_primaria ?> 9%, <?= $cor_primaria ?> 100%) !important;
        min-height: 60px;
        display: flex;
        align-items: center;
    }
    .navbar-custom .navbar-brand,
    .navbar-custom .nav-link,
    .navbar-custom .btn {
        color: <?= $cor_contraste ?> !important;
    }
    
    .navbar-custom .btn-custom-logout {
        background-color: <?= $cor_contraste ?> !important;
        color: var(--primary-color) !important;
        border-color: <?= $cor_contraste ?> !important;
    }
    .navbar-custom .btn-custom-logout:hover {
        background-color: transparent !important;
        color: <?= $cor_contraste ?> !important;
    }

    /* Estilos da Logo */
    .navbar-brand {
        height: 100%;
        display: flex;
        align-items: center;
        padding-top: 0;
        padding-bottom: 0;
    }
    .logo-img {
        height: 98%;
        max-height: 58px;
        width: auto;
        object-fit: contain;
        margin-right: 15px;
    }

    /* Estilos dos Títulos das Páginas */
    .container h1, .container h2 {
        color: var(--primary-color) !important;
    }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard.php">
            <img src="<?= $caminho_base_clinica_uploads . $url_logo ?>" alt="Logo da <?= $nome_clinica ?>" class="logo-img">
            <span class="ms-2"><?= $nome_clinica ?></span>
        </a>
        <div>
            <span class="me-3" style="color: <?= $cor_contraste ?>;"><?= htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
            <a href="<?= $caminho_base_do_projeto ?>logout.php" class="btn btn-sm btn-custom-logout">Sair</a>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">