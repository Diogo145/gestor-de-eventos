<?php
include 'db_connection.php';
session_start();

// Proteção só admin
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Processar Remover
if (isset($_POST['delete']) && is_numeric($_POST['delete'])) {
    $id_to_delete = (int)$_POST['delete'];
    $stmt = $conn->prepare("DELETE FROM categoria WHERE ID_categoria = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $message = "Categoria removida com sucesso.";
}

// Processar Editar
if (isset($_POST['save_edit'])) {
    $id_edit = (int)$_POST['edit_id'];
    $nome_edit = $_POST['edit_nome'];
    $tipo_edit = $_POST['edit_tipo'];

    $stmt = $conn->prepare("UPDATE categoria SET nome = ?, tipo = ? WHERE ID_categoria = ?");
    $stmt->bind_param("ssi", $nome_edit, $tipo_edit, $id_edit);
    $stmt->execute();
    $stmt->close();
    $message = "Categoria editada com sucesso.";
}

// Processar Nova Categoria
if (isset($_POST['create_category'])) {
    $nome_novo = $_POST['new_nome'];
    $tipo_novo = $_POST['new_tipo'];

    $stmt = $conn->prepare("INSERT INTO categoria (nome, tipo) VALUES (?, ?)");
    $stmt->bind_param("ss", $nome_novo, $tipo_novo);
    $stmt->execute();
    $stmt->close();
    $message = "Nova categoria criada com sucesso.";
}

// Carregar categorias
$categories = [];
$sql = "SELECT ID_categoria, nome, tipo FROM categoria ORDER BY ID_categoria DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Editar carrega dados da categoria
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT ID_categoria, nome, tipo FROM categoria WHERE ID_categoria = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    if ($result_edit) {
        $edit_category = $result_edit->fetch_assoc();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>PlanMatch - Gestão de Categorias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
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
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 10px;
        }
        .container h1 {
            text-align: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            text-align: center;
            padding: 10px;
        }
        th {
            background-color: #f5f5f5;
        }
        .action-buttons form {
            display: inline-block;
        }
        .action-buttons button {
            margin: 0 3px;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .edit-btn {
            background-color: #28a745;
            color: white;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .edit-form, .create-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #eef;
            border: 2px solid #99f;
            border-radius: 8px;
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

    <div class="container">
        <h1>Gestão de Categorias</h1>

        <?php if (isset($message)): ?>
            <p style="color: green;"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Formulário de Nova Categoria -->
        <div class="create-form">
            <h2>Criar Nova Categoria</h2>
            <form method="POST">
                <p>Nome: <input type="text" name="new_nome" required></p>
                <p>Tipo:
                    <select name="new_tipo" required>
                        <option value="principal">Principal</option>
                        <option value="secundaria">Secundária</option>
                    </select>
                </p>
                <button type="submit" name="create_category">Criar Categoria</button>
            </form>
        </div>

        <!-- Formulário de Edição -->
        <?php if ($edit_category): ?>
            <div class="edit-form">
                <h2>Editar Categoria ID <?php echo $edit_category['ID_categoria']; ?></h2>
                <form method="POST">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_category['ID_categoria']; ?>">
                    <p>Nome: <input type="text" name="edit_nome" value="<?php echo htmlspecialchars($edit_category['nome']); ?>" required></p>
                    <p>Tipo:
                        <select name="edit_tipo" required>
                            <option value="principal" <?php if ($edit_category['tipo']=='principal') echo 'selected'; ?>>Principal</option>
                            <option value="secundaria" <?php if ($edit_category['tipo']=='secundaria') echo 'selected'; ?>>Secundária</option>
                        </select>
                    </p>
                    <button type="submit" name="save_edit">Guardar Alterações</button>
                    <a href="manage-categories.php" style="margin-left:10px;">Cancelar</a>
                </form>
            </div>
        <?php endif; ?>

        <!-- Tabela de Categorias -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['ID_categoria']; ?></td>
                            <td><?php echo htmlspecialchars($category['nome']); ?></td>
                            <td><?php echo htmlspecialchars($category['tipo']); ?></td>
                            <td class="action-buttons">
                                <form method="get" action="manage-categories.php" style="display:inline;">
                                    <input type="hidden" name="edit" value="<?php echo $category['ID_categoria']; ?>">
                                    <button type="submit" class="edit-btn">Editar</button>
                                </form>
                                <form method="post" onsubmit="return confirm('Tem a certeza que deseja remover esta categoria?');" style="display:inline;">
                                    <input type="hidden" name="delete" value="<?php echo $category['ID_categoria']; ?>">
                                    <button type="submit" class="delete-btn">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nenhuma categoria encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
