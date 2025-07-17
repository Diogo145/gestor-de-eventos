<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'utilizador') {
    header("Location: login.php");
    exit();
}

$stmtNotif = $conn->prepare("
    SELECT mensagem, data_criacao 
    FROM notificacao 
    WHERE ID_utilizador = (
        SELECT ID_utilizador FROM utilizador WHERE email = ?
    ) AND lida = 0
    ORDER BY data_criacao DESC
");
$stmtNotif->bind_param("s", $_SESSION['email']);
$stmtNotif->execute();
$notificacoesResult = $stmtNotif->get_result();
$notificacoes = $notificacoesResult->fetch_all(MYSQLI_ASSOC);
$stmtNotif->close();

// Marcar notificações como lidas
$conn->query("
    UPDATE notificacao 
    SET lida = 1 
    WHERE ID_utilizador = (
        SELECT ID_utilizador FROM utilizador WHERE email = '" . $conn->real_escape_string($_SESSION['email']) . "'
    )
");

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard do Utilizador</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; margin-top: 20px ;border-radius: 10px; border: 2px solid #ccc; }
        h1 { text-align: center; color: #333; }
        .notificacoes { margin-top: 20px; }
        .notificacao { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .notificacao:last-child { border-bottom: none; }
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
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .actions a, .actions button {
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .notifications-section {
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 10px;
            padding: 20px;
        }
        .notifications-section h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .notifications-section p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
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
            <a href="logout.php">Log out</a>
        </div>
    </div>
    <div class="container">
        <h1>Bem-vindo ao Painel do Utilizador</h1>
        <div class="actions">
            <a href="meus-interesses.php">Gerir Interesses</a>
        </div>
        <div class="notifications-section">
            <h2>Notificações</h2>
            <?php if (empty($notificacoes)): ?>
                <p>Sem novas notificações.</p>
            <?php else: ?>
                <?php foreach ($notificacoes as $n): ?>
                    <p><?= htmlspecialchars($n['mensagem']) ?> <br>
                        <small><?= date("d/m/Y H:i", strtotime($n['data_criacao'])) ?></small>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
