<?php
require_once __DIR__ . '/../../config/config.php';

$prontuario_id = $_GET['prontuario_id'] ?? 0;

if ($prontuario_id > 0) {
    $stmt = $conn->prepare("
        SELECT * FROM evolucao_prontuarios
        WHERE prontuario_id = ?
        ORDER BY data_evolucao DESC
    ");
    $stmt->bind_param("i", $prontuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($evolucao = $result->fetch_assoc()) {
            echo '<div class="card mb-2">';
            echo '    <div class="card-body">';
            echo '        <h6 class="card-subtitle mb-2 text-muted">' . date('d/m/Y H:i', strtotime($evolucao['data_evolucao'])) . '</h6>';
            echo '        <p class="card-text">' . nl2br(htmlspecialchars($evolucao['texto_evolucao'])) . '</p>';
            echo '    </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="alert alert-info">Nenhuma evolução registrada ainda.</div>';
    }
    $stmt->close();
}