<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include 'db_connection.php';
session_start();

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


// Verifica se é simpatizante ou admin
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['simpatizante', 'admin'])) {
    header("Location: login.php");
    exit();
}

// Aqui futuramente podes carregar eventos, conteúdos e categorias do utilizador
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanMatch - Simpatizante Dashboard</title>
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
        .dashboard {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .dashboard h1 {
            text-align: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .event-card, .content-card {
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .event-card .placeholder-image, .content-card .placeholder-image {
            width: 150px;
            height: 100px;
            background-color: #e0e0e0;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
            text-align: center;
        }
        .details {
            margin-top: 10px;
        }
        .details h3 {
            color: #333;
            font-size: 18px;
            margin: 0;
        }
        .details p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
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

    <div class="dashboard">
        <h1>Simpatizante Dashboard</h1>

        <!-- Conteúdo e funcionalidades do simpatizante -->
        <div class="actions">
            <a href="create-event.php">Criar Evento</a>
            <a href="upload-content.php">Upload Conteúdos</a>
            <a href="manage-categories.php">Criar Categoria Secundária</a>
            <a href="associate-content.php">Associar Meta-Info</a>
            <a href="manage-users.php">Editar Perfil</a>
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

        <!-- Placeholder de conteúdos -->
        <div class="content-card">
            <div class="placeholder-image">Imagem/Vídeo/Audio</div>
            <div class="details">
                <h3>Conteúdo de Exemplo</h3>
                <p>Categoria: Casamentos</p>
                <p>Visibilidade: Privada</p>
            </div>
            <div class="actions">
                <button>Ver</button>
                <button>Editar</button>
                <button>Eliminar</button>
            </div>
        </div>
    </div>
</body>
</html>
