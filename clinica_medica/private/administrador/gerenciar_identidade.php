<?php
session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/log_auditoria.php';
require_once __DIR__ . '/../includes/cabecalho.php';

$niveis_permitidos = ['master', 'administrador'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $niveis_permitidos)) {
    header("Location: " . BASE_URL_PUBLIC . "login.php");
    exit;
}

$mensagem = '';
$identidade = [
    'nome_clinica' => '',
    'url_logo' => '',
    'endereco' => '',
    'telefone' => '',
    'email' => '',
    'cor_primaria' => '#007bff', // Valor padrão
    'id' => null
];

// Lógica para carregar os dados existentes ao carregar a página
try {
    $stmt = $conn->prepare("SELECT * FROM identidade_clinica ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $identidade = $result->fetch_assoc();
    }
    $stmt->close();
} catch (Exception $e) {
    $mensagem = "Erro ao carregar dados existentes: " . $e->getMessage();
}

// Lógica para processar o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_clinica = $_POST['nome_clinica'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $cor_primaria = $_POST['cor_primaria'] ?? '#007bff'; // Novo campo
    $id = $_POST['id'] ?? null;
    $url_logo_db = $identidade['url_logo'] ?? '';

    if (empty($nome_clinica)) {
        $mensagem = "O nome da clínica é obrigatório.";
    } else {
        // Lógica de upload de arquivo
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $caminho_destino = __DIR__ . '/../../uploads/';
            $nome_arquivo = uniqid() . '-' . basename($_FILES['logo_file']['name']);
            $caminho_completo = $caminho_destino . $nome_arquivo;

            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $caminho_completo)) {
                $url_logo_db = 'uploads/' . $nome_arquivo;
                $mensagem .= "Logo enviado com sucesso! ";
            } else {
                $mensagem .= "Erro ao mover arquivo de logo. ";
            }
        }

        try {
            if ($id) {
                // ATUALIZAÇÃO
                $sql = "UPDATE identidade_clinica SET nome_clinica = ?, url_logo = ?, endereco = ?, telefone = ?, email = ?, cor_primaria = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt === false) {
                    throw new Exception("Erro na preparação da consulta: " . $conn->error);
                }

                $stmt->bind_param("ssssssi", $nome_clinica, $url_logo_db, $endereco, $telefone, $email, $cor_primaria, $id);
                
                if ($stmt->execute()) {
                    $acao_log = "Atualizou a identidade da clínica para '{$nome_clinica}'";
                    registrar_log($acao_log, 'Identidade da Clínica', $id);
                    $mensagem .= "Identidade da clínica atualizada com sucesso!";
                } else {
                    throw new Exception("Erro na execução da consulta: " . $stmt->error);
                }
            } else {
                // INSERÇÃO
                $sql = "INSERT INTO identidade_clinica (nome_clinica, url_logo, endereco, telefone, email, cor_primaria) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    throw new Exception("Erro na preparação da consulta: " . $conn->error);
                }
                
                $stmt->bind_param("ssssss", $nome_clinica, $url_logo_db, $endereco, $telefone, $email, $cor_primaria);
                
                if ($stmt->execute()) {
                    $novo_id = $conn->insert_id;
                    $acao_log = "Criou a identidade da clínica com o nome '{$nome_clinica}'";
                    registrar_log($acao_log, 'Identidade da Clínica', $novo_id);
                    $mensagem .= "Identidade da clínica salva com sucesso!";
                } else {
                    throw new Exception("Erro na execução da consulta: " . $stmt->error);
                }
            }
            $stmt->close();

            // Recarrega os dados da identidade da clínica para mostrar o estado atualizado
            $stmt = $conn->prepare("SELECT * FROM identidade_clinica ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $identidade = $result->fetch_assoc();
            }
            $stmt->close();

        } catch (Exception $e) {
            $mensagem .= "Erro ao salvar identidade: " . $e->getMessage();
        }
    }
}
?>

<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gerenciar Identidade da Clínica</h1>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-info" role="alert"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form action="gerenciar_identidade.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($identidade['id'] ?? '') ?>">
        
        <div class="mb-3">
            <label for="nome_clinica" class="form-label">Nome da Clínica</label>
            <input type="text" class="form-control" id="nome_clinica" name="nome_clinica" value="<?= htmlspecialchars($identidade['nome_clinica'] ?? '') ?>" required>
        </div>
        
        <div class="mb-3">
            <label for="logo_file" class="form-label">Enviar Novo Logo</label>
            <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*">
            <div class="form-text">Envie um arquivo de imagem (JPG, PNG, GIF, etc.).</div>
            <?php if (!empty($identidade['url_logo'])): ?>
                <div class="mt-2">
                    <p>Logo Atual:</p>
                    <img src="<?= BASE_URL_PUBLIC . htmlspecialchars($identidade['url_logo']) ?>" alt="Logo Atual" style="max-height: 100px;">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mb-3">
            <label for="cor_primaria" class="form-label">Cor Primária do Site</label>
            <input type="color" class="form-control form-control-color" id="cor_primaria" name="cor_primaria" value="<?= htmlspecialchars($identidade['cor_primaria'] ?? '#007bff') ?>">
        </div>

        <div class="mb-3">
            <label for="endereco" class="form-label">Endereço</label>
            <input type="text" class="form-control" id="endereco" name="endereco" value="<?= htmlspecialchars($identidade['endereco'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($identidade['telefone'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($identidade['email'] ?? '') ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Salvar Identidade</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../includes/rodape.php';
?>