<?php
// Inclui o cabeçalho master
require_once __DIR__ . '/includes/master_cabecalho.php';
// MÓDULO DE ATUALIZAÇÃO DE CAMINHOS
// Este script percorre todos os arquivos de uma clínica clonada e atualiza os caminhos internos.

// Inicia a sessão para verificar o nível de acesso
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário é master
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'master') {
    header("Location: " . "/gestorvital/login.php");
    exit;
}

// --- Funções do Módulo ---

// Função para sanitizar nomes de pastas, removendo acentos e caracteres especiais
function sanitize_folder_name_cloner($string) {
    $string = mb_convert_encoding($string, 'UTF-8', 'auto');
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = strtolower($string);
    $string = str_replace(' ', '_', $string);
    $string = preg_replace('/[^a-z0-9-_]/', '', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = preg_replace('/_+/', '_', $string);
    return trim($string, '-_');
}

// Função para percorrer recursivamente e atualizar os caminhos dos arquivos
function update_file_paths_recursively($dir, $old_name, $new_name) {
    $count = 0;
    $files_updated = [];
    
    // Abrir o diretório
    if ($handle = opendir($dir)) {
        // Ler todos os arquivos e subdiretórios
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    // Se for um diretório, chama a função recursivamente
                    $result = update_file_paths_recursively($path, $old_name, $new_name);
                    $count += $result['count'];
                    $files_updated = array_merge($files_updated, $result['files']);
                } else if (is_file($path) && (mime_content_type($path) === 'text/html' || strpos(mime_content_type($path), 'text/') === 0 || strpos($file, '.php') !== false || strpos($file, '.js') !== false || strpos($file, '.css') !== false)) {
                    // Se for um arquivo de texto, HTML, PHP, JS ou CSS
                    $content = file_get_contents($path);
                    $new_content = str_replace('/' . $old_name . '/', '/' . $new_name . '/', $content);
                    
                    if ($new_content !== $content) {
                        file_put_contents($path, $new_content);
                        $count++;
                        $files_updated[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                    }
                }
            }
        }
        closedir($handle);
    }
    
    return ['count' => $count, 'files' => $files_updated];
}

// --- Lógica Principal do Script ---
$mensagem_status = '';
$pasta_origem_modelo = 'clinica_medica';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_nova_clinica = $_POST['nome_nova_clinica'] ?? '';
    
    if (empty($nome_nova_clinica)) {
        $mensagem_status = '<div class="alert alert-danger">O nome da nova clínica é obrigatório.</div>';
    } else {
        $nome_nova_clinica_sanitizado = sanitize_folder_name_cloner($nome_nova_clinica);
        $pasta_destino_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $nome_nova_clinica_sanitizado;

        if (!is_dir($pasta_destino_path)) {
            $mensagem_status = '<div class="alert alert-danger">A pasta da clínica **' . $nome_nova_clinica_sanitizado . '** não existe. Execute o `clonar_tudo.php` primeiro.</div>';
        } else {
            try {
                $result = update_file_paths_recursively($pasta_destino_path, $pasta_origem_modelo, $nome_nova_clinica_sanitizado);
                $count = $result['count'];
                $files_updated = $result['files'];

                if ($count > 0) {
                    $mensagem_status = '<div class="alert alert-success">Sucesso! ' . $count . ' arquivos foram atualizados.</div>';
                    $mensagem_status .= '<div class="mt-3"><small class="text-muted">Arquivos atualizados:</small><ul>';
                    foreach ($files_updated as $file) {
                        $mensagem_status .= '<li>' . htmlspecialchars($file) . '</li>';
                    }
                    $mensagem_status .= '</ul></div>';
                } else {
                    $mensagem_status = '<div class="alert alert-warning">Nenhum arquivo encontrado para ser atualizado na pasta `' . $nome_nova_clinica_sanitizado . '`.</div>';
                }
            } catch (Exception $e) {
                $mensagem_status = '<div class="alert alert-danger">Erro durante a atualização dos caminhos: ' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atualizar Caminhos - GestorVital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h3 class="text-center mb-4">Atualizar Caminhos de Clínica</h3>
        <p class="text-center lead">Este script corrigirá os links internos da nova clínica clonada.</p>
        <div class="alert alert-info">
            **Importante:** Use este script **após** a clonagem da pasta com o `clonar_tudo.php`.
        </div>
        
        <?= $mensagem_status ?>
        
        <form action="atualizar_caminhos.php" method="POST">
            <div class="mb-3">
                <label for="nome_nova_clinica" class="form-label">Nome da Nova Clínica (pasta)</label>
                <input type="text" class="form-control" id="nome_nova_clinica" name="nome_nova_clinica" required>
                <small class="form-text text-muted">Ex: odontoclin. O mesmo nome que você usou para clonar a pasta.</small>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-magic"></i> Atualizar Caminhos</button>
        </form>
    </div>
</body>
</html>