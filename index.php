<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Médica - Gerencie sua saúde com eficiência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .hero {
            /* CORRIGIDO: Usando o caminho relativo à raiz do domínio */
            background: url('/public_assets/background.jpg') center center/cover no-repeat fixed;
            height: 80vh;
            color: white;
            position: relative;
            display: flex;
            align-items: center;
        }
        .overlay {
            background: rgba(0, 0, 0, 0.6);
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0; left: 0;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .feature-box {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
        }
        .form-control:focus {
            box-shadow: none;
        }
        .video-wrapper {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.4);
        }
        .form-section {
            padding-top: 3rem;
            padding-bottom: 5rem;
            background-color: #e9ecef;
        }
        .btn-custom {
            background-color: #007BFF;
            color: white;
            border-radius: 30px;
            padding: 12px 30px;
            transition: background 0.3s ease;
            font-weight: 600;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="overlay"></div>
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5" data-aos="fade-right">
                    <h1 class="display-4 fw-bold mb-4">GestorVital</h1>
                    <ul class="list-unstyled fs-5">
                        <li class="mb-3">✔️ Controle total sobre agendamentos</li>
                        <li class="mb-3">✔️ Históricos de pacientes completos</li>
                        <li class="mb-3">✔️ Gestão financeira intuitiva</li>
                    </ul>
                    <a href="login.php" class="btn btn-custom mt-3">Acessar Sistema</a>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <h2 class="mb-4">Conheça nosso sistema</h2>
                    <div class="video-wrapper ratio ratio-16x9">
                        <iframe width="560" height="315" src="https://www.youtube.com/embed/SUA_FzQW0hM" title="Vídeo de Apresentação" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="form-section" data-aos="fade-up">
        <div class="container">
            <?php
            $status = $_GET['status'] ?? '';
            $alert_class = '';
            $message = '';
            if ($status === 'sucesso') {
                $alert_class = 'alert-success';
                $message = 'Mensagem enviada com sucesso! Em breve entraremos em contato.';
            } elseif ($status === 'erro_campos') {
                $alert_class = 'alert-warning';
                $message = 'Por favor, preencha todos os campos obrigatórios.';
            } elseif ($status === 'erro_db') {
                $alert_class = 'alert-danger';
                $message = 'Ocorreu um erro ao enviar a mensagem. Tente novamente mais tarde.';
            }
            if ($status) {
                echo "<div class='row justify-content-center'><div class='col-lg-8'><div class='alert $alert_class' role='alert'>$message</div></div></div>";
            }
            ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="feature-box">
                        <h4 class="mb-3 text-center">Entre em contato conosco</h4>
                        <form action="public/salvar_contato.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" placeholder="Seu nome" name="nome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="email" class="form-control" placeholder="Seu e-mail" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Assunto (opcional)" name="assunto">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" rows="4" placeholder="Sua mensagem" name="mensagem" required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-custom px-5">Enviar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="text-center text-white py-4" style="background-color: #0056b3;">
        © <?= date('Y') ?> Gestor Vital. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>