<?php
// O rodapé não precisa mais incluir o config.php, pois o cabeçalho já fez isso.
// Isso evita que a conexão seja criada e fechada múltiplas vezes.

// A variável $conn já está disponível aqui, vinda do cabecalho.php

$dados_rodape = [
    'nome_clinica' => 'Clínica Médica',
    'endereco' => '',
    'telefone' => '',
    'email' => '',
    'cor_primaria' => '#f8f9fa' // Cor de fundo padrão do Bootstrap
];

try {
    // Verifica se a variável de conexão existe e se a conexão com o banco de dados ainda está ativa
    if (isset($conn) && $conn->ping()) {
        $sql = "SELECT nome_clinica, endereco, telefone, email, cor_primaria FROM identidade_clinica ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $dados_rodape = $result->fetch_assoc();
        }
    }
} catch (Exception $e) {
    error_log("Erro ao buscar dados do rodapé: " . $e->getMessage());
}
?>
    </main>
    <footer style="text-align: center; padding: 10px; background-color: <?= htmlspecialchars($dados_rodape['cor_primaria']) ?>; color: white; border-top: 1px solid #dee2e6;">
        <p>&copy; <?= date('Y') ?> - <?= htmlspecialchars($dados_rodape['nome_clinica']) ?></p>
        <p style="margin-bottom: 0;">
            Endereço: <?= htmlspecialchars($dados_rodape['endereco']) ?> |
            Telefone: <?= htmlspecialchars($dados_rodape['telefone']) ?> |
            E-mail: <?= htmlspecialchars($dados_rodape['email']) ?>
        </p>
    </footer>
</body>
</html>
<?php
// Fecha a conexão com o banco de dados no final da página, se ela existir
if (isset($conn) && $conn) {
    $conn->close();
}
?>