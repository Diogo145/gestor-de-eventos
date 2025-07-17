<?php
session_start();
include 'db_connection.php'; // Inclui o arquivo de conexão

// Consulta para buscar eventos
$sql = "SELECT * FROM evento"; 
$result = $conn->query($sql);
?>
<script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // Convert sheet to JSON to filter blank rows
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                // Filter out blank rows (rows where all cells are empty, null, or undefined)
                var filteredData = jsonData.filter(row => row.some(filledCell));

                // Heuristic to find the header row by ignoring rows with fewer filled cells than the next row
                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                // Fallback
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                // Convert filtered JSON back to CSV
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); // Create a new sheet from filtered array of arrays
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script><!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanMatch - Gestão de Eventos</title>
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
        .carousel-container {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            position: relative;
            overflow: hidden;
            height: 300px;
        }
        .carousel {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        .carousel-item {
            min-width: 100%;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            font-size: 32px;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.6);
            position: relative;
        }

        .carousel-item:nth-child(1) {
            background-image: url('uploads/batizados.jpg');
        }
        .carousel-item:nth-child(2) {
            background-image: url('uploads/aniversario.jpg');
        }
        .carousel-item:nth-child(3) {
            background-image: url('uploads/gala.jpg');
        }
        .carousel-controls {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
        }
        .carousel-controls button {
            background-color: rgba(0, 0, 0, 0.5);
            border: none;
            color: white;
            padding: 10px;
            cursor: pointer;
            font-size: 18px;
        }
        .content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 10px;
            text-align: center;
        }
        .content h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .content p {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
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

            <?php if (!isset($_SESSION['perfil'])): ?>
                <a href="login.php">Login</a>
                <a href="register.php">Registe-se</a>
            <?php else: ?>
                <?php if ($_SESSION['perfil'] === 'utilizador'): ?>
                    <a href="user-dashboard.php">Painel</a>
                <?php elseif ($_SESSION['perfil'] === 'simpatizante'): ?>
                    <a href="sympathizer-dashboard.php">Simpatizante</a>
                <?php elseif ($_SESSION['perfil'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Sair</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="carousel-container">
        <div class="carousel" id="carousel">
            <div class="carousel-item">Batizados</div>
            <div class="carousel-item">Festas de Aniversário</div>
            <div class="carousel-item">Jantares de Gala</div>
        </div>
        <div class="carousel-controls">
            <button onclick="prevSlide()">&#10094;</button>
            <button onclick="nextSlide()">&#10095;</button>
        </div>
    </div>

    <div class="content">
        <h2>ORGANIZA E CELEBRA COM FACILIDADE.</h2>
        <p>Organiza os teus eventos de forma fácil, rápida e elegante com a nossa plataforma. Desde aniversários a casamentos, jantares de gala ou festas temáticas, tudo num só lugar. Subscreve em eventos únicos e escolhe o local perfeito. A nossa interface é simples, intuitiva e feita para todos os tipos de utilizadores. Porque cada evento merece ser especial, começa já a planear com a PlanMatch!</p>
    </div>

    <script>
        const carousel = document.getElementById('carousel');
        const items = document.querySelectorAll('.carousel-item');
        let currentIndex = 0;

        function showSlide(index) {
            if (index >= items.length) currentIndex = 0;
            else if (index < 0) currentIndex = items.length - 1;
            else currentIndex = index;
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
        }

        function nextSlide() {
            showSlide(currentIndex + 1);
        }

        function prevSlide() {
            showSlide(currentIndex - 1);
        }

        // Auto-scroll every 5 seconds
        setInterval(nextSlide, 5000);

        // Initial display
        showSlide(currentIndex);
    </script>
</body>
</html>