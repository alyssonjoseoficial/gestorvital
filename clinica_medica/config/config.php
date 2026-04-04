<?php
// helpers.php

// Definições de ambiente para o servidor de produção
define('DB_SERVER', 'localhost');
// Por segurança, você deve definir o nome do banco de dados e usuário corretos para este sistema
// Exemplo: 'alyss340_vital_db'
define('DB_USERNAME', 'alyss340_teste'); // Substitua com o nome de usuário do seu banco de dados
define('DB_PASSWORD', 'Gja@4367k'); // Substitua com a senha do usuário
define('DB_NAME', 'alyss340_clinica_medica'); // Substitua com o nome do seu banco de dados
    
/* Conecta ao banco de dados MySQL */
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
// Verifica a conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>