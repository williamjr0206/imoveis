<?php
session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN']);

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare(
    "UPDATE inquilinos
     SET ativo = IF(ativo=1,0,1)
     WHERE id=?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: inquilinos.php");
exit;
