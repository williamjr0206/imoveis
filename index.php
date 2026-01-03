<?php
session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/auth.php';

// garante que está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
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
        <a href="cadastros/usuarios.php">👤 Usuários</a>
    <?php endif; ?>

    <a href="cadastros/proprietarios.php">🏠 Proprietários</a>
    <a href="cadastros/imoveis.php">🏢 Imóveis</a>
    <a href="cadastros/inquilinos.php">👥 Inquilinos</a>
    <a href="cadastros/contratos.php">📄 Contratos</a>
    <a href="cadastros/pagamentos.php">💰 Pagamentos</a>
</div>

<div class="logout">
    <a href="logout.php">🚪 Sair</a>
</div>

</body>
</html>
