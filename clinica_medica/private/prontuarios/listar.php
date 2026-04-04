<?php
// Inicia a sessão no topo, antes de qualquer coisa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui os arquivos necessários
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/cabecalho.php';
include_once "../includes/menu.php";

$nivel = $_SESSION['nivel_acesso'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($nivel, ['administrador', 'medico'])) {
    die('Acesso negado.');
}

// Se for médico, buscar o ID do médico vinculado ao usuário logado
$medico_id = null;
if ($nivel === 'medico') {
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
    if ($medico) {
        $medico_id = $medico['id'];
    } else {
        die("Médico não encontrado para o usuário logado.");
    }
}

// Recebe filtro do form
$filtro = trim($_GET['filtro'] ?? '');

// Montar query base
$where = '';
$params = [];
$types = '';

if ($nivel === 'medico') {
    $where = "WHERE p.id IN (SELECT paciente_id FROM prontuarios WHERE medico_id = ?)";
    $params[] = $medico_id;
    $types .= 'i';
}

if ($filtro !== '') {
    $filtro_sql = "%" . $filtro . "%";
    if ($where) {
        $where .= " AND (p.nome LIKE ? OR p.cpf LIKE ?)";
    } else {
        $where = "WHERE p.nome LIKE ? OR p.cpf LIKE ?";
    }
    $params[] = $filtro_sql;
    $params[] = $filtro_sql;
    $types .= 'ss';
}

$sql = "SELECT p.id, p.nome, p.cpf, p.telefone FROM pacientes p $where ORDER BY p.nome ASC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Pacientes Vinculados</h4>
        <a href="<?= BASE_URL_PRIVATE ?>prontuarios/formulario.php" class="btn btn-success">➕ Cadastrar Novo Prontuário</a>
    </div>

    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="filtro" class="form-control" placeholder="Buscar por nome ou CPF" value="<?= htmlspecialchars($filtro) ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="listar.php" class="btn btn-secondary ms-2">Limpar</a>
        </div>
    </form>

    <?php if ($result->num_rows === 0): ?>
        <p>Nenhum paciente encontrado.</p>
    <?php else: ?>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($paciente = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($paciente['nome']) ?></td>
                        <td><?= htmlspecialchars($paciente['cpf']) ?></td>
                        <td><?= htmlspecialchars($paciente['telefone']) ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL_PRIVATE ?>prontuarios/visualizar.php" style="display:inline;">
                                <input type="hidden" name="paciente_id" value="<?= $paciente['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Visualizar Prontuário</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>