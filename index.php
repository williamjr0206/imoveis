<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>
<h2>Bem-vindo, <?= $_SESSION['nome'] ?></h2>
<?php if ($_SESSION['perfil'] === 'ADMIN'): ?>
    <a href="cadastros/usuarios.php">Usuários</a>
<?php endif; ?>
