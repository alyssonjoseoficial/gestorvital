<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
use Dompdf\Dompdf;

session_start();
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

if (!isset($_GET['id'])) {
    die("ID da receita não fornecido.");
}

$receita_id = $_GET['id'];

try {
    // Consulta para buscar os dados da receita, paciente e médico
    $sql = "SELECT r.receita, r.instrucoes_gerais, r.data_receita,
                   p.nome AS paciente_nome, p.data_nascimento, p.telefone,
                   m.nome AS medico_nome, m.crm, m.especialidade
            FROM receitas r
            JOIN pacientes p ON r.paciente_id = p.id
            JOIN medicos m ON r.medico_id = m.id
            WHERE r.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $receita_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $receita = $resultado->fetch_assoc();

    if (!$receita) {
        die("Receita não encontrada.");
    }

    $data_nascimento = new DateTime($receita['data_nascimento']);
    $hoje = new DateTime();
    $idade = $hoje->diff($data_nascimento)->y;

    // Conteúdo HTML da receita
    $html = '
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Receita Médica</title>
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
            <h2>Receituário</h2>
            <p>Data: ' . date('d/m/Y', strtotime($receita['data_receita'])) . '</p>
        </div>

        <div class="patient-info">
            <strong>Paciente:</strong> ' . htmlspecialchars($receita['paciente_nome']) . '<br>
            <strong>Idade:</strong> ' . htmlspecialchars($idade) . ' anos<br>
            <strong>Telefone:</strong> ' . htmlspecialchars($receita['telefone']) . '
        </div>

        <div class="content">
            <p><strong>Prescrição:</strong></p>
            <p>' . nl2br(htmlspecialchars($receita['receita'])) . '</p>
            <br>
            <p><strong>Instruções Gerais:</strong></p>
            <p>' . nl2br(htmlspecialchars($receita['instrucoes_gerais'])) . '</p>
        </div>

        <div class="signature">
            <br><br>
            ________________________________________<br>
            Dr(a). ' . htmlspecialchars($receita['medico_nome']) . '<br>
            ' . htmlspecialchars($receita['especialidade']) . ' | CRM: ' . htmlspecialchars($receita['crm']) . '
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
    $dompdf->stream("receita_" . $receita_id . ".pdf", ["Attachment" => false]);

} catch (Exception $e) {
    die("Erro ao gerar PDF: " . $e->getMessage());
}
?>