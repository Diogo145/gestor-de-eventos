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

// Carregar locais e categorias
$categorias = [];
$sitios = [];

$resCat = $conn->query("SELECT ID_categoria, nome FROM categoria ORDER BY nome ASC");
while ($row = $resCat->fetch_assoc()) {
    $categorias[] = $row;
}

$resSitio = $conn->query("SELECT ID_local, nome FROM sitio ORDER BY nome ASC");
while ($row = $resSitio->fetch_assoc()) {
    $sitios[] = $row;
}

// Remover evento
if (isset($_GET['apagar'])) {
    $id = intval($_GET['apagar']);
    $stmt = $conn->prepare("DELETE FROM evento WHERE ID_evento = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage-events.php");
    exit();
}

// Editar evento
$evento_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $stmt = $conn->prepare("SELECT * FROM evento WHERE ID_evento = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $result = $stmt->get_result();
    $evento_editar = $result->fetch_assoc();
    $stmt->close();
}

// Guardar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $id_evento = intval($_POST['id_evento']);
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $visibilidade = $_POST['visibilidade'];
    $id_local = intval($_POST['local']);
    $id_categoria = intval($_POST['categoria']);

    $stmt = $conn->prepare("UPDATE evento SET titulo = ?, descricao = ?, visibilidade = ?, ID_local = ?, ID_categoria = ? WHERE ID_evento = ?");
    $stmt->bind_param("sssiii", $titulo, $descricao, $visibilidade, $id_local, $id_categoria, $id_evento);
    $stmt->execute();
    $stmt->close();
    header("Location: manage-events.php");
    exit();
}

// Buscar eventos
$sql = "SELECT evento.*, categoria.nome AS categoria_nome, sitio.nome AS sitio_nome 
        FROM evento 
        LEFT JOIN categoria ON evento.ID_categoria = categoria.ID_categoria 
        LEFT JOIN sitio ON evento.ID_local = sitio.ID_local
        ORDER BY evento.ID_evento DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gerir Eventos</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 0; margin: 0; background-color: #f4f4f4; }
        h1 { text-align: center; }
        .edit-box {
            background-color: #e9f5ff;
            border: 1px solid #007bff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .edit-box label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        .edit-box input, .edit-box textarea, .edit-box select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .edit-box button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-box a {
            margin-left: 15px;
            color: #dc3545;
            text-decoration: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        a.btn {
            padding: 5px 10px;
            color: white;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 4px;
        }
        a.btn:hover { background-color: #0056b3; }
        a.del { background-color: #dc3545; }
        a.del:hover { background-color: #a71d2a; }
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
    <h1>Gestão de Eventos</h1>

    <?php if ($evento_editar): ?>
        <div class="edit-box">
            <form method="POST">
                <input type="hidden" name="id_evento" value="<?= $evento_editar['ID_evento'] ?>">

                <label>Título:</label>
                <input type="text" name="titulo" value="<?= htmlspecialchars($evento_editar['titulo']) ?>" required>

                <label>Descrição:</label>
                <textarea name="descricao" required><?= htmlspecialchars($evento_editar['descricao']) ?></textarea>

                <label>Visibilidade:</label>
                <select name="visibilidade" required>
                    <option value="publico" <?= $evento_editar['visibilidade'] === 'publico' ? 'selected' : '' ?>>Público</option>
                    <option value="privado" <?= $evento_editar['visibilidade'] === 'privado' ? 'selected' : '' ?>>Privado</option>
                </select>

                <label>Local:</label>
                <select name="local" required>
                    <option value="">Selecione um local</option>
                    <?php foreach ($sitios as $s): ?>
                        <option value="<?= $s['ID_local'] ?>" <?= $s['ID_local'] == $evento_editar['ID_local'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Categoria:</label>
                <select name="categoria" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['ID_categoria'] ?>" <?= $c['ID_categoria'] == $evento_editar['ID_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="guardar">Guardar Alterações</button>
                <a href="manage-events.php">Cancelar</a>
            </form>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>Local</th>
                <th>Categoria</th>
                <th>Visibilidade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['titulo']) ?></td>
                    <td><?= htmlspecialchars($row['sitio_nome']) ?></td>
                    <td><?= htmlspecialchars($row['categoria_nome'] ?? 'Sem categoria') ?></td>
                    <td><?= $row['visibilidade'] ?></td>
                    <td>
                        <a href="manage-events.php?editar=<?= $row['ID_evento'] ?>" class="btn">Editar</a>
                        <a href="manage-events.php?apagar=<?= $row['ID_evento'] ?>" class="btn del" onclick="return confirm('Tem a certeza que deseja apagar este evento?');">Apagar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
