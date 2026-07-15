<?php
$hub_url = "https://kirontech.com.br/api/webhook/update_stats.php";
$api_key = "gestorvital_api_key_2026";

// TODO: Troque os ZEROS abaixo pelas consultas SQL reais do seu banco de dados
$data = [
    "active_users" => 0,
    "revenue_monthly" => 0,
    "auto_blocks_today" => 0,
    "new_subscriptions_today" => 0
];

$payload = json_encode($data);
$ch = curl_init($hub_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "x-api-key: " . $api_key
]);

$response = curl_exec($ch);
curl_close($ch);
echo "Sincronizado: " . $response;
?>