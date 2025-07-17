<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include 'db_connection.php';
session_start();

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Token inválido.");
}

$token = $_GET['token'];

// Verifica se o token existe na base de dados
$stmt = $conn->prepare("SELECT ID_utilizador FROM utilizador WHERE token_verificacao = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Atualiza o utilizador para marcar como verificado
    $update = $conn->prepare("UPDATE utilizador SET email_verificado = 1, token_verificacao = NULL WHERE ID_utilizador = ?");
    $update->bind_param("i", $user['ID_utilizador']);
    $update->execute();

    echo "<h2> Conta verificada com sucesso!</h2>";
    echo "<p>Já podes fazer <a href='login.php'>login</a>.</p>";

} else {
    echo "<h2> Token inválido ou conta já verificada.</h2>";
}
?>
