<?php
// O caminho base do seu projeto na URL do navegador
$caminho_base_do_projeto = '/gestorvital/';

// Inclui o cabeçalho master
require_once __DIR__ . '/includes/master_cabecalho.php';
// Inclui o arquivo de configuração central
require_once __DIR__ . '/../config/config_central.php';

// Se o ID da clínica não for passado, redireciona para a página de gerenciamento
if (!isset($_GET['clinica_id'])) {
    header("Location: gerenciar_identidade.php");
    exit;
}

$clinica_id = filter_var($_GET['clinica_id'], FILTER_SANITIZE_NUMBER_INT);

// Conecta ao banco de dados central
$conn_central = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CENTRAL);
if ($conn_central->connect_error) {
    die("Conexão falhou: " . $conn_central->connect_error);
}

$clinica_info = null;
$stmt_central = $conn_central->prepare("SELECT * FROM clinicas WHERE id = ?");
$stmt_central->bind_param("i", $clinica_id);
$stmt_central->execute();
$result_central = $stmt_central->get_result();
$clinica_info = $result_central->fetch_assoc();
$stmt_central->close();
$conn_central->close();

if (!$clinica_info) {
    die("Clínica não encontrada.");
}

// Conecta ao banco de dados específico da clínica
$conn_clinica = new mysqli($clinica_info['db_host'], $clinica_info['db_user'], $clinica_info['db_pass'], $clinica_info['db_name']);
if ($conn_clinica->connect_error) {
    die("Conexão falhou: " . $conn_clinica->connect_error);
}

$identidade = null;
$sql = "SELECT * FROM identidade_clinica WHERE id = 1";
$result = $conn_clinica->query($sql);
if ($result && $result->num_rows > 0) {
    $identidade = $result->fetch_assoc();
}
$conn_clinica->close();
?>

<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <h2 class="mt-4">Editar Identidade da Clínica</h2>
    <p>Preencha os campos abaixo para atualizar a identidade visual da clínica.</p>
    <div class="card p-4">
        <form action="processa_edicao_identidade.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_clinica" value="<?= $clinica_id ?>">
            <div class="mb-3">
                <label for="nome_clinica" class="form-label">Nome da Clínica</label>
                <input type="text" class="form-control" id="nome_clinica" name="nome_clinica" value="<?= htmlspecialchars($identidade['nome_clinica'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="url_logo" class="form-label">Logo</label>
                <input class="form-control" type="file" id="url_logo" name="url_logo" accept="image/*">
                <?php if (isset($identidade['url_logo']) && !empty($identidade['url_logo'])): ?>
                    <div class="mt-2">
                        <p>Logo atual:</p>
                        <img src="<?= htmlspecialchars('/gestorvital/' . $clinica_info['db_name'] . '/' . $identidade['url_logo']) ?>" alt="Logo da Clínica" style="max-width: 200px; height: auto;">
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="cor_primaria" class="form-label">Cor Primária</label>
                <input type="color" class="form-control form-control-color" id="cor_primaria" name="cor_primaria" value="<?= htmlspecialchars($identidade['cor_primaria'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="endereco" class="form-label">Endereço</label>
                <input type="text" class="form-control" id="endereco" name="endereco" value="<?= htmlspecialchars($identidade['endereco'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($identidade['telefone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($identidade['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
    </div>
</div>
<?php include_once __DIR__ . '/includes/master_rodape.php'; ?>