<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificaPerfil(array $perfisPermitidos) {
    if (
        !isset($_SESSION['usuario_id']) ||
        !in_array($_SESSION['perfil'], $perfisPermitidos)
    ) {
        header("HTTP/1.1 403 Forbidden");
        echo "Acesso não autorizado.";
        exit;
    }
}
