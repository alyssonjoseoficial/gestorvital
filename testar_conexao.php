<?php
// Inclui o arquivo de configuração central
require_once __DIR__ . '/config/config_central.php';

// Tenta estabelecer a conexão com o banco de dados central
$connCentral = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CENTRAL);

// Verifica a conexão
if ($connCentral->connect_error) {
    // Se a conexão falhar, exibe o erro detalhado
    die("Falha na conexão com o banco de dados central: " . $connCentral->connect_error);
}

// Se a conexão for bem-sucedida, exibe uma mensagem de sucesso
echo "Conexão com o banco de dados central estabelecida com sucesso!";

// Fecha a conexão
$connCentral->close();

?>