<?php
include 'db_connection.php';
session_start();

// Verifica se é admin ou simpatizante
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['admin', 'simpatizante'])) {
    header("Location: login.php");
    exit();
}

// Verifica se a conexão está ativa
if (!$conn->ping()) {
    die("Conexão fechada ou inválida!");
}

// Busca os sitios existentes
$sitios = [];
$sitio_query = "SELECT ID_local, nome FROM sitio";
$result_sitio = $conn->query($sitio_query);
if ($result_sitio) {
    while ($row = $result_sitio->fetch_assoc()) {
        $sitios[] = $row;
    }
}

// Processa o form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['title'];
    $descricao = $_POST['description'];
    $data_inicio = $_POST['start-date'];
    $visibilidade = $_POST['location-type'] === 'public' ? 'publico' : 'privado';
    $meta_info = $_POST['meta-info'];
    $id_sitio = $_POST['sitio'];

    // Busca ID do utilizador
    $stmt_user = $conn->prepare("SELECT ID_utilizador FROM utilizador WHERE email = ?");
    $stmt_user->bind_param("s", $_SESSION['email']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $userId = $user['ID_utilizador'];
    $stmt_user->close();

    // Insere evento
    $stmt = $conn->prepare("INSERT INTO evento (titulo, descricao, data_inicio, visibilidade, ID_utilizador, ID_local, meta_info) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $titulo, $descricao, $data_inicio, $visibilidade, $userId, $id_sitio, $meta_info);
    $stmt->execute();
    $id_evento = $stmt->insert_id;
    $stmt->close();

    // Upload de conteúdos unitários
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    foreach (['imagem', 'video', 'audio'] as $fileField) {
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

    // Upload por lote (ZIP)
    if (!empty($_FILES['zipfile']['name'])) {
        $zipPath = $targetDir . time() . '_' . basename($_FILES['zipfile']['name']);
        if (move_uploaded_file($_FILES['zipfile']['tmp_name'], $zipPath)) {
            $extractDir = $targetDir . "lote_" . time();
            $zip = new ZipArchive;
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($extractDir);
                $zip->close();
                $message = "ZIP extraído com sucesso!";
            } else {
                $message = "Erro ao extrair ZIP.";
            }
        }
    }

    header("Location: events.php");
    exit();
}
?>
