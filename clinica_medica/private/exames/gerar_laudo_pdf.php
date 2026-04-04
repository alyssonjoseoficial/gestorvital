<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$nivel_acesso = $_SESSION['nivel_acesso'] ?? '';
if (!in_array($nivel_acesso, ['administrador', 'recepcao', 'medico'])) {
    die("Acesso negado.");
}

$exame_id = $_GET['id'] ?? null;
if (!$exame_id) {
    die("ID do exame não fornecido.");
}

try {
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }

    // Buscar os dados do exame, paciente e médico
    $sql = "SELECT 
                e.tipo_exame, 
                e.data_exame, 
                e.resultado,
                p.nome AS nome_paciente,
                p.data_nascimento,
                p.telefone,
                m.nome AS nome_medico,
                m.especialidade
            FROM exames e
            INNER JOIN pacientes p ON e.paciente_id = p.id
            INNER JOIN medicos m ON e.medico_id = m.id
            WHERE e.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exame_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exame = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$exame) {
        die("Exame não encontrado.");
    }

    // --- Conteúdo HTML do Laudo ---
    $html = '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .container { width: 90%; margin: 0 auto; }
            h1, h2, h3 { text-align: center; }
            .header, .footer { text-align: center; font-size: 10px; margin-bottom: 20px; }
            .patient-info, .exam-info { margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
            .patient-info p, .exam-info p { margin: 5px 0; }
            .section-title { font-weight: bold; margin-top: 15px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
            .result { margin-top: 10px; line-height: 1.5; }
            .signature { margin-top: 40px; text-align: center; }
            .signature p { margin: 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>CLÍNICA MÉDICA</h2>
                <p>Endereço da Clínica | Telefone: (00) 0000-0000 | Email: contato@clinica.com</p>
            </div>

            <h3>LAUDO DE EXAME</h3>

            <div class="patient-info">
                <p><strong>Paciente:</strong> ' . htmlspecialchars($exame['nome_paciente']) . '</p>
                <p><strong>Data de Nascimento:</strong> ' . date('d/m/Y', strtotime($exame['data_nascimento'])) . '</p>
                <p><strong>Telefone:</strong> ' . htmlspecialchars($exame['telefone']) . '</p>
            </div>

            <div class="exam-info">
                <p><strong>Tipo de Exame:</strong> ' . htmlspecialchars($exame['tipo_exame']) . '</p>
                <p><strong>Data do Exame:</strong> ' . date('d/m/Y', strtotime($exame['data_exame'])) . '</p>
                <p><strong>Médico Responsável:</strong> ' . htmlspecialchars($exame['nome_medico']) . ' (' . htmlspecialchars($exame['especialidade']) . ')</p>
            </div>

            <div class="section-title">Resultado do Exame</div>
            <div class="result">
                <p>' . nl2br(htmlspecialchars($exame['resultado'])) . '</p>
            </div>

            <div class="signature">
                <p>______________________________________</p>
                <p><strong>' . htmlspecialchars($exame['nome_medico']) . '</strong></p>
                <p>' . htmlspecialchars($exame['especialidade']) . '</p>
            </div>

        </div>
    </body>
    </html>';

    // Configurações do Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'sans-serif');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Envia o PDF para o navegador
    $dompdf->stream("laudo_exame_" . $exame_id . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    die("Erro ao gerar o PDF: " . htmlspecialchars($e->getMessage()));
}