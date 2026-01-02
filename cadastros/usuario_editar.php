<?php
session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN']);

$id = $_GET['id'] ?? 0;

/* =========================
   SALVAR EDIÇÃO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id     = $_POST['id'];
    $nome   = $_POST['nome'];
    $email  = $_POST['email'];
    $perfil = $_POST['perfil'];

    if (!empty($_POST['senha'])) {
        // Atualiza com nova senha
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "UPDATE usuarios
             SET nome = ?, email = ?, perfil = ?, senha = ?
             WHERE id = ?"
        );
        $stmt->bind_param("ssssi", $nome, $email, $perfil, $senha, $id);

    } else {
        // Atualiza sem mexer na senha
        $stmt = $conn->prepare(
            "UPDATE usuarios
             SET nome = ?, email = ?, perfil = ?
             WHERE id = ?"
        );
        $stmt->bind_param("sssi", $nome, $email, $perfil, $id);
    }

    $stmt->execute();

    header("Location: usuarios.php");
    exit;
}

/* =========================
   CARREGAR USUÁRIO
========================= */
$stmt = $conn->prepare(
    "SELECT id, nome, email, perfil
     FROM usuarios
     WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    header("Location: usuarios.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
</head>
<body>

<h2>Editar Usuário</h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

    <input name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
    <input name="email" type="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

    <select name="perfil" required>
        <option value="ADMIN" <?= $usuario['perfil']=='ADMIN'?'selected':'' ?>>Administrador</option>
        <option value="OPERADOR" <?= $usuario['perfil']=='OPERADOR'?'selected':'' ?>>Operador</option>
        <option value="CONSULTA" <?= $usuario['perfil']=='CONSULTA'?'selected':'' ?>>Consulta</option>
    </select>

    <input name="senha" type="password" placeholder="Nova senha (opcional)">

    <button type="submit">Salvar</button>
    <a href="usuarios.php">Cancelar</a>
</form>

</body>
</html>
