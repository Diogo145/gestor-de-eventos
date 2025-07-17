<?php
include 'db_connection.php';
session_start();

// Proteção, só admin pode aceder
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'admin') {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanMatch - Admin</title>
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
            margin: 50px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .section {
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .section h2 {
            color: #333;
            font-size: 20px;
            margin: 0 0 20px;
        }
        .section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .section ul li {
            margin: 10px 0;
        }
        .section ul li a {
            color: #007bff;
            font-size: 16px;
            text-decoration: none;
        }
        .section ul li a:hover {
            text-decoration: underline;
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

    <div class="dashboard">
        <!-- Gestão de Utilizadores -->
        <div class="section">
            <h2>Gestão de Utilizadores</h2>
            <ul>
                <li><a href="manage-users.php">Gerir Utilizadores (Editar/Remover)</a></li>
            </ul>
        </div>

        <!-- Gestão de Eventos -->
        <div class="section">
            <h2>Gestão de Eventos</h2>
            <ul>
                <li><a href="create-event.php">Criar Evento</a></li>
                <li><a href="manage-events.php">Ver e Gerir Eventos</a></li>
            </ul>
        </div>

        <!-- Gestão de Categorias -->
        <div class="section">
            <h2>Gestão de Categorias</h2>
            <ul>
                <li><a href="manage-categories.php">Gerir Categorias</a></li>
            </ul>
        </div>

        <!-- Gestão de locais -->
        <div class="section">
            <h2>Gestão de locais</h2>
            <ul>
                <li><a href="manage-places.php">Gerir locais</a></li>
            </ul>
        </div>

        <!-- Formulário de contactos -->
        <div class="section">
            <h2>Formulários de Contacto</h2>
            <ul>
                <li><a href="ver-contactos.php">Ver Submissões</a></li>
            </ul>
        </div>

        <!-- Upload de Conteúdos -->
        <div class="section">
            <h2>Upload de Conteúdos</h2>
            <ul>
                <li><a href="upload-content-admin.php">Dar Upload de Conteúdos</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
