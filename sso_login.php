<?php
$secret_key = "gestorvital_secret_key_2026!@#"; 
if (isset($_GET["token"])) {
    $jwt = $_GET["token"];
    $tokenParts = explode(".", $jwt);
    if(count($tokenParts) == 3) {
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signature_provided = $tokenParts[2];
        $base64UrlHeader = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($header));
        $base64UrlPayload = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($payload));
        $signature = hash_hmac("sha256", $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
        $base64UrlSignature = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($signature));
        
        if ($base64UrlSignature === $signature_provided) {
            $data = json_decode($payload);
            if(time() <= $data->exp) {
                session_start();
                $_SESSION["admin_logged_in"] = true;
                $_SESSION["admin_id"] = $data->user_id;
                
                // TODO: Redirecionar para o painel de admin correto.
                // Substitua a proxima linha pela rota do seu painel:
                echo "<h1>Autenticado com Sucesso no GestorVital!</h1><p>Altere o header() neste arquivo para redirecionar de fato.</p>";
                exit();
            } else {
                die("Erro SSO: Token expirado.");
            }
        } else {
            die("Erro SSO: Assinatura invalida.");
        }
    } else {
        die("Erro SSO: Token malformado.");
    }
} else {
    die("Erro SSO: Token ausente.");
}
?>