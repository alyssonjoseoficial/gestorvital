<?php
// Inclui os arquivos de configuração necessários
require_once __DIR__ . '/../config/config_central.php';
require_once __DIR__ . '/../clinica_medica/private/includes/cabecalho.php';
require_once __DIR__ . '/../clinica_medica/private/includes/menu.php';
require_once __DIR__ . '/../config/helpers.php';

// Verifica se o usuário é master
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'master') {
    header("Location: " . "/gestorvital/login.php");
    exit;
}

$mensagem_status = '';

// Processa a requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_nova_clinica = $_POST['nome_nova_clinica'] ?? '';
    
    if (empty($nome_nova_clinica)) {
        $mensagem_status = '<div class="alert alert-danger">O nome da nova clínica não pode ser vazio.</div>';
    } else {
        // 1. Sanitiza o nome para o banco de dados e a pasta
        $nome_sanitizado = sanitize_folder_name($nome_nova_clinica);
        $nome_db = "gestor_" . $nome_sanitizado;
        $nome_pasta = $nome_sanitizado;
        $pasta_destino = $_SERVER['DOCUMENT_ROOT'] . '/gestorvital/' . $nome_pasta;
        $pasta_origem = $_SERVER['DOCUMENT_ROOT'] . '/gestorvital/clinica_medica';
        
        // 2. Validação adicional: verifica se a pasta ou o banco de dados já existem
        $conn = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL);
        $result_db = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$nome_db'");
        if ($result_db && $result_db->num_rows > 0) {
            $mensagem_status = '<div class="alert alert-danger">O banco de dados ' . $nome_db . ' já existe.</div>';
        } elseif (is_dir($pasta_destino)) {
            $mensagem_status = '<div class="alert alert-danger">A pasta da clínica ' . $nome_pasta . ' já existe.</div>';
        } else {
            // Tudo validado, inicia o processo de clonagem
            $conn->close();

            // Define o caminho do binário do MySQL
            $mysql_bin_path = 'C:/xampp/mysql/bin/';
            $mysqldump_path = $mysql_bin_path . 'mysqldump';
            $mysql_path = $mysql_bin_path . 'mysql';

            // Monta as credenciais com ou sem a senha
            $pass_param = empty(DB_PASS_CENTRAL) ? '' : sprintf(' --password="%s"', DB_PASS_CENTRAL);
            $user_param = sprintf('--user="%s"', DB_USER_CENTRAL);

            // 3. Clonar o banco de dados
            $cmd_export = sprintf('"%s" %s %s %s > %s/%s.sql',
                $mysqldump_path, $user_param, $pass_param, DB_NAME_CENTRAL, sys_get_temp_dir(), DB_NAME_CENTRAL);
            $cmd_create_db = sprintf('"%s" %s %s -e "CREATE DATABASE %s"',
                $mysql_path, $user_param, $pass_param, $nome_db);
            $cmd_import = sprintf('"%s" %s %s %s < %s/%s.sql',
                $mysql_path, $user_param, $pass_param, $nome_db, sys_get_temp_dir(), DB_NAME_CENTRAL);
            
            try {
                exec($cmd_export, $output_export, $return_var_export);
                if ($return_var_export !== 0) {
                    throw new Exception("Erro ao exportar o banco de dados. Saída: " . implode(" ", $output_export));
                }
                exec($cmd_create_db, $output_create, $return_var_create);
                if ($return_var_create !== 0) {
                    throw new Exception("Erro ao criar o novo banco de dados. Saída: " . implode(" ", $output_create));
                }
                exec($cmd_import, $output_import, $return_var_import);
                if ($return_var_import !== 0) {
                    throw new Exception("Erro ao importar os dados para o novo banco de dados. Saída: " . implode(" ", $output_import));
                }
                unlink(sys_get_temp_dir() . '/' . DB_NAME_CENTRAL . '.sql');
                $mensagem_status_db = '<div class="alert alert-success">Banco de dados clonado com sucesso!</div>';
            } catch (Exception $e) {
                $mensagem_status_db = '<div class="alert alert-danger">Erro no processo de clonagem do banco de dados: ' . $e->getMessage() . '</div>';
            }
            
            // 4. Clonar a pasta e atualizar o config.php
            $mensagem_status_pasta = '';
            if (empty($mensagem_status_db)) {
                try {
                    recurse_copy($pasta_origem, $pasta_destino);
                    
                    $config_file = $pasta_destino . '/config/config.php';
                    $config_content = file_get_contents($config_file);
                    
                    if ($config_content === false) {
                        throw new Exception("Erro ao ler o arquivo de configuração de origem.");
                    }
                    
                    $config_content = preg_replace('/\'DB_NAME\', \'[a-zA-Z0-9_]+\'/', '\'DB_NAME\', \'' . $nome_db . '\'', $config_content);
                    
                    if (file_put_contents($config_file, $config_content) === false) {
                        throw new Exception("Erro ao atualizar o arquivo de configuração na nova pasta.");
                    }

                    $mensagem_status_pasta = '<div class="alert alert-success">Pasta e arquivo de configuração clonados com sucesso!</div>';
                } catch (Exception $e) {
                    $mensagem_status_pasta = '<div class="alert alert-danger">Erro no processo de clonagem da pasta: ' . $e->getMessage() . '</div>';
                }
            }

            // Exibe a mensagem final
            $mensagem_status = $mensagem_status_db . $mensagem_status_pasta;
        }
    }
}
?>

<div class="container mt-5">
    <h3 class="text-center mb-4">Clonar Nova Clínica</h3>
    <p class="text-center lead">Preencha o nome da nova clínica e o sistema fará todo o processo de clonagem para você.</p>
    
    <?= $mensagem_status ?>
    
    <form action="clonar_clinica.php" method="POST">
        <div class="mb-3">
            <label for="nome_nova_clinica" class="form-label">Nome da Nova Clínica</label>
            <input type="text" class="form-control" id="nome_nova_clinica" name="nome_nova_clinica" required>
            <small class="form-text text-muted">Ex: Clínica Odontológica. Use a grafia correta com acentos.</small>
        </div>
        
        <button type="submit" class="btn btn-primary"><i class="fas fa-copy"></i> Clonar Clínica</button>
    </form>
</div>

<?php include_once __DIR__ . "/../clinica_medica/private/includes/rodape.php"; ?>