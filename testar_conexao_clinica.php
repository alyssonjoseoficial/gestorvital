<?php
// Arquivo: gestorvital/testar_conexao_clinica.php

// Inclui o arquivo de configuração central
require_once __DIR__ . '/config/config_central.php';

// ATENÇÃO: SUBSTITUA O NOME DO BANCO DE DADOS DA CLÍNICA ABAIXO
// Por exemplo, 'alyss340_clinica_medica'
define('DB_NAME_CLINICA', 'alyss340_clinica_medica'); 

// Tenta conectar ao banco de dados específico da clínica
$connClinica = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CLINICA);

// Verifica a conexão
if ($connClinica->connect_error) {
    die("Falha na conexão com o banco de dados da clínica: " . $connClinica->connect_error);
}

// Se a conexão for bem-sucedida, exibe uma mensagem de sucesso
echo "Conexão com o banco de dados da clínica (DB: " . DB_NAME_CLINICA . ") estabelecida com sucesso!";

// Fecha a conexão
$connClinica->close();

?>