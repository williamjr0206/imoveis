<?php
session_start();


// garante que está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/auth.php';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" charset="UTF-8">
<link rel="stylesheet" href="../assets/css/mobile.css">
    <title>Painel - Imóveis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }
        h2 {
            margin-bottom: 10px;
        }
        .menu a {
            display: inline-block;
            margin: 6px 10px 6px 0;
            padding: 10px 15px;
            background: #f0f0f0;
            text-decoration: none;
            border-radius: 4px;
            color: #000;
        }
        .menu a:hover {
            background: #ddd;
        }
        .logout {
            margin-top: 30px;
        }
    </style>
</head>
<body>

<h2>Bem-vindo, <?= htmlspecialchars($_SESSION['nome']) ?></h2>
<p>Perfil: <strong><?= $_SESSION['perfil'] ?></strong></p>

<div class="menu">
    <?php if ($_SESSION['perfil'] === 'ADMIN'): ?>
        <a href="<?= BASE_URL ?>cadastros/usuarios.php">👤 Usuários</a>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>cadastros/proprietarios.php">🏠 Proprietários</a>
    <a href="<?= BASE_URL ?>cadastros/imoveis.php">🏢 Imóveis</a>
    <a href="<?= BASE_URL ?>cadastros/inquilinos.php">👥 Inquilinos</a>
    <a href="<?= BASE_URL ?>cadastros/contratos.php">📄 Contratos</a>
    <a href="<?= BASE_URL ?>cadastros/pagamentos.php">💰 Pagamentos</a>
    <a href="<?= BASE_URL ?>logout.php">🚪 Sair</a>
</div>

</body>
</html>
