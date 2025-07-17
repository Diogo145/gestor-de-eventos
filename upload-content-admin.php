<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db_connection.php';

if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$uploadFeito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ficheiro'])) {
    $visibilidade = $_POST['visibilidade'] ?? 'privado';
    $tituloConteudo = $_POST['titulo'] ?? 'Sem título';
    $id_sitio = isset($_POST['sitio']) ? intval($_POST['sitio']) : null;

    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $ficheiro = $_FILES['ficheiro'];
    $nomeOriginal = basename($ficheiro['name']);
    $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    $ficheiroPath = $targetDir . time() . '_' . $nomeOriginal;

    if ($ext === 'zip') {
        if (move_uploaded_file($ficheiro['tmp_name'], $ficheiroPath)) {
            $zip = new ZipArchive;
            if ($zip->open($ficheiroPath) === TRUE) {
                $extractPath = $targetDir . 'unzipped_' . time() . '/';
                mkdir($extractPath, 0777, true);
                $zip->extractTo($extractPath);
                $zip->close();

                $validExt = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'webm'];
                foreach (scandir($extractPath) as $file) {
                    $filePath = $extractPath . $file;
                    if (is_file($filePath)) {
                        $extLocal = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($extLocal, $validExt)) {
                            $newPath = $targetDir . time() . '_' . $file;
                            rename($filePath, $newPath);

                            $stmt = $conn->prepare("INSERT INTO conteudo (titulo, ficheiro_path, visibilidade, data_envio, ID_local) VALUES (?, ?, ?, NOW(), ?)");
                            $stmt->bind_param("sssi", $file, $newPath, $visibilidade, $id_sitio);
                            if (!$stmt->execute()) {
                                error_log("Erro ao inserir ficheiro $file: " . $stmt->error);
                            }
                            $stmt->close();
                        }
                    }
                }

                $mensagem = "ZIP extraído e conteúdos carregados com sucesso.";
                $uploadFeito = true;
            } else {
                $mensagem = "Erro ao abrir o ficheiro ZIP.";
            }
        }
    } else {
        if (move_uploaded_file($ficheiro['tmp_name'], $ficheiroPath)) {
            $stmt = $conn->prepare("INSERT INTO conteudo (titulo, ficheiro_path, visibilidade, data_envio, ID_local) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->bind_param("sssi", $tituloConteudo, $ficheiroPath, $visibilidade, $id_sitio);
            $stmt->execute();
            $stmt->close();
            $mensagem = "Ficheiro carregado com sucesso.";
            $uploadFeito = true;
        } else {
            $mensagem = "Erro ao carregar o ficheiro.";
        }
    }
}

// Buscar sitios para o dropdown
$sitios = [];
$res = $conn->query("SELECT ID_local, nome FROM sitio ORDER BY nome");
while ($row = $res->fetch_assoc()) {
    $sitios[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Upload de Conteúdos - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; }
        .container {
            max-width: 600px; margin: auto; background: #fff;
            padding: 30px; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type="text"], select, input[type="file"] {
            width: 100%; padding: 10px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            margin-top: 20px; padding: 10px 20px;
            background: #007bff; color: #fff;
            border: none; border-radius: 5px;
            cursor: pointer; font-size: 16px;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #e0ffe0;
            border: 1px solid #a0d0a0;
            color: #006400;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Upload de Conteúdos</h1>

    <?php if ($mensagem): ?>
        <div class="message"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>
    <?php if ($uploadFeito): ?>
        <script>
            setTimeout(function () {
                window.location.href = "admin.php";
            }, 2000);
        </script>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="titulo">Título do conteúdo:</label>
        <input type="text" name="titulo" id="titulo" required>

        <label for="ficheiro">Escolher ficheiro (imagem, vídeo ou ZIP):</label>
        <input type="file" name="ficheiro" accept=".jpg,.jpeg,.png,.gif,.mp4,.mov,.webm,.zip" required>

        <label for="sitio">Associar a Sítio:</label>
        <select name="sitio" id="sitio" required>
            <option value="">-- Selecione um sítio --</option>
            <?php foreach ($sitios as $s): ?>
                <option value="<?= $s['ID_local'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="visibilidade">Visibilidade:</label>
        <select name="visibilidade" required>
            <option value="publico">Público</option>
            <option value="privado">Privado</option>
        </select>

        <button type="submit">Carregar</button>
    </form>
</div>
</body>
</html>
