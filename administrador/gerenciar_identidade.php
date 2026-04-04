<?php
// Inclui o cabeçalho master
require_once __DIR__ . '/includes/master_cabecalho.php';
// Inclui o arquivo de configuração central
require_once __DIR__ . '/../config/config_central.php';

$conn_central = new mysqli(DB_HOST_CENTRAL, DB_USER_CENTRAL, DB_PASS_CENTRAL, DB_NAME_CENTRAL);

if ($conn_central->connect_error) {
    die("Conexão falhou com o banco central: " . $conn_central->connect_error);
}

$clinicas = [];
try {
    $sql = "SELECT id, nome_clinica, db_name FROM clinicas ORDER BY nome_clinica ASC";
    $result = $conn_central->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clinicas[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Erro ao buscar lista de clínicas: " . $e->getMessage());
}

$conn_central->close();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold mb-3">Gerenciar Clínicas</h1>
                <p class="lead text-muted">Gerencie as identidades, logos, e dados de contato de todas as clínicas do sistema.</p>
            </div>
            
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="clonar_tudo.php" class="btn btn-primary btn-lg shadow-sm">
                    <i class="fas fa-copy me-2"></i> Clonar Nova Clínica
                </a>
                <a href="atualizar_caminhos.php" class="btn btn-secondary btn-lg shadow-sm">
                    <i class="fas fa-magic me-2"></i> Atualizar Caminhos
                </a>
                <a href="excluir_tudo.php" class="btn btn-danger btn-lg shadow-sm">
                    <i class="fas fa-trash-alt me-2"></i> Excluir Clínica
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col" style="width: 5%;">ID</th>
                            <th scope="col" style="width: 75%;">Nome da Clínica</th>
                            <th scope="col" class="text-center" style="width: 20%;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clinicas)): ?>
                            <?php foreach ($clinicas as $clinica): ?>
                                <tr>
                                    <td><?= htmlspecialchars($clinica['id']) ?></td>
                                    <td><?= htmlspecialchars($clinica['nome_clinica']) ?></td>
                                    <td class="text-center">
                                        <a href="editar_identidade.php?clinica_id=<?= htmlspecialchars($clinica['id']) ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-edit me-1"></i> Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Nenhuma clínica encontrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/master_rodape.php'; ?>