<?php
require_once "../../config/config.php"; // <-- INCLUIR conexão antes de usar $conn

require_once "../includes/cabecalho.php";
require_once "../includes/menu.php";
require_once "../../config/funcoes.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

verifica_permissao(['administrador', 'recepcao', 'medico']); // Permissões

$sql = "SELECT p.id, p.nome, p.cpf, p.telefone, c.nome AS convenio
        FROM pacientes p
        LEFT JOIN convenios c ON p.convenio_id = c.id
        ORDER BY p.nome ASC";
$resultado = $conn->query($sql);
?>


<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Pacientes</h4>
    <a href="cadastrar.php" class="btn btn-success">+ Novo Paciente</a>
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Nome</th>
            <th>CPF</th>
            <th>Telefone</th>
            <th>Convênio</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['nome']; ?></td>
                <td><?php echo $row['cpf']; ?></td>
                <td><?php echo $row['telefone']; ?></td>
                <td><?php echo $row['convenio'] ?? '—'; ?></td>
                <td>
                    <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                    <a href="excluir.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir este paciente?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once "../includes/rodape.php"; ?>
