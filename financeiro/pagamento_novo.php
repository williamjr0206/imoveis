<?php
require '__DIR__/../config/db.php';
require ' __DIR__/../config/auth.php';
;

verificaPerfil(['ADMIN','OPERADOR']);

if ($_POST) {
    $contrato = $_POST['contrato_id'];
    $aluguel  = $_POST['valor_aluguel'];
    $multa    = $_POST['multa'];
    $juros    = $_POST['juros'];

    $total = $aluguel + $multa + $juros;

    $sql = $pdo->prepare("
        INSERT INTO pagamentos
        (contrato_id, mes_referencia, valor_aluguel, multa, juros, valor_total, valor_pago, data_pagamento, status, criado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'PAGO', ?)
    ");

    $sql->execute([
        $contrato,
        $_POST['mes_referencia'],
        $aluguel,
        $multa,
        $juros,
        $total,
        $_POST['valor_pago'],
        $_SESSION['usuario_id']
    ]);

    echo "Pagamento registrado com sucesso!";
}
