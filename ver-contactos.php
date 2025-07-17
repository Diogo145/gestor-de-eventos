<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'admin') {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT * FROM contacto ORDER BY data_envio DESC");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Formulários de Contacto</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; border: 2px solid #ccc; }
        h1 { text-align: center; }
        .contacto { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .contacto:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Submissões de Contacto</h1>
        <?php if ($result->num_rows === 0): ?>
            <p>Nenhuma submissão encontrada.</p>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="contacto">
                    <strong>Nome:</strong> <?= htmlspecialchars($row['nome']) ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($row['email']) ?><br>
                    <strong>Telefone:</strong> <?= htmlspecialchars($row['telefone']) ?><br>
                    <strong>Assunto:</strong> <?= htmlspecialchars($row['assunto']) ?><br>
                    <strong>Mensagem:</strong> <?= nl2br(htmlspecialchars($row['mensagem'])) ?><br>
                    <strong>Data:</strong> <?= $row['data_envio'] ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>
</html>
