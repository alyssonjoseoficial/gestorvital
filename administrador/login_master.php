<?php
// O caminho base para a raiz do projeto na URL do navegador
$caminho_base_do_projeto = '/gestorvital/';
$mensagem_erro = '';

if (isset($_GET['erro'])) {
    if ($_GET['erro'] == '1') {
        $mensagem_erro = "Usuário ou senha incorretos.";
    } elseif ($_GET['erro'] == 'conexao') {
        $mensagem_erro = "Erro de conexão com o banco de dados. Tente novamente mais tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin-top: 100px; }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card shadow-lg">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Painel Master</h3>
                <h4 class="card-subtitle text-center mb-4 text-muted">Acesso Restrito</h4>
                <?php if ($mensagem_erro): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $mensagem_erro ?>
                    </div>
                <?php endif; ?>
                <form action="processa_login_master.php" method="POST">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required>
                    </div>
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-dark">Entrar</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="<?= $caminho_base_do_projeto ?>login.php">Voltar para o Login de Clínicas</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>