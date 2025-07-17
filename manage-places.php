<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';
session_start();

// Proteção → admin ou simpatizante
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['admin', 'simpatizante'])) {
    header("Location: login.php");
    exit();
}

// Processar Remover
if (isset($_POST['delete']) && is_numeric($_POST['delete'])) {
    $id_to_delete = (int)$_POST['delete'];
    $stmt = $conn->prepare("DELETE FROM sitio WHERE ID_local = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $message = "Sitio removido com sucesso.";
}

// Processar Editar
if (isset($_POST['save_edit'])) {
    $id_edit = (int)$_POST['edit_id'];
    $nome_edit = trim($_POST['edit_nome'] ?? '');
    $morada_edit = trim($_POST['edit_morada'] ?? '');
    $cidade_edit = trim($_POST['edit_cidade'] ?? '');
    $capacidade_edit = isset($_POST['edit_capacidade']) ? (int)$_POST['edit_capacidade'] : 0;
    $latitude_edit = $_POST['edit_latitude'] ?? null;
    $longitude_edit = $_POST['edit_longitude'] ?? null;

    if ($nome_edit === '' || $morada_edit === '' || $cidade_edit === '') {
        $message = "Erro: Nome, morada e cidade são obrigatórios.";
    } else {
        // Verifica se há imagem
        if (!empty($_FILES['imagem']['name'])) {
            $nomeImagem = basename($_FILES['imagem']['name']);
            $ficheiroPathImagem = 'uploads/' . time() . '_edit_' . $nomeImagem;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $ficheiroPathImagem)) {
                $stmt = $conn->prepare("UPDATE sitio SET nome = ?, morada = ?, cidade = ?, capacidade = ?, latitude = ?, longitude = ?, imagem = ? WHERE ID_local = ?");
                $stmt->bind_param("sssiddssi", $nome_edit, $morada_edit, $cidade_edit, $capacidade_edit, $latitude_edit, $longitude_edit, $ficheiroPathImagem, $id_edit);
            }
        } else {
            $stmt = $conn->prepare("UPDATE sitio SET nome = ?, morada = ?, cidade = ?, capacidade = ?, latitude = ?, longitude = ? WHERE ID_local = ?");
            $stmt->bind_param("sssiddi", $nome_edit, $morada_edit, $cidade_edit, $capacidade_edit, $latitude_edit, $longitude_edit, $id_edit);
        }

        if (isset($stmt)) {
            $stmt->execute();
            $stmt->close();
            $message = "Sitio editado com sucesso.";
        }
    }
}


// Processar Nova Sitio
if (isset($_POST['create_sitio'])) {
    $nome_novo = $_POST['new_nome'];
    $morada_novo = $_POST['new_morada'];
    $cidade_novo = $_POST['new_cidade'];
    $capacidade_novo = (int)$_POST['new_capacidade'];
    $latitude_novo = $_POST['new_latitude'];
    $longitude_novo = $_POST['new_longitude'];

    $stmt = $conn->prepare("INSERT INTO sitio (nome, morada, cidade, capacidade, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssidd", $nome_novo, $morada_novo, $cidade_novo, $capacidade_novo, $latitude_novo, $longitude_novo);
    $stmt->execute();
    $stmt->close();
    $message = "Novo sitio criado com sucesso.";
}

// Carregar sitios
$sitios = [];
$sql = "SELECT * FROM sitio ORDER BY ID_local DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sitios[] = $row;
    }
}

// Se clicou em Editar → carrega dados do sitio
$edit_sitio = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM sitio WHERE ID_local = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    if ($result_edit) {
        $edit_sitio = $result_edit->fetch_assoc();
    }
    $stmt->close();
}

