<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db_connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Local inválido.");
}

$id_local = intval($_GET['id']);

// Buscar nome e coordenadas do local
$stmt = $conn->prepare("SELECT nome, latitude, longitude FROM sitio WHERE ID_local = ?");
$stmt->bind_param("i", $id_local);
$stmt->execute();
$res = $stmt->get_result();
$local = $res->fetch_assoc();
$stmt->close();

if (!$local) {
    die("Local não encontrado.");
}

// Buscar eventos do local com descrição
$stmt = $conn->prepare("SELECT titulo, descricao FROM evento WHERE ID_local = ?");
$stmt->bind_param("i", $id_local);
$stmt->execute();
$res = $stmt->get_result();
$eventos = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Buscar conteúdos (ex: fotos) do local
$stmt = $conn->prepare("SELECT titulo, ficheiro_path, visibilidade, data_envio FROM conteudo WHERE ID_local = ?");
$stmt->bind_param("i", $id_local);
$stmt->execute();
$result = $stmt->get_result();
$conteudos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Galeria - <?= htmlspecialchars($local['nome']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        h1 { text-align: center; color: #333; }
        .descricao { max-width: 800px; margin: 10px auto 30px; color: #555; font-size: 15px; text-align: center; }
        .galeria {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; max-width: 1200px; margin: auto;
        }
        .card {
            background: #fff; padding: 15px; border-radius: 10px;
            border: 1px solid #ccc; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card img {
            max-width: 100%; height: 180px; object-fit: cover;
            border-radius: 6px;
        }
        .card h3 {
            margin-top: 10px; font-size: 16px; color: #444;
        }
        .card p {
            margin: 5px 0; font-size: 13px; color: #666;
        }
        #map {
            width: 100%;
            height: 400px;
            margin-top: 30px;
            border-radius: 10px;
            border: 1px solid #ccc;
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBklXgvJHdJMKEf5pDFAY2iokwbf8Ibn48"></script>
    <script>
        function initMap() {
            var local = { lat: <?= floatval($local['latitude']) ?>, lng: <?= floatval($local['longitude']) ?> };
            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: local
            });
            new google.maps.Marker({
                position: local,
                map: map,
                title: "<?= htmlspecialchars($local['nome']) ?>"
            });
        }
    </script>
</head>
<body onload="initMap()">
    <h1>Galeria - <?= htmlspecialchars($local['nome']) ?></h1>

    <?php if (!empty($eventos)): ?>
        <div class="descricao">
            <?php foreach ($eventos as $evento): ?>
                <h3><?= htmlspecialchars($evento['titulo']) ?></h3>
                <p><?= isset($evento['descricao']) ? nl2br(htmlspecialchars($evento['descricao'])) : '<em>(sem descrição)</em>' ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="galeria">
        <?php foreach ($conteudos as $conteudo): ?>
            <div class="card">
                <?php if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $conteudo['ficheiro_path'])): ?>
                    <img src="<?= htmlspecialchars($conteudo['ficheiro_path']) ?>" alt="Imagem">
                <?php else: ?>
                    <p>[Ficheiro não é imagem]</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="map"></div>
</body>
</html>
