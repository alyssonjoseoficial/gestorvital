<?php

// Apenas o HTML do formulário, para fazer o teste
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo '<h2>Diagnóstico de Upload de Arquivo</h2>';
    echo '<form method="POST" enctype="multipart/form-data">';
    echo '  <label for="logo_teste">Selecione o arquivo para teste:</label><br>';
    echo '  <input type="file" name="logo_teste" id="logo_teste"><br><br>';
    echo '  <button type="submit">Testar Upload</button>';
    echo '</form>';
    exit;
}

// PADRONIZE O NOME DA PASTA DA CLÍNICA AQUI (EX: clinica_medica, clinica_a)
$nome_pasta_clinica_padronizado = "clinica_medica";

// O CAMINHO ABSOLUTO PARA A PASTA DE UPLOADS DO SEU SERVIDOR
// Este é o mesmo caminho que o seu código tenta usar para salvar
$pasta_uploads = $_SERVER['DOCUMENT_ROOT'] . "/gestorvital/" . $nome_pasta_clinica_padronizado . "/uploads/";

echo '<h2>Resultado do Diagnóstico</h2>';
echo '<p>Verificando o caminho de destino: <strong>' . $pasta_uploads . '</strong></p>';

// Verifica se o arquivo foi enviado
if (empty($_FILES['logo_teste']['tmp_name'])) {
    echo '<p style="color: red;"><strong>Erro:</strong> Nenhum arquivo enviado ou erro no upload.</p>';
    exit;
}

// Verifica se a pasta de destino existe
if (!is_dir($pasta_uploads)) {
    echo '<p style="color: red;"><strong>Erro Fatal:</strong> A pasta de uploads não foi encontrada.</p>';
    echo '<p>Caminho verificado: ' . realpath($pasta_uploads) . '</p>';
    exit;
}

// Verifica as permissões da pasta
if (!is_writable($pasta_uploads)) {
    echo '<p style="color: red;"><strong>Erro Fatal:</strong> A pasta de uploads não tem permissão de escrita. Por favor, ajuste as permissões de escrita (chmod) para o usuário do servidor.</p>';
    echo '<p>Caminho verificado: ' . realpath($pasta_uploads) . '</p>';
    exit;
}

$extensao = pathinfo($_FILES['logo_teste']['name'], PATHINFO_EXTENSION);
$nome_arquivo_novo = uniqid() . '.' . $extensao;
$caminho_completo = $pasta_uploads . $nome_arquivo_novo;

echo '<p>Caminho completo do arquivo: <strong>' . $caminho_completo . '</strong></p>';
echo '<p>Caminho temporário: <strong>' . $_FILES['logo_teste']['tmp_name'] . '</strong></p>';

// Tenta mover o arquivo
if (move_uploaded_file($_FILES['logo_teste']['tmp_name'], $caminho_completo)) {
    echo '<p style="color: green;"><strong>Sucesso Definitivo!</strong> O arquivo foi movido para o destino.</p>';
    echo '<p>Caminho real e completo: ' . realpath($caminho_completo) . '</p>';
} else {
    echo '<p style="color: red;"><strong>Erro de Upload:</strong> Falha ao mover o arquivo. O servidor recusou a operação.</p>';
    echo '<p>Verifique o log de erros do PHP para mais detalhes sobre a falha.</p>';
    // Adiciona uma mensagem de erro para o log do servidor
    error_log("Falha ao mover o arquivo de " . $_FILES['logo_teste']['tmp_name'] . " para " . $caminho_completo);
}

?>