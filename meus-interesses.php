<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['simpatizante', 'utilizador'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$preferenciasEscolhidas = false;

// Obter o ID do utilizador
$stmt = $conn->prepare("SELECT ID_utilizador FROM utilizador WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$userId = $user['ID_utilizador'];
$stmt->close();

// Atualizar interesses se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpar interesses antigos
    $conn->query("DELETE FROM interesses_utilizador WHERE ID_utilizador = $userId");

    if (!empty($_POST['categorias'])) {
        foreach ($_POST['categorias'] as $categoriaId) {
            $categoriaId = intval($categoriaId);
            $conn->query("INSERT INTO interesses_utilizador (ID_utilizador, ID_categoria) VALUES ($userId, $categoriaId)");
        }
    }
    $mensagem = "Interesses atualizados com sucesso!";
    $preferenciasEscolhidas = true;
}

// Buscar categorias existentes
$categorias = [];
$res = $conn->query("SELECT ID_categoria, nome FROM categoria ORDER BY nome");
while ($row = $res->fetch_assoc()) {
    $categorias[] = $row;
}

// Buscar categorias de interesse do utilizador
$interesses = [];
$res = $conn->query("SELECT ID_categoria FROM interesses_utilizador WHERE ID_utilizador = $userId");
while ($row = $res->fetch_assoc()) {
    $interesses[] = $row['ID_categoria'];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>PlanMatch - Meus Interesses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .header {
            background-color: #fff;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .nav a {
            margin-left: 15px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .container {
            max-width: 600px; margin: auto; background: #fff;
            padding: 30px; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; }
        form { margin-top: 20px; }
        label {
            display: block; margin-bottom: 10px;
        }
        input[type="checkbox"] { margin-right: 10px; }
        button {
            margin-top: 20px; padding: 10px 20px;
            background-color: #007bff; color: #fff;
            border: none; border-radius: 5px;
            cursor: pointer;
        }
        .message {
            margin-top: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            color: #155724;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">PlanMatch</div>
            <div class="nav">
            <a href="homepage.php">Início</a>
            <a href="events.php">Eventos</a>
            <a href="contact.php">Contactos</a>

            <?php if (!isset($_SESSION['perfil'])): ?>
                <a href="login.php">Login</a>
                <a href="register.php">Registe-se</a>
            <?php else: ?>
                <?php if ($_SESSION['perfil'] === 'utilizador'): ?>
                    <a href="user-dashboard.php">Painel</a>
                <?php elseif ($_SESSION['perfil'] === 'simpatizante'): ?>
                    <a href="sympathizer-dashboard.php">Simpatizante</a>
                <?php elseif ($_SESSION['perfil'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Sair</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
    <h1>Gerir Categorias de Interesse</h1>

    <?php if (isset($mensagem)): ?>
        <div class="message"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>
    <?php if ($preferenciasEscolhidas): ?>
        <script>
            const utilizadorPerfil = '<?= $_SESSION['perfil'] ?>';
            if(utilizadorPerfil === 'simpatizante') {
                setTimeout(function () {
                window.location.href = 'sympathizer-dashboard.php';
            }, 3000);
            } else if(utilizadorPerfil === 'utilizador') {
                setTimeout(function () {
                window.location.href = 'user-dashboard.php';
            }, 3000);
            }
        </script>
    <?php endif; ?>
    <form method="POST">
        <?php foreach ($categorias as $cat): ?>
            <label>
                <input type="checkbox" name="categorias[]" value="<?= $cat['ID_categoria'] ?>"
                    <?= in_array($cat['ID_categoria'], $interesses) ? 'checked' : '' ?>>
                <?= htmlspecialchars($cat['nome']) ?>
            </label>
        <?php endforeach; ?>

        <button type="submit">Guardar Preferências</button>
    </form>
    </div>
</body>
</html>
