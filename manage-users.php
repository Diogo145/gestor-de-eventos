<?php
include 'db_connection.php';
session_start();

// Proteção → só admin
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Processar Remover
if (isset($_POST['delete']) && is_numeric($_POST['delete'])) {
    $id_to_delete = (int)$_POST['delete'];
    $stmt = $conn->prepare("DELETE FROM utilizador WHERE ID_utilizador = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $message = "Utilizador removido com sucesso.";
}

// Processar Editar
if (isset($_POST['save_edit'])) {
    $id_edit = (int)$_POST['edit_id'];
    $nome_edit = $_POST['edit_nome'];
    $email_edit = $_POST['edit_email'];
    $perfil_edit = $_POST['edit_perfil'];

    $stmt = $conn->prepare("UPDATE utilizador SET nome = ?, email = ?, perfil = ? WHERE ID_utilizador = ?");
    $stmt->bind_param("sssi", $nome_edit, $email_edit, $perfil_edit, $id_edit);
    $stmt->execute();
    $stmt->close();
    $message = "Utilizador editado com sucesso.";
}

// Carregar utilizadores
$users = [];
$sql = "SELECT ID_utilizador, nome, email, perfil, data_registo FROM utilizador ORDER BY data_registo DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Se clicou em Editar → carrega dados do utilizador
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT ID_utilizador, nome, email, perfil FROM utilizador WHERE ID_utilizador = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    if ($result_edit) {
        $edit_user = $result_edit->fetch_assoc();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>PlanMatch - Gestão de Utilizadores</title>
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
            max-width: 1200px;
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
        .edit-form {
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
        <h1>Gestão de Utilizadores</h1>

        <?php if (isset($message)): ?>
            <p style="color: green;"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Formulário de edição -->
        <?php if ($edit_user): ?>
            <div class="edit-form">
                <h2>Editar Utilizador ID <?php echo $edit_user['ID_utilizador']; ?></h2>
                <form method="POST">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_user['ID_utilizador']; ?>">
                    <p>Nome: <input type="text" name="edit_nome" value="<?php echo htmlspecialchars($edit_user['nome']); ?>" required></p>
                    <p>Email: <input type="email" name="edit_email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required></p>
                    <p>Perfil:
                        <select name="edit_perfil" required>
                            <option value="utilizador" <?php if ($edit_user['perfil']=='utilizador') echo 'selected'; ?>>Utilizador</option>
                            <option value="simpatizante" <?php if ($edit_user['perfil']=='simpatizante') echo 'selected'; ?>>Simpatizante</option>
                            <option value="admin" <?php if ($edit_user['perfil']=='admin') echo 'selected'; ?>>Admin</option>
                        </select>
                    </p>
                    <button type="submit" name="save_edit">Guardar Alterações</button>
                    <a href="manage-users.php" style="margin-left:10px;">Cancelar</a>
                </form>
            </div>
        <?php endif; ?>

        <!-- Tabela de utilizadores -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Data de Registo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['ID_utilizador']; ?></td>
                            <td><?php echo htmlspecialchars($user['nome']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['perfil']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['data_registo'])); ?></td>
                            <td class="action-buttons">
                                <form method="get" action="manage-users.php" style="display:inline;">
                                    <input type="hidden" name="edit" value="<?php echo $user['ID_utilizador']; ?>">
                                    <button type="submit" class="edit-btn">Editar</button>
                                </form>
                                <form method="post" onsubmit="return confirm('Tem a certeza que deseja remover este utilizador?');" style="display:inline;">
                                    <input type="hidden" name="delete" value="<?php echo $user['ID_utilizador']; ?>">
                                    <button type="submit" class="delete-btn">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Nenhum utilizador encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
