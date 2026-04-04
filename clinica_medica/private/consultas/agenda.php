<?php
require_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../includes/cabecalho.php';
include_once __DIR__ . '/../includes/menu.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($nivel !== 'medico') {
    die("Acesso negado.");
}

// Buscar o ID do médico vinculado ao usuário logado
$stmt_med = $conn->prepare("
    SELECT m.id 
    FROM medicos m
    INNER JOIN medico_usuario mu ON mu.medico_id = m.id
    WHERE mu.usuario_id = ?
    LIMIT 1
");
$stmt_med->bind_param("i", $usuario_id);
$stmt_med->execute();
$result_med = $stmt_med->get_result();
$medico = $result_med->fetch_assoc();

if (!$medico) {
    die("Médico não encontrado para o usuário logado.");
}

$medico_id = $medico['id'];
$hoje = date('Y-m-d');

// Buscar consultas do médico para hoje
$sql = "SELECT c.id, c.hora_consulta, p.nome AS paciente_nome, c.status
        FROM consultas c
        JOIN pacientes p ON c.paciente_id = p.id
        WHERE c.medico_id = ? AND c.data_consulta = ?
        ORDER BY c.hora_consulta ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $medico_id, $hoje);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <h3>Agenda de Consultas para Hoje (<?= date('d/m/Y') ?>)</h3>

    <?php if ($result->num_rows === 0): ?>
        <p>Você não tem consultas agendadas para hoje.</p>
    <?php else: ?>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Horário</th>
                    <th>Paciente</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($consulta = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(substr($consulta['hora_consulta'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($consulta['paciente_nome']) ?></td>
                        <td><?= htmlspecialchars($consulta['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/rodape.php'; ?>