?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>PlanMatch - Gestão de Sitios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; }
        .header { background-color: #fff; border-bottom: 1px solid #ddd; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; }
        .logo { font-size: 24px; font-weight: bold; color: #333; }
        .nav a { margin-left: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; background-color: #fff; border: 2px solid #ccc; border-radius: 10px; }
        .container h1 { text-align: center; color: #333; font-size: 24px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { text-align: center; padding: 10px; }
        th { background-color: #f5f5f5; }
        .action-buttons form { display: inline-block; }
        .action-buttons button { margin: 0 3px; padding: 6px 12px; border-radius: 5px; font-size: 14px; border: none; cursor: pointer; }
        .edit-btn { background-color: #28a745; color: white; }
        .delete-btn { background-color: #dc3545; color: white; }
        .edit-form, .create-form { margin: 20px 0; padding: 15px; background-color: #eef; border: 2px solid #99f; border-radius: 8px; }
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

    <div class="container">
        <h1>Gestão de Sitios</h1>

        <?php if (isset($message)): ?>
            <p style="color: green;"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Formulário de Novo Sitio -->
        <div class="create-form">
            <h2>Criar Novo Sitio</h2>
            <form method="POST">
                <p>Nome: <input type="text" name="new_nome" required></p>
                <p>Morada: <input type="text" name="new_morada" required></p>
                <p>Cidade: <input type="text" name="new_cidade" required></p>
                <p>Capacidade: <input type="number" name="new_capacidade" required></p>
                <p>Latitude: <input type="text" name="new_latitude"></p>
                <p>Longitude: <input type="text" name="new_longitude"></p>
                <p>Imagem (opcional): <input type="file" name="imagem"></p>
                <button type="submit" name="create_sitio">Criar Sitio</button>
            </form>
        </div>

        <!-- Formulário de Edição -->
        <?php if ($edit_sitio): ?>
            <div class="edit-form">
                <h2>Editar Sitio ID <?php echo $edit_sitio['ID_local']; ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_sitio['ID_local']; ?>">
                    <p>Nome: <input type="text" name="edit_nome" value="<?php echo htmlspecialchars($edit_sitio['nome']); ?>" required></p>
                    <p>Morada: <input type="text" name="edit_morada" value="<?php echo htmlspecialchars($edit_sitio['morada']); ?>" required></p>
                    <p>Cidade: <input type="text" name="edit_cidade" value="<?php echo htmlspecialchars($edit_sitio['cidade']); ?>" required></p>
                    <p>Capacidade: <input type="number" name="edit_capacidade" value="<?php echo $edit_sitio['capacidade']; ?>" required></p>
                    <p>Latitude: <input type="text" name="edit_latitude" value="<?php echo $edit_sitio['latitude']; ?>"></p>
                    <p>Longitude: <input type="text" name="edit_longitude" value="<?php echo $edit_sitio['longitude']; ?>"></p>
                    <button type="submit" name="save_edit">Guardar Alterações</button>
                    <a href="manage-places.php" style="margin-left:10px;">Cancelar</a>
                </form>
            </div>
        <?php endif; ?>

        <!-- Tabela de Sitios -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Morada</th>
                    <th>Cidade</th>
                    <th>Capacidade</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sitios)): ?>
                    <?php foreach ($sitios as $sitio): ?>
                        <tr>
                            <td><?php echo $sitio['ID_local']; ?></td>
                            <td><?php echo htmlspecialchars($sitio['nome']); ?></td>
                            <td><?php echo htmlspecialchars($sitio['morada']); ?></td>
                            <td><?php echo htmlspecialchars($sitio['cidade']); ?></td>
                            <td><?php echo $sitio['capacidade']; ?></td>
                            <td><?php echo $sitio['latitude']; ?></td>
                            <td><?php echo $sitio['longitude']; ?></td>
                            <td class="action-buttons">
                                <form method="get" action="manage-places.php" style="display:inline;">
                                    <input type="hidden" name="edit" value="<?php echo $sitio['ID_local']; ?>">
                                    <button type="submit" class="edit-btn">Editar</button>
                                </form>
                                <form method="post" onsubmit="return confirm('Tem a certeza que deseja remover este sitio?');" style="display:inline;">
                                    <input type="hidden" name="delete" value="<?php echo $sitio['ID_local']; ?>">
                                    <button type="submit" class="delete-btn">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Nenhum sitio encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
