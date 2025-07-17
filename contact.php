<?php
include 'db_connection.php';
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);


if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: homepage.php");
    exit();
}

$success_message = '';
$error_message = '';

// Carrega os sítios disponíveis
$sitios = [];
$result = $conn->query("SELECT ID_local, nome FROM sitio ORDER BY nome ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sitios[] = $row;
    }
}

// Carrega tipos de evento (categorias principais)
$categorias = [];
$res = $conn->query("SELECT ID_categoria, nome FROM categoria WHERE tipo = 'principal' ORDER BY nome ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $categorias[] = $row;
    }
}

$name = $email = $phone = $subject = $message = '';
$empresa = $num_pessoas = $data_evento = $tipo_evento = $espaco = $observacoes = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $empresa = trim($_POST['empresa'] ?? '') ?: null;
    $num_pessoas = is_numeric($_POST['num_pessoas'] ?? '') ? intval($_POST['num_pessoas']) : null;
    $data_evento = !empty($_POST['data_evento']) ? $_POST['data_evento'] : null;
    $tipo_evento = trim($_POST['tipo_evento'] ?? '') ?: null;
    $espaco = is_numeric($_POST['espaco'] ?? '') ? intval($_POST['espaco']) : null;
    $observacoes = trim($_POST['observacoes'] ?? '') ?: null;

    $valid = true;

    if (empty($name) || empty($email)) {
        $error_message = "Por favor, preencha os campos obrigatórios: Nome e Email.";
        $valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Por favor, insira um email válido.";
        $valid = false;
    } elseif ($subject === 'Pedido de Informações' && empty($message)) {
        $error_message = "Por favor, escreva a mensagem.";
        $valid = false;
    } elseif ($subject === 'Pedido de Orçamento' && (empty($empresa) || empty($tipo_evento) || empty($espaco))) {
        $error_message = "Por favor, preencha todos os campos do pedido de orçamento.";
        $valid = false;
    }

    if ($valid) {
        $stmt = $conn->prepare("INSERT INTO contacto 
            (nome, email, telefone, assunto, mensagem, data_envio, empresa, num_pessoas, data_evento, tipo_evento, espaco, observacoes)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            die("Erro ao preparar a consulta: " . $conn->error);
        }

        $stmt->bind_param("sssssssssss", 
            $name, $email, $phone, $subject, $message,
            $empresa, $num_pessoas, $data_evento, $tipo_evento, $espaco, $observacoes
        );

        if ($stmt->execute()) {
            $success_message = "Mensagem enviada com sucesso! Entraremos em contato em breve.";
            $name = $email = $phone = $subject = $message = '';
            $empresa = $num_pessoas = $data_evento = $tipo_evento = $espaco = $observacoes = '';
        } else {
            $error_message = "Erro ao guardar: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanMatch - Contactos</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 0; display: flex; flex-direction: column; align-items: center; }
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
        .logo { font-size: 24px; font-weight: bold; color: #333; }
        .nav a { margin-left: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        .contact-section { display: flex; align-items: center; gap: 50px; max-width: 1200px; margin: 30px auto; padding: 20px; }
        .contact-form { background-color: #fff; border: 2px solid #ccc; border-radius: 10px; padding: 20px; width: 400px; }
        .contact-form label { display: block; margin: 10px 0 5px; color: #666; font-size: 14px; }
        .contact-form input, .contact-form select, .contact-form textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .contact-form textarea { height: 100px; resize: none; }
        .contact-form button { background-color: #007bff; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .message { margin-top: 10px; padding: 10px; border-radius: 5px; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
                <a href="?logout=1">Sair</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="contact-section">
        <h2>Pretende agendar um evento ou obter informações? Contacte-nos!</h2>
        <div class="contact-form">
            <form method="POST">
                <label for="name">Nome:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

                <label for="phone">Telefone:</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>">

                <label for="subject">Assunto:</label>
                <select id="subject" name="subject" required onchange="toggleExtraFields(this.value)">
                    <option value="">Selecione...</option>
                    <option value="Pedido de Informações" <?= $subject == "Pedido de Informações" ? 'selected' : '' ?>>Pedido de Informações</option>
                    <option value="Pedido de Orçamento" <?= $subject == "Pedido de Orçamento" ? 'selected' : '' ?>>Pedido de Orçamento</option>
                </select>

                <div id="mensagemField">
                    <label for="message">Mensagem:</label>
                    <textarea id="message" name="message"><?= htmlspecialchars($message) ?></textarea>
                </div>

                <div id="orcamentoFields" style="display: none;">
                    <label for="empresa">Empresa:</label>
                    <input type="text" id="empresa" name="empresa" value="<?= htmlspecialchars($empresa) ?>">

                    <label for="num_pessoas">Nº pessoas:</label>
                    <input type="number" id="num_pessoas" name="num_pessoas" value="<?= htmlspecialchars($num_pessoas) ?>" min="1">

                    <label for="data_evento">Data:</label>
                    <input type="date" id="data_evento" name="data_evento" value="<?= htmlspecialchars($data_evento) ?>">

                    <label for="tipo_evento">Tipo de Evento:</label>
                    <select id="tipo_evento" name="tipo_evento">
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= htmlspecialchars($c['nome']) ?>" <?= $tipo_evento == $c['nome'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="espaco">Espaço:</label>
                    <select id="espaco" name="espaco">
                        <option value="">Selecione...</option>
                        <?php foreach ($sitios as $s): ?>
                            <option value="<?= htmlspecialchars($s['ID_local']) ?>" <?= $espaco == $s['ID_local'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="observacoes">Observações adicionais:</label>
                    <textarea id="observacoes" name="observacoes"><?= htmlspecialchars($observacoes) ?></textarea>
                </div>

                <button type="submit">Enviar</button>

                <?php if ($success_message): ?>
                    <div class="message success-message"><?= $success_message ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="message error-message"><?= $error_message ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
    function toggleExtraFields(value) {
        const orcamentoFields = document.getElementById('orcamentoFields');
        const mensagemField = document.getElementById('mensagemField');

        if (value === 'Pedido de Orçamento') {
            orcamentoFields.style.display = 'block';
            mensagemField.style.display = 'none';
        } else {
            orcamentoFields.style.display = 'none';
            mensagemField.style.display = 'block';
        }
    }

    window.onload = function () {
        toggleExtraFields(document.getElementById('subject').value);
    };
    </script>
</body>
</html>