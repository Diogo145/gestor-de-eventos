<?php
include 'db_connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['senha'];

    // Validação da password no servidor (mínimo 8 caracteres)
    if (strlen($password) < 8) {
        $error_message = "A password deve ter pelo menos 8 caracteres.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM utilizador WHERE email = ?");
        if ($stmt === false) {
            die("Erro ao preparar a consulta: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($password === $row['senha']) { 
                $_SESSION['email'] = $email;
                $_SESSION['perfil'] = $row['perfil'];

                if ($row['perfil'] === 'simpatizante') {
                    header("Location: sympathizer-dashboard.php");
                } elseif ($row['perfil'] === 'admin') {
                    header("Location: admin.php");
                } elseif ($row['perfil'] === 'utilizador') {
                    header("Location: events.php");
                } else {
                    $error_message = "Perfil inválido!";
                }
                exit();
            } else {
                $error_message = "Email ou senha inválidos!";
            }
        } else {
            $error_message = "Email ou senha inválidos!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanMatch - Login</title>
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
        .error-message { color: red; font-size: 14px; margin-top: 10px; }
    </style>
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
        <h2>Log In</h2>
        <form method="POST">
            <label for="login-email">Email:</label>
            <input type="email" id="login-email" name="email" placeholder="exemplo@dominio.com" required>
            <label for="login-password">Password:</label>
            <input type="password" id="login-password" name="senha" placeholder="********" required minlength="8">
            <button type="submit" name="login">LOG IN</button>
            <a href="#">Forgot Password?</a>
            <br>
            <a href="">Ainda não tem conta? Registe-se!</a>
            <button type="button" onclick="window.location.href='register.php'">Registar</button>
            <?php if (isset($error_message)) { echo "<div class='error-message'>$error_message</div>"; } ?>
        </form>
    </div>
</body>
</html>
