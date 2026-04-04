<?php
session_start();

if (isset($_SESSION['erro_login'])) {
    $erro_login = $_SESSION['erro_login'];
    unset($_SESSION['erro_login']);
} else {
    $erro_login = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Clínica Médica</title>
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
            height: 100vh;
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
            color: black;
        }
        .form-control:focus {
            box-shadow: none;
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
        .password-container {
            position: relative;
        }
        .password-container .fa-eye {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #ccc;
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
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="feature-box">
                        <h4 class="text-center mb-4">Acesso ao Sistema</h4>
                        <?php
                        if ($erro_login) {
                            echo "<div class='alert alert-danger'>" . $erro_login . "</div>";
                        }
                        ?>
                        <form action="processa_login.php" method="POST">
                            <div class="mb-3">
                                <label for="id_clinica" class="form-label">Código da Clínica:</label>
                                <input type="text" name="id_clinica" id="id_clinica" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail:</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha:</label>
                                <div class="password-container">
                                    <input type="password" name="senha" id="senha" class="form-control" required>
                                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-custom w-100 mt-2">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
        const togglePassword = document.getElementById('togglePassword');
        const senhaInput = document.getElementById('senha');
        
        togglePassword.addEventListener('click', function () {
            const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            senhaInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>