<?php
session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN','OPERADOR']);

$id = $_GET['id'] ?? 0;

/* =========================
   SALVAR EDIÇÃO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id    = $_POST['id'];
    $nome  = $_POST['nome'];
    $cpf   = $_POST['cpf'];
    $fone  = $_POST['telefone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare(
        "UPDATE inquilinos
         SET nome=?, cpf=?, telefone=?, email=?
         WHERE id=?"
    );
    $stmt->bind_param("ssssi", $nome, $cpf, $fone, $email, $id);
    $stmt->execute();

    header("Location: inquilinos.php");
    exit;
}

/* =========================
   CARREGAR
========================= */
$stmt = $conn->prepare(
    "SELECT * FROM inquilinos WHERE id=?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$inquilino = $stmt->get_result()->fetch_assoc();

if (!$inquilino) {
    header("Location: inquilinos.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Inquilino</title>
</head>
<body>

<h2>Editar Inquilino</h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $inquilino['id'] ?>">

    <input name="nome" value="<?= htmlspecialchars($inquilino['nome']) ?>" required>
    <input name="cpf" value="<?= htmlspecialchars($inquilino['cpf']) ?>">
    <input name="telefone" value="<?= htmlspecialchars($inquilino['telefone']) ?>">
    <input name="email" type="email" value="<?= htmlspecialchars($inquilino['email']) ?>">

    <button type="submit">Salvar</button>
    <a href="inquilinos.php">Cancelar</a>
</form>

</body>
</html>
