<?php
// Inclui os arquivos de configuração necessários
require_once __DIR__ . '/../config/config_central.php';
// Inclui o arquivo de funções auxiliares (está na mesma pasta)
require_once __DIR__ . '/../config/helpers.php';

session_start();

// Verifica se o usuário está logado e se tem permissão de 'master'
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'master') {
    header("Location: " . "/gestorvital/login.php");
    exit;
}

// Verifica se a requisição é do tipo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: gerenciar_identidade.php");
    exit;
}

// 1. Obtém o ID da clínica do formulário
$clinica_id = $_POST['id_clinica'] ?? 0;
if ($clinica_id == 0) {
    header("Location: gerenciar_identidade.php?status=error&message=ID da clínica não especificado.");
    exit;
}

// 2. Conecta ao banco de dados central para obter os dados da clínica
$conn_central = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CENTRAL);
if ($conn_central->connect_error) {
    header("Location: gerenciar_identidade.php?status=error&message=Falha na conexão central.");
    exit;
}

// Busca as credenciais de conexão da clínica específica
$clinica_info = null;
$stmt_central = $conn_central->prepare("SELECT * FROM clinicas WHERE id = ?");
$stmt_central->bind_param("i", $clinica_id);
$stmt_central->execute();
$result_central = $stmt_central->get_result();
$clinica_info = $result_central->fetch_assoc();
$stmt_central->close();
$conn_central->close();

if (!$clinica_info) {
    header("Location: gerenciar_identidade.php?status=error&message=Informações da clínica não encontradas.");
    exit;
}

// 3. Conecta ao banco de dados específico da clínica
$conn_clinica = new mysqli($clinica_info['db_host'], $clinica_info['db_user'], $clinica_info['db_pass'], $clinica_info['db_name']);
if ($conn_clinica->connect_error) {
    header("Location: gerenciar_identidade.php?status=error&message=Falha na conexão com a clínica.");
    exit;
}

// 4. Inicia o processo de atualização
$nome_clinica_form = $_POST['nome_clinica'];
$cor_primaria = $_POST['cor_primaria'];
$endereco = $_POST['endereco'];
$telefone = $_POST['telefone'];
$email = $_POST['email'];

$url_logo_para_bd = null;
$logo_atualizada = false;

// Tenta atualizar a logo apenas se um arquivo foi enviado e não houve erro
if (isset($_FILES['url_logo']) && $_FILES['url_logo']['error'] === UPLOAD_ERR_OK) {
    // CORRIGIDO: O nome da pasta agora é definido manualmente para corresponder ao nome exato no servidor
    // Isso evita qualquer erro de sanitização
    $nome_pasta_clinica = 'clinica_medica';

    // Constrói o caminho de destino usando o caminho absoluto do servidor
    $pasta_uploads = $_SERVER['DOCUMENT_ROOT'] . '/' . $nome_pasta_clinica . '/uploads/';
    
    // --- LINHAS DE DIAGNÓSTICO ---
    if (!is_dir($pasta_uploads)) {
        die("Erro: A pasta de uploads não foi encontrada. O caminho procurado foi: " . $pasta_uploads);
    }
    if (!is_writable($pasta_uploads)) {
        die("Erro: A pasta de uploads não tem permissão de escrita. Por favor, ajuste as permissões.");
    }
    // --- FIM DAS LINHAS DE DIAGNÓSTICO ---

    $extensao = pathinfo($_FILES['url_logo']['name'], PATHINFO_EXTENSION);
    $nome_arquivo_novo = uniqid() . '-' . time() . '.' . $extensao;
    $caminho_completo = $pasta_uploads . $nome_arquivo_novo;
    
    if (move_uploaded_file($_FILES['url_logo']['tmp_name'], $caminho_completo)) {
        $url_logo_para_bd = "uploads/" . $nome_arquivo_novo;
        $logo_atualizada = true;
    } else {
        die("Erro: Falha ao mover o arquivo. Verifique o caminho ou as permissões.");
    }
}

// 5. Prepara a consulta de atualização, incluindo a logo apenas se ela foi atualizada
$sql_update = "UPDATE identidade_clinica SET nome_clinica = ?, cor_primaria = ?, endereco = ?, telefone = ?, email = ?";
$params = "sssss";
$param_values = [$nome_clinica_form, $cor_primaria, $endereco, $telefone, $email];

if ($logo_atualizada) {
    $sql_update .= ", url_logo = ?";
    $params .= "s";
    $param_values[] = $url_logo_para_bd;
}

$sql_update .= " WHERE id = 1";

$stmt_update = $conn_clinica->prepare($sql_update);

if (!$stmt_update) {
    die("Erro na preparação da query de atualização.");
}

$stmt_update->bind_param($params, ...$param_values);

if ($stmt_update->execute()) {
    $status = "success";
    $message = "Dados da clínica atualizados com sucesso!";
} else {
    $status = "error";
    $message = "Erro ao atualizar os dados: " . $stmt_update->error;
}

$stmt_update->close();
$conn_clinica->close();

// 6. Redireciona com a mensagem de status
header("Location: gerenciar_identidade.php?status=" . urlencode($status) . "&message=" . urlencode($message));
exit;
?>