<?php
// MÓDULO DE EXCLUSÃO DE CLÍNICA
// Exclui a pasta, o banco de dados e o registro da clínica.

// Inclui a configuração do banco de dados central
require_once __DIR__ . '/../config/config_central.php';

// Inicia a sessão para verificar o nível de acesso
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário é master
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'master') {
    header("Location: " . "/gestorvital/login.php");
    exit;
}

// Função para apagar uma pasta e seu conteúdo recursivamente
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

// Lógica de processamento
$mensagem_status = '';
$conn_central = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CENTRAL);

if ($conn_central->connect_error) {
    $mensagem_status = '<div class="alert alert-danger">Erro na conexão com o sistema central. Tente mais tarde.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_clinica = trim($_POST['id_clinica'] ?? '');
    $confirmacao_texto = trim($_POST['confirmacao_texto'] ?? '');
    $frase_confirmacao_necessaria = "EXCLUIR PERMANENTEMENTE";
    $sucesso_total = false;

    if (empty($id_clinica)) {
        $mensagem_status = '<div class="alert alert-danger">Selecione uma clínica para excluir.</div>';
    } elseif ($confirmacao_texto !== $frase_confirmacao_necessaria) {
        $mensagem_status = '<div class="alert alert-danger">O texto de confirmação está incorreto. Digite "' . $frase_confirmacao_necessaria . '".</div>';
    } elseif ($id_clinica === 'clinica_medica') {
        $mensagem_status = '<div class="alert alert-danger">A clínica base não pode ser excluída.</div>';
    } else {
        $stmt_select = $conn_central->prepare("SELECT db_name, codigo_clinica FROM clinicas WHERE codigo_clinica = ?");
        $stmt_select->bind_param("s", $id_clinica);
        $stmt_select->execute();
        $resultado = $stmt_select->get_result();
        $clinica_para_excluir = $resultado->fetch_assoc();
        $stmt_select->close();

        if (!$clinica_para_excluir) {
            $mensagem_status = '<div class="alert alert-danger">Clínica não encontrada.</div>';
        } else {
            $db_name = $clinica_para_excluir['db_name'];
            $codigo_clinica = $clinica_para_excluir['codigo_clinica'];
            $pasta_destino = $_SERVER['DOCUMENT_ROOT'] . '/gestorvital/' . $codigo_clinica;
            
            // 1. Excluir o banco de dados
            $sql_drop_db = "DROP DATABASE IF EXISTS `" . $conn_central->real_escape_string($db_name) . "`";
            $sucesso_db = $conn_central->query($sql_drop_db);

            // 2. Excluir a pasta
            $sucesso_pasta = deleteDirectory($pasta_destino);
            
            // 3. Excluir a entrada na tabela central
            $stmt_delete_central = $conn_central->prepare("DELETE FROM clinicas WHERE codigo_clinica = ?");
            $stmt_delete_central->bind_param("s", $id_clinica);
            $sucesso_registro = $stmt_delete_central->execute();
            $stmt_delete_central->close();

            if ($sucesso_db && $sucesso_pasta && $sucesso_registro) {
                // Se tudo deu certo, redireciona o usuário
                $conn_central->close();
                header("Location: gerenciar_identidade.php?status=success_delete");
                exit;
            } else {
                if (!$sucesso_db) {
                    $mensagem_status .= '<div class="alert alert-danger">Erro ao excluir o banco de dados.</div>';
                }
                if (!$sucesso_pasta) {
                    $mensagem_status .= '<div class="alert alert-danger">Erro ao excluir a pasta.</div>';
                }
                if (!$sucesso_registro) {
                    $mensagem_status .= '<div class="alert alert-danger">Erro ao excluir o registro central.</div>';
                }
            }
        }
    }
}
// Inclui o cabeçalho master
require_once __DIR__ . '/includes/master_cabecalho.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Excluir Clínica - GestorVital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h3 class="text-center mb-4">Excluir Clínica Permanentemente</h3>
        <p class="text-center lead text-danger">ATENÇÃO: Esta ação é irreversível e apagará todos os dados!</p>
        
        <?= $mensagem_status ?>
        
        <form action="excluir_tudo.php" method="POST" onsubmit="return confirm('Tem certeza que deseja EXCLUIR esta clínica permanentemente? Esta ação não pode ser desfeita!');">
            <div class="mb-3">
                <label for="id_clinica" class="form-label">Selecione a Clínica para Excluir</label>
                <select class="form-select" id="id_clinica" name="id_clinica" required>
                    <option value="">Selecione...</option>
                    <?php
                    $sql = "SELECT codigo_clinica, nome_clinica FROM clinicas ORDER BY nome_clinica";
                    $resultado_clinicas = $conn_central->query($sql);
                    if ($resultado_clinicas->num_rows > 0) {
                        while ($clinica = $resultado_clinicas->fetch_assoc()) {
                            if ($clinica['codigo_clinica'] !== 'clinica_medica') {
                                echo '<option value="' . htmlspecialchars($clinica['codigo_clinica']) . '">' . htmlspecialchars($clinica['nome_clinica']) . '</option>';
                            }
                        }
                    }
                    $conn_central->close();
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="confirmacao_texto" class="form-label">Confirme a exclusão digitando "EXCLUIR PERMANENTEMENTE" no campo abaixo:</label>
                <input type="text" class="form-control" id="confirmacao_texto" name="confirmacao_texto" required>
            </div>
            
            <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Excluir Clínica</button>
        </form>
    </div>
</body>
</html>