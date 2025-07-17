<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connection.php';

if (!$conn->ping()) {
    die("Conexão fechada ou inválida!");
}

// Buscar eventos com info de local e categoria, incluindo imagem do local
$sql = "SELECT 
                evento.ID_evento, evento.titulo, evento.visibilidade,
                categoria.nome AS categoria_nome,
                sitio.ID_local, sitio.nome AS sitio_nome, sitio.capacidade, sitio.imagem
            FROM evento
            LEFT JOIN categoria ON evento.ID_categoria = categoria.ID_categoria
            LEFT JOIN sitio ON evento.ID_local = sitio.ID_local";

if (!isset($_SESSION['perfil'])) {
    $sql .= " WHERE evento.visibilidade = 'publico'";
}

$sql .= " ORDER BY categoria.nome, evento.ID_evento DESC";

$result = $conn->query($sql);

$eventosPorCategoria = [];

while ($evento = $result->fetch_assoc()) {
    $categoria = $evento['categoria_nome'] ?? 'Outros';
    $eventosPorCategoria[$categoria][] = $evento;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>PlanMatch - Eventos</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
        .header {
            background: #fff; border-bottom: 1px solid #ddd;
            padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;
        }
        .logo { font-size: 24px; font-weight: bold; }
        .nav a {
            margin-left: 15px; text-decoration: none; color: #007bff; font-weight: bold;
        }
        .section {
            max-width: 1200px; margin: 30px auto; padding: 0 20px;
        }
        h2 {
            color: #333; border-left: 5px solid #007bff; padding-left: 10px; margin-top: 40px;
        }
        .cards {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px; margin-top: 20px;
        }
        .card {
            background: #fff; border: 2px solid #ccc; border-radius: 10px;
            overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card img {
            width: 100%; height: 160px; object-fit: cover;
        }
        .card-content {
            padding: 15px;
        }
        .card h3 { margin-top: 0; font-size: 18px; color: #333; }
        .card p { font-size: 14px; color: #555; margin-bottom: 5px; }
        .card a {
            display: inline-block; margin-top: 10px; padding: 8px 12px;
            background-color: #007bff; color: #fff; text-decoration: none;
            border-radius: 5px; font-size: 14px;
        }
        .card a:hover {
            background-color: #0056b3;
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

<div class="section">
<?php foreach ($eventosPorCategoria as $categoria => $eventos): ?>
    <h2><?php echo htmlspecialchars($categoria); ?></h2>
    <div class="cards">
        <?php foreach ($eventos as $evento): ?>
            <div class="card">
                <?php if (!empty($evento['imagem'])): ?>
                    <img src="<?php echo htmlspecialchars($evento['imagem']); ?>" alt="Imagem do Local">
                <?php else: ?>
                    <img src="placeholder.jpg" alt="Sem imagem disponível">
                <?php endif; ?>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                    <p><strong>Local:</strong> <?php echo htmlspecialchars($evento['sitio_nome']); ?></p>
                    <p><strong>Capacidade:</strong> <?php echo htmlspecialchars($evento['capacidade']) . " pessoas"; ?></p>
                    <a href="galeria-local.php?id=<?php echo $evento['ID_local']; ?>">Ver mais</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>
</body>
</html>
