<?php
// Exibe o caminho do diretório atual
echo "Caminho do arquivo atual (cabecalho.php está aqui): <br>";
echo __DIR__ . "<br><br>";

// Tenta incluir o arquivo helpers.php usando o caminho que estamos usando
$caminho_helpers = __DIR__ . '/../../../config/helpers.php';

echo "Tentando incluir o helpers.php do caminho: <br>";
echo $caminho_helpers . "<br><br>";

// Verifica se o arquivo existe nesse caminho
if (file_exists($caminho_helpers)) {
    echo "<b><p style='color: green;'>Sucesso! O arquivo helpers.php foi ENCONTRADO!</p></b>";
    
    // Tenta incluir o arquivo
    require_once $caminho_helpers;

    // Tenta chamar a função para ver se ela existe
    if (function_exists('get_contrast_color')) {
        echo "<br><b><p style='color: green;'>Sucesso! A função get_contrast_color() foi ENCONTRADA!</p></b>";
        echo "<br>Testando a função com a cor preta: " . get_contrast_color('#000000');
    } else {
        echo "<br><b><p style='color: red;'>Erro Fatal: O arquivo foi encontrado, mas a função não foi definida.</p></b>";
    }

} else {
    echo "<b><p style='color: red;'>Erro Fatal: O arquivo helpers.php NÃO FOI ENCONTRADO!</p></b>";
}

?>