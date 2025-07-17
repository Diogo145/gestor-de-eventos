<?php
include 'db_connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($conn->ping()) {
    echo "Conexão ativa!<br>";
} else {
    die("Conexão fechada ou inválida!");
}

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $register_email = $_POST['email'];
    $register_password = $_POST['senha'];
    $confirm_password = $_POST['confirma-senha'];
    $register_name = isset($_POST['nome']) ? $_POST['nome'] : '';

    $recaptchaSecret = '6Lfi7l8rAAAAAH9jVj5SibzexI4sIme_x-gF192E';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
    $responseData = json_decode($verifyResponse);

    if (!$responseData->success) {
        $error_message = "Por favor confirme que não é um robô!";
    } else {
        // Validação de password no servidor (mínimo 8 caracteres)
        if (strlen($register_password) < 8) {
            $error_message = "A password deve ter pelo menos 8 caracteres.";
        } elseif ($register_password !== $confirm_password) {
            $error_message = "As senhas não coincidem!";
        } else {
            $stmt = $conn->prepare("INSERT INTO utilizador (nome, senha, email, perfil, data_registo) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt === false) {
                die("Erro ao preparar o INSERT: " . $conn->error);
            }
            $perfil = 'utilizador';
            $stmt->bind_param("ssss", $register_name, $register_password, $register_email, $perfil);
            if ($stmt->execute()) {
                // Login automático após registo com sucesso
                $_SESSION['email'] = $register_email;
                $_SESSION['perfil'] = $perfil;

                // Redireciona para homepage
                header("Location: homepage.php");
                exit();
            }else {

                echo "Erro ao inserir: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanMatch - Registro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; display: flex; flex-direction: column; align-items: center; }
        .header { background-color: #fff; border-bottom: 1px solid #ddd; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; }
        .logo { font-size: 24px; font-weight: bold; color: #333; }
        .nav a { margin-left: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        .form-section { background-color: #fff; border: 2px solid #ccc; border-radius: 10px; padding: 20px; width: 300px; text-align: center; margin-top: 50px; }
        .form-section h2 { color: #333; font-size: 20px; margin-bottom: 20px; }
        .form-section label { display: block; text-align: left; margin: 10px 0 5px; color: #666; font-size: 14px; }
        .form-section input { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .form-section button { background-color: #007bff; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .form-section a { display: block; margin-top: 10px; color: #007bff; text-decoration: none; font-size: 14px; }
        .error-message, .success-message { color: red; font-size: 14px; margin-top: 10px; }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Validação JS para password mínima e igual -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (password.length < 8) {
                alert('A password deve ter pelo menos 8 caracteres.');
                e.preventDefault();
                return;
            }

            if (password !== confirmPassword) {
                alert('As passwords não coincidem.');
                e.preventDefault();
                return;
            }
        });
    });
    </script>
</head>
<body>
    <div class="header">
        <div class="logo">PlanMatch</div>
        <div class="nav">
            <a href="homepage.php">Início</a>
            <a href="events.php">Eventos</a>
            <a href="contact.php">Contactos</a>
        </div>
    </div>

    <div class="form-section">
        <h2>Registe-se</h2>
        <form method="POST">
            <label for="register-name">Nome</label>
            <input type="text" id="register-name" name="nome" placeholder="Seu Nome">
            <label for="register-email">Email:</label>
            <input type="email" id="register-email" name="email" placeholder="exemplo@dominio.com" required>
            <label for="register-password">Password:</label>
            <input type="password" id="register-password" name="senha" placeholder="********" required minlength="8">
            <label for="confirm-password">Confirm Password:</label>
            <input type="password" id="confirm-password" name="confirma-senha" placeholder="********" required minlength="8">

            <div class="g-recaptcha" data-sitekey="6Lfi7l8rAAAAAO1di3T4llbZ1BMb_3Git9r0kXLB"></div>

            <button type="submit" name="register">REGISTAR</button>
            <a href="login.php">Já tem conta? Faça login</a>
            <?php if (isset($error_message)) { echo "<div class='error-message'>$error_message</div>"; } ?>
            <?php if (isset($success_message)) { echo "<div class='success-message'>$success_message</div>"; } ?>
        </form>
    </div>
</body>
</html>
