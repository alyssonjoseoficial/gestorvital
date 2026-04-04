<?php
require_once "../includes/cabecalho.php";
require_once "../includes/menu.php";
require_once "../../config/config.php";
require_once "../../config/log_auditoria.php"; // Adicionado: Inclui a função de log

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: listar.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $data_nascimento = $_POST['data_nascimento'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $convenio_id = $_POST['convenio_id'] ?: NULL;
    $observacoes = $_POST['observacoes'];

    $sql = "UPDATE pacientes SET nome=?, cpf=?, data_nascimento=?, telefone=?, email=?, endereco=?, convenio_id=?, observacoes=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssisi", $nome, $cpf, $data_nascimento, $telefone, $email, $endereco, $convenio_id, $observacoes, $id);

    if ($stmt->execute()) {
        // Adicionado: Chama a função para registrar a ação
        registrar_log('Editou paciente: ' . $nome, 'Pacientes', $id);
        
        header("Location: listar.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Erro: " . $conn->error . "</div>";
    }
}

$sql = "SELECT * FROM pacientes WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();

$convenios = $conn->query("SELECT * FROM convenios ORDER BY nome ASC");
?>

<h4>Editar Paciente</h4>
<form method="POST">
    <div class="row mb-3">
        <div class="col">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" value="<?php echo $paciente['nome']; ?>" required>
        </div>
        <div class="col">
            <label>CPF:</label>
            <input type="text" name="cpf" class="form-control" value="<?php echo $paciente['cpf']; ?>" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col">
            <label>Data de Nascimento:</label>
            <input type="date" name="data_nascimento" class="form-control" value="<?php echo $paciente['data_nascimento']; ?>">
        </div>
        <div class="col">
            <label>Telefone:</label>
            <input type="text" name="telefone" class="form-control" value="<?php echo $paciente['telefone']; ?>">
        </div>
    </div>

    <div class="mb-3">
        <label>E-mail:</label>
        <input type="email" name="email" class="form-control" value="<?php echo $paciente['email']; ?>">
    </div>

    <div class="mb-3">
        <label>Endereço:</label>
        <textarea name="endereco" class="form-control"><?php echo $paciente['endereco']; ?></textarea>
    </div>

    <div class="mb-3">
        <label>Convênio:</label>
        <select name="convenio_id" class="form-control">
            <option value="">Nenhum</option>
            <?php while($c = $convenios->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>" <?php if ($paciente['convenio_id'] == $c['id']) echo "selected"; ?>>
                    <?php echo $c['nome']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Observações:</label>
        <textarea name="observacoes" class="form-control"><?php echo $paciente['observacoes']; ?></textarea>
    </div>

    <button type="submit" class="btn btn-success">Salvar Alterações</button>
    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php require_once "../includes/rodape.php"; ?>