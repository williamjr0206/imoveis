<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/auth.php';
verificaPerfil(['ADMIN']);

$id = $_GET['id'];

$sql = $pdo->prepare("
    UPDATE usuarios
    SET ativo = IF(ativo = 1, 0, 1)
    WHERE id = ?
");
$sql->execute([$id]);

header("Location: usuarios.php");
exit;
