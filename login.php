<?php
session_start();
require 'config/db.php';

if ($_POST) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
    $sql->execute([$email]);
    $user = $sql->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['perfil'] = $user['perfil'];

        header("Location: index.php");
        exit;
    } else {
        $erro = "Usuário ou senha inválidos";
    }
}
?>
