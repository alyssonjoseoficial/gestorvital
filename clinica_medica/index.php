<?php
// Obtém o nome da pasta (que é o ID da clínica)
$id_clinica = basename(__DIR__);

// Redireciona para o login central, passando o ID
header("Location: ../login.php?id=" . $id_clinica);
exit;
?>