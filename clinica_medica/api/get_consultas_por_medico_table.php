<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['administrador', 'recepcao', 'medico'])) {
    echo "Acesso negado.";
    exit();
}

$medico_id = $_GET['medico_id'] ?? null;

if (!$medico_id || !is_numeric($medico_id)) {
    echo "ID do médico inválido.";
    exit();
}

// CORRIGIDO: A consulta agora busca 'c.turno' em vez de 'c.hora_consulta'
$sql = "SELECT c.id, p.nome AS paciente_nome, c.data_consulta, c.turno, c.status FROM consultas c INNER JOIN pacientes p ON c.paciente_id = p.id WHERE c.medico_id = ? ORDER BY c.data_consulta ASC, c.turno ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    echo "<table class='table table-striped'>";
    // CORRIGIDO: O cabeçalho agora exibe 'Turno'
    echo "<thead><tr><th>Paciente</th><th>Data</th><th>Turno</th><th>Status</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while ($consulta = $resultado->fetch_assoc()) {
        $data_formatada = (new DateTime($consulta['data_consulta']))->format('d/m/Y');
        $status_label = '';
        switch ($consulta['status']) {
            case 'agendada': $status_label = 'Agendada'; break;
            case 'realizada': $status_label = 'Realizada'; break;
            case 'cancelada': $status_label = 'Cancelada'; break;
            default: $status_label = 'Desconhecido'; break;
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($consulta['paciente_nome']) . "</td>";
        echo "<td>" . htmlspecialchars($data_formatada) . "</td>";
        // CORRIGIDO: Agora exibe o turno e formata a primeira letra para maiúscula
        echo "<td>" . htmlspecialchars(ucfirst($consulta['turno'])) . "</td>";
        echo "<td>" . htmlspecialchars($status_label) . "</td>";
        echo "<td><button class='btn btn-sm btn-info btn-editar-consulta' data-id='{$consulta['id']}'>Editar</button></td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p>Nenhuma consulta agendada para este médico.</p>";
}

$stmt->close();
$conn->close();
?>