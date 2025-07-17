<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';
session_start();

if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['admin', 'simpatizante'])) {
    header("Location: login.php");
    exit();
}

$sitios = [];
$categorias = [];
$eventoCriado = false;

$result_sitio = $conn->query("SELECT ID_local, nome FROM sitio");
while ($row = $result_sitio->fetch_assoc()) {
    $sitios[] = $row;
}

$result_categoria = $conn->query("SELECT ID_categoria, nome FROM categoria ORDER BY nome");
while ($row = $result_categoria->fetch_assoc()) {
    $categorias[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['title'];
    $descricao = $_POST['description'];
    $visibilidade = $_POST['location-type'] === 'public' ? 'publico' : 'privado';
    $meta_info = $_POST['meta-info'];
    $id_sitio = intval($_POST['sitio']);
    $id_categoria = intval($_POST['categoria']);

    $stmt_user = $conn->prepare("SELECT ID_utilizador FROM utilizador WHERE email = ?");
    $stmt_user->bind_param("s", $_SESSION['email']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $userId = $user['ID_utilizador'];
    $stmt_user->close();

    $stmt = $conn->prepare("INSERT INTO evento (titulo, descricao, visibilidade, ID_utilizador, ID_local, meta_info, ID_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissi", $titulo, $descricao, $visibilidade, $userId, $id_sitio, $meta_info, $id_categoria);
    $stmt->execute();
    $id_evento = $stmt->insert_id;
    $stmt->close();

    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Uploads do evento (imagem e vídeo)
    foreach (['imagem', 'video'] as $fileField) {
        if (!empty($_FILES[$fileField]['name'])) {
            $nomeOriginal = basename($_FILES[$fileField]['name']);
            $ficheiroPath = $targetDir . time() . '_' . $nomeOriginal;

            if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $ficheiroPath)) {
                $stmtConteudo = $conn->prepare("INSERT INTO conteudo (titulo, ficheiro_path, visibilidade, data_envio, ID_evento) VALUES (?, ?, ?, NOW(), ?)");
                $stmtConteudo->bind_param("sssi", $nomeOriginal, $ficheiroPath, $visibilidade, $id_evento);
                $stmtConteudo->execute();
                $stmtConteudo->close();
            }
        }
    }

    // Upload da imagem do local (salvo diretamente na tabela sitio)
    if (!empty($_FILES['imagem']['name'])) {
        $nomeOriginalLocal = basename($_FILES['imagem']['name']);
        $ficheiroPathLocal = $targetDir . time() . '_local_' . $nomeOriginalLocal;

        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $ficheiroPathLocal)) {
            $stmtUpdateLocal = $conn->prepare("UPDATE sitio SET imagem = ? WHERE ID_local = ?");
            $stmtUpdateLocal->bind_param("si", $ficheiroPathLocal, $id_sitio);
            $stmtUpdateLocal->execute();
            $stmtUpdateLocal->close();
        }
    }
    $eventoCriado = true;
    // Enviar notificações para simpatizantes com interesse na categoria
    $notificacaoSQL = "
        SELECT u.ID_utilizador, u.email
        FROM utilizador u
        JOIN interesses_utilizador i ON u.ID_utilizador = i.ID_utilizador
        WHERE i.ID_categoria = ? AND u.perfil IN ('simpatizante', 'utilizador')
    ";
    $stmtNotif = $conn->prepare($notificacaoSQL);
    $stmtNotif->bind_param("i", $id_categoria);
    $stmtNotif->execute();
    $resultNotif = $stmtNotif->get_result();

    $mensagemTexto = "Novo evento disponível na categoria que lhe interessa: '$titulo'.";

    while ($row = $resultNotif->fetch_assoc()) {
        $id_user_interessado = $row['ID_utilizador'];

        $stmtInsert = $conn->prepare("INSERT INTO notificacao (ID_utilizador, ID_evento, mensagem) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("iis", $id_user_interessado, $id_evento, $mensagemTexto);
        $stmtInsert->execute();
        $stmtInsert->close();
    }

    $stmtNotif->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Criar Novo Evento</title>
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
        .container {
            max-width: 800px; margin: auto; background: #fff;
            padding: 30px; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        h1 { text-align: center; color: #333; }
        form label {
            display: block; margin-top: 15px; font-weight: bold;
        }
        form input[type="text"],
        form textarea,
        form select {
            width: 100%; padding: 10px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        form input[type="file"] { margin-top: 5px; }
        form input[type="radio"] { margin-right: 5px; }
        button {
            margin-top: 20px; padding: 10px 20px;
            background-color: #007bff; border: none;
            color: white; border-radius: 5px;
            cursor: pointer; font-size: 16px;
        }
        button:hover { background-color: #0056b3; }
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
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">PlanMatch</div>
        <div class="nav">
            <a href="homepage.php">Início</a>
            <a href="events.php">Eventos</a>
            <a href="contact.php">Contactos</a>
            <a href="admin.php">Admin</a>
            <a href="logout.php">Log out</a>
        </div>
    </div>
    <?php if ($eventoCriado): ?>
        <div class="success-message">
            Evento criado com sucesso! Redirecionando para a página do administrador...
        </div>
        <script>
            setTimeout(function () {
                window.location.href = 'admin.php';
            }, 3000);
        </script>
    <?php endif; ?>
    <div class="container">
    <h1>Criar Novo Evento</h1>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Título:</label>
        <input type="text" name="title" required>

        <label for="description">Descrição:</label>
        <textarea name="description" required></textarea>

        <label>Visibilidade:</label>
        <input type="radio" name="location-type" value="public" required> Público
        <input type="radio" name="location-type" value="private" required> Privado

        <label for="categoria">Categoria:</label>
        <select name="categoria" required>
            <option value="">-- Selecione --</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['ID_categoria']; ?>"><?= htmlspecialchars($cat['nome']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="sitio">Local:</label>
        <select name="sitio" required>
            <option value="">-- Selecione --</option>
            <?php foreach ($sitios as $s): ?>
                <option value="<?= $s['ID_local']; ?>"><?= htmlspecialchars($s['nome']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="imagem">Imagem do Evento:</label>
        <input type="file" name="imagem" id="imagem" accept="image/*">

        <label for="video">Vídeo do Evento:</label>
        <input type="file" name="video" accept="video/*">

        <label for="imagem_local">Imagem do Local (opcional):</label>
        <input type="file" name="imagem_local" accept="image/*">

        <label for="meta-info">Meta Informação:</label>
        <textarea name="meta-info"></textarea>

        <button type="submit">Criar Evento</button>
    </form>
    </div>
</body>
</html>
