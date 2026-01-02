<?php
session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN']);

/* =========================
   SALVAR USUÁRIO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome   = $_POST['nome']   ?? '';
    $email  = $_POST['email']  ?? '';
    $perfil = $_POST['perfil'] ?? '';
    $senha  = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO usuarios (nome, email, senha, perfil, ativo)
         VALUES (?, ?, ?, ?, 1)"
    );

    $stmt->bind_param("ssss", $nome, $email, $senha, $perfil);
    $stmt->execute();
}

/* =========================
   LISTAR USUÁRIOS
========================= */
$result = $conn->query(
    "SELECT id, nome, email, perfil, ativo
     FROM usuarios
     ORDER BY nome"
);

$usuarios = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Usuários</title>
</head>
<body>

<h2>Usuários</h2>

<form method="post">
    <input name="nome" placeholder="Nome" required>
    <input name="email" type="email" placeholder="Email" required>
    <input name="senha" type="password" placeholder="Senha" required>

    <select name="perfil" required>
        <option value="ADMIN">Administrador</option>
        <option value="OPERADOR">Operador</option>
        <option value="CONSULTA">Consulta</option>
    </select>

    <button type="submit">Salvar</button>
</form>

<hr>

<table border="1" cellpadding="5">
<tr>
    <th>Nome</th>
    <th>Email</th>
    <th>Perfil</th>
    <th>Status</th>
    <th>Ações</th>
</tr>

<?php foreach ($usuarios as $u): ?>
<tr>
    <td><?= htmlspecialchars($u['nome']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= $u['perfil'] ?></td>
    <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
    <td>
        <a href="usuario_toggle.php?id=<?= $u['id'] ?>">
            <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
