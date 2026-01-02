<?php
require __DIR__ . '/config/database.php';

$senha = '123456';
$hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO usuarios (nome, email, senha, perfil, ativo)
     VALUES (?, ?, ?, ?, 1)"
);

$nome   = 'Administrador';
$email  = 'admin@teste.com';
$perfil = 'admin';

$stmt->bind_param("ssss", $nome, $email, $hash, $perfil);

if ($stmt->execute()) {
    echo "Usuário criado com sucesso!<br>";
    echo "Email: $email<br>";
    echo "Senha: $senha";
} else {
    echo "Erro ao criar usuário";
}
