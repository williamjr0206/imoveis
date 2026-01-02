<?php
session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =========================
   SALVAR INQUILINO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome  = $_POST['nome'];
    $cpf   = $_POST['cpf'];
    $fone  = $_POST['telefone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare(
        "INSERT INTO inquilinos (nome, cpf, telefone, email, ativo)
         VALUES (?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("ssss", $nome, $cpf, $fone, $email);
    $stmt->execute();
}

/* =========================
   LISTAR INQUILINOS
========================= */
$result = $conn->query(
    "SELECT id, nome, cpf, telefone, email, ativo
     FROM inquilinos
     ORDER BY nome"
);
$inquilinos = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Inquilinos</title>
</head>
<body>

<h2>Inquilinos</h2>

<form method="post">
    <input name="nome" placeholder="Nome" required>
    <input name="cpf" placeholder="CPF">
    <input name="telefone" placeholder="Telefone">
    <input name="email" type="email" placeholder="Email">
    <button type="submit">Salvar</button>
</form>

<hr>

<table border="1" cellpadding="5">
<tr>
    <th>Nome</th>
    <th>CPF</th>
    <th>Telefone</th>
    <th>Email</th>
    <th>Status</th>
    <th>Ações</th>
</tr>

<?php foreach ($inquilinos as $i): ?>
<tr>
    <td><?= htmlspecialchars($i['nome']) ?></td>
    <td><?= htmlspecialchars($i['cpf']) ?></td>
    <td><?= htmlspecialchars($i['telefone']) ?></td>
    <td><?= htmlspecialchars($i['email']) ?></td>
    <td><?= $i['ativo'] ? 'Ativo' : 'Inativo' ?></td>
    <td>
        <a href="inquilino_editar.php?id=<?= $i['id'] ?>">Editar</a> |
        <a href="inquilino_toggle.php?id=<?= $i['id'] ?>">
            <?= $i['ativo'] ? 'Desativar' : 'Ativar' ?>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
