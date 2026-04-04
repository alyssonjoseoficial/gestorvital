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
    die("ID do atestado não fornecido.");
}

$atestado_id = $_GET['id'];

try {
    // Consulta para buscar os dados do atestado, paciente e médico
    $sql = "SELECT a.cid, a.dias_repouso, a.motivo_repouso, a.data_atestado,
                   p.nome AS paciente_nome, p.data_nascimento,
                   m.nome AS medico_nome, m.crm, m.especialidade
            FROM atestados a
            JOIN pacientes p ON a.paciente_id = p.id
            JOIN medicos m ON a.medico_id = m.id
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $atestado_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $atestado = $resultado->fetch_assoc();

    if (!$atestado) {
        die("Atestado não encontrado.");
    }

    $data_nascimento = new DateTime($atestado['data_nascimento']);
    $hoje = new DateTime();
    $idade = $hoje->diff($data_nascimento)->y;

    // Conteúdo HTML do atestado
    $html = '
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Atestado Médico</title>
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
            <h2>Atestado Médico</h2>
            <p>Data: ' . date('d/m/Y', strtotime($atestado['data_atestado'])) . '</p>
        </div>

        <div class="content">
            <p>Atesto, para os devidos fins, que o(a) paciente <strong>' . htmlspecialchars($atestado['paciente_nome']) . '</strong>,
            portador(a) do RG nº XXXXXX, esteve em meu consultório
            no dia ' . date('d/m/Y', strtotime($atestado['data_atestado'])) . ' e necessita de **' . htmlspecialchars($atestado['dias_repouso']) . '** dias de repouso por motivo de **' . htmlspecialchars($atestado['motivo_repouso']) . '**.</p>
            <p><strong>CID:</strong> ' . htmlspecialchars($atestado['cid']) . '</p>
        </div>

        <div class="signature">
            <br><br>
            ________________________________________<br>
            Dr(a). ' . htmlspecialchars($atestado['medico_nome']) . '<br>
            ' . htmlspecialchars($atestado['especialidade']) . ' | CRM: ' . htmlspecialchars($atestado['crm']) . '
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
    $dompdf->stream("atestado_" . $atestado_id . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    die("Erro ao gerar PDF: " . $e->getMessage());
}
?>