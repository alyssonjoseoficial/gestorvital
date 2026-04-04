<?php
// Certifique-se de que o caminho para o autoload do Dompdf está correto
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
use Dompdf\Dompdf;

session_start();
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

if (!isset($_GET['id'])) {
    die("ID da solicitação não fornecido.");
}

$solicitacao_id = $_GET['id'];

try {
    // Consulta para buscar os dados da solicitação, paciente e médico
    // Consulta para buscar os dados da solicitação, paciente e médico
    $sql = "SELECT s.descricao, s.data_solicitacao,
                   p.nome AS paciente_nome, p.data_nascimento, p.telefone,
                   m.nome AS medico_nome, m.crm, m.especialidade
            FROM solicitacoes_exame s
            JOIN consultas c ON s.consulta_id = c.id
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN medicos m ON c.medico_id = m.id
            WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $solicitacao_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $solicitacao = $resultado->fetch_assoc();

    if (!$solicitacao) {
        die("Solicitação de exame não encontrada.");
    }

    $data_nascimento = new DateTime($solicitacao['data_nascimento']);
    $hoje = new DateTime();
    $idade = $hoje->diff($data_nascimento)->y;

    // Conteúdo HTML da solicitação
    $html = '
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Solicitação de Exame</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .header, .footer { text-align: center; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 24px; }
            .header h2 { margin: 0; font-size: 18px; }
            .patient-info { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; }
            .content { line-height: 1.6; }
            .signature { margin-top: 50px; text-align: center; }
        </style>
    </head>
    <body>

        <div class="header">
            <h1>Clínica Médica</h1>
            <p>Endereço da Clínica | Telefone: (00) 0000-0000</p>
            <hr>
            <h2>Solicitação de Exame</h2>
            <p>Data: ' . date('d/m/Y', strtotime($solicitacao['data_solicitacao'])) . '</p>
        </div>

        <div class="patient-info">
            <strong>Paciente:</strong> ' . htmlspecialchars($solicitacao['paciente_nome']) . '<br>
            <strong>Idade:</strong> ' . htmlspecialchars($idade) . ' anos<br>
            <strong>Telefone:</strong> ' . htmlspecialchars($solicitacao['telefone']) . '
        </div>

        <div class="content">
            <p><strong>Exames Solicitados:</strong></p>
            <p>' . nl2br(htmlspecialchars($solicitacao['descricao'])) . '</p>
        </div>

        <div class="signature">
            <br><br>
            ________________________________________<br>
            Dr(a). ' . htmlspecialchars($solicitacao['medico_nome']) . '<br>
            ' . htmlspecialchars($solicitacao['especialidade']) . ' | CRM: ' . htmlspecialchars($solicitacao['crm']) . '
        </div>

    </body>
    </html>
    ';

    // Instanciação e configuração do Dompdf
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Saída do PDF para o navegador
    $dompdf->stream("solicitacao_exame_" . $solicitacao_id . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    die("Erro ao gerar PDF: " . $e->getMessage());
}
?>