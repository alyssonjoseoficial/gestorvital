<?php
// Inicia a sessão
session_start();

// Destrói todas as variáveis de sessão
session_destroy();

// Redireciona o usuário para a página de login
// O caminho foi ajustado para a raiz do seu domínio, onde o login.php parece estar
header("Location: /login.php");
exit;