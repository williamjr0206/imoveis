<?php
session_start();

// limpa todas as variáveis de sessão
$_SESSION = [];

// destrói a sessão
session_destroy();

// redireciona para o login
header("Location: " . BASE_URL . "login.php");
exit;