<?php
require_once "../includes/cabecalho.php";
require_once "../includes/menu.php";
require_once "../../config/config.php";
require_once "../../config/log_auditoria.php"; // Adicionado: Inclui a função de log

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $data_nascimento = $_POST['data_nascimento'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $convenio_id = $_POST['convenio_id'] ?: NULL;
    $observacoes = $_POST['observacoes'];

    $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, telefone, email, endereco, convenio_id, observacoes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssis", $nome, $cpf, $data_nascimento, $telefone, $email, $endereco, $convenio_id, $observacoes);

    if ($stmt->execute()) {
        $novo_paciente_id = $conn->insert_id; // Pega o ID do novo paciente
        
        // Adicionado: Chama a função para registrar a ação
        registrar_log('Cadastrou novo paciente: ' . $nome, 'Pacientes', $novo_paciente_id);
        
        header("Location: listar.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Erro: " . $conn->error . "</div>";
    }
}

$convenios = $conn->query("SELECT * FROM convenios ORDER BY nome ASC");
?>

<h4>Novo Paciente</h4>
<form method="POST">
    <div class="row mb-3">
        <div class="col">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="col">
            <label>CPF:</label>
            <input type="text" name="cpf" class="form-control" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col">
            <label>Data de Nascimento:</label>
            <input type="date" name="data_nascimento" class="form-control">
        </div>
        <div class="col">
            <label>Telefone:</label>
            <input type="text" name="telefone" class="form-control">
        </div>
    </div>

    <div class="mb-3">
        <label>E-mail:</label>
        <input type="email" name="email" class="form-control">
    </div>

    <div class="mb-3">
        <label>Endereço:</label>
        <textarea name="endereco" class="form-control"></textarea>
    </div>

    <div class="mb-3">
        <label>Convênio:</label>
        <select name="convenio_id" class="form-control">
            <option value="">Nenhum</option>
            <?php while($c = $convenios->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo $c['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Observações:</label>
        <textarea name="observacoes" class="form-control"></textarea>
    </div>

    <button type="submit" class="btn btn-success">Salvar</button>
    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php require_once "../includes/rodape.php"; ?>