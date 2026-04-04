<?php
// Inclui o cabeçalho master
require_once __DIR__ . '/includes/master_cabecalho.php';
// MÓDULO DE CLONAGEM MÍNIMA PARA AMBIENTES DE PRODUÇÃO
// Este script apenas clona a pasta de uma clínica-base para uma nova pasta de clínica.
// Ele não lida com bancos de dados.

// Inicia a sessão para verificar o nível de acesso
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário é master (pode ser ajustado conforme sua lógica de login)
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'master') {
    // Redireciona para a página de login se não for master
    header("Location: " . "/gestorvital/login.php");
    exit;
}

// --- Funções do Módulo ---

// Função para sanitizar nomes de pastas, removendo acentos e caracteres especiais
function sanitize_folder_name_cloner($string) {
    $string = mb_convert_encoding($string, 'UTF-8', 'auto');
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = strtolower($string);
    $string = str_replace(' ', '_', $string); // Substitui espaços por underscores
    $string = preg_replace('/[^a-z0-9-_]/', '', $string); // Permite letras, números, hifens e underscores
    $string = preg_replace('/-+/', '-', $string);
    $string = preg_replace('/_+/', '_', $string);
    return trim($string, '-_');
}

// Função para copiar uma pasta e seu conteúdo recursivamente
function recurse_copy_cloner($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy_cloner($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// --- Lógica Principal do Script ---
$mensagem_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_nova_clinica = $_POST['nome_nova_clinica'] ?? '';
    
    // Validação inicial
    if (empty($nome_nova_clinica)) {
        $mensagem_status = '<div class="alert alert-danger">O nome da nova clínica é obrigatório.</div>';
    } else {
        // Sanitiza o nome para a pasta
        $nome_sanitizado = sanitize_folder_name_cloner($nome_nova_clinica);
        $nome_pasta = $nome_sanitizado;
        
        // CORREÇÃO CRÍTICA: Ajusta o caminho de destino para a raiz do domínio
        $pasta_destino = $_SERVER['DOCUMENT_ROOT'] . '/' . $nome_pasta;
        
        // CORREÇÃO CRÍTICA: Ajusta o caminho da pasta de origem para 'clinica_medica'
        $pasta_origem = $_SERVER['DOCUMENT_ROOT'] . '/clinica_medica'; 

        // Validação: verifica se a pasta já existe
        if (is_dir($pasta_destino)) {
            $mensagem_status = '<div class="alert alert-danger">A pasta da clínica ' . $nome_pasta . ' já existe.</div>';
        } else {
            try {
                // Clonar a pasta e seu conteúdo
                if (!is_dir($pasta_origem)) {
                    throw new Exception("Pasta de origem '$pasta_origem' não encontrada.");
                }
                
                recurse_copy_cloner($pasta_origem, $pasta_destino);
                
                $mensagem_status = '<div class="alert alert-success">Pasta da clínica clonada com sucesso! Você agora pode criar o banco de dados e as tabelas manualmente.</div>';
            } catch (Exception $e) {
                $mensagem_status = '<div class="alert alert-danger">Erro durante a clonagem: ' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Clonar Clínica - GestorVital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h3 class="text-center mb-4">Clonar Nova Clínica</h3>
        <p class="text-center lead">Preencha o nome da nova clínica para iniciar a clonagem da pasta.</p>
        <div class="alert alert-info">
            Este script **apenas clona a pasta**. Você precisará criar o banco de dados e as tabelas manualmente.
        </div>
        
        <?= $mensagem_status ?>
        
        <form action="clonar_tudo.php" method="POST">
            <div class="mb-3">
                <label for="nome_nova_clinica" class="form-label">Nome da Nova Clínica</label>
                <input type="text" class="form-control" id="nome_nova_clinica" name="nome_nova_clinica" required>
                <small class="form-text text-muted">Ex: Clínica Odontológica. Use a grafia correta, com acentos.</small>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-copy"></i> Clonar Pasta</button>
        </form>
    </div>
</body>
</html>