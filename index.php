<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$nome   = $_SESSION['nome'];
$perfil = $_SESSION['perfil'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Principal</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        h2 { margin-bottom: 20px; }
        .menu a {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 10px 15px;
            background: #f2f2f2;
            border-radius: 5px;
            text-decoration: none;
            color: #000;
            border: 1px solid #ccc;
        }
        .menu a:hover {
            background: #e0e0e0;
        }
        .perfil {
            margin-bottom: 20px;
            color: #555;
        }
    </style>
</head>
<body>

<h2>Bem-vindo, <?= htmlspecialchars($nome) ?></h2>
<div class="perfil">
    Perfil: <strong><?= htmlspecialchars($perfil) ?></strong>
</div>

<div class="menu">

    <!-- ADMIN -->
    <?php if ($perfil === 'ADMIN'): ?>
        <a href="cadastros/usuarios.php">👤 Usuários</a>
    <?php endif; ?>

    <!-- ADMIN e OPERADOR -->
    <?php if (in_array($perfil, ['ADMIN','OPERADOR'])): ?>
        <a href="cadastros/proprietarios.php">🏠 Proprietários</a>
        <a href="cadastros/imoveis.php">🏢 Imóveis</a>
        <a href="cadastros/contratos.php">📄 Contratos</a>
        <a href="cadastros/inquilinos.php">🧍 Inquilinos</a>
    <?php endif; ?>

</div>

<hr>

<a href="logout.php">Sair</a>

</body>
</html>
