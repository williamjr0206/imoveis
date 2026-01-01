<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =====================
   1) SALVAR / EDITAR
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id'] ?? null;
    $nome     = $_POST['nome'];
    $cpf      = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    $email    = $_POST['email'];

    if ($id) {
        // EDITAR
        $sql = $pdo->prepare("
            UPDATE proprietarios
            SET nome = ?, cpf = ?, telefone = ?, email = ?
            WHERE id = ?
        ");
        $sql->execute([$nome, $cpf, $telefone, $email, $id]);
    } else {
        // SALVAR
        $sql = $pdo->prepare("
            INSERT INTO proprietarios (nome, cpf, telefone, email)
            VALUES (?, ?, ?, ?)
        ");
        $sql->execute([$nome, $cpf, $telefone, $email]);
    }

    header("Location: proprietarios.php");
    exit;
}

/* =====================
   2) EXCLUIR
===================== */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']); // somente admin exclui

    $sql = $pdo->prepare("DELETE FROM proprietarios WHERE id = ?");
    $sql->execute([$_GET['delete']]);

    header("Location: proprietarios.php");
    exit;
}

/* =====================
   3) CARREGAR PARA EDIÇÃO
===================== */
$editar = null;
if (isset($_GET['edit'])) {
    $sql = $pdo->prepare("SELECT * FROM proprietarios WHERE id = ?");
    $sql->execute([$_GET['edit']]);
    $editar = $sql->fetch();
}

/* =====================
   4) LISTAR
===================== */
$proprietarios = $pdo
    ->query("SELECT * FROM proprietarios ORDER BY nome")
    ->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Proprietários</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { margin-bottom: 30px; }
        input { margin: 5px 0; padding: 5px; width: 300px; display: block; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        a { margin-right: 10px; }
    </style>
</head>
<body>

<h2><?= $editar ? 'Editar Proprietário' : 'Novo Proprietário' ?></h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <label>Nome</label>
    <input name="nome" required
           value="<?= $editar['nome'] ?? '' ?>">

    <label>CPF</label>
    <input name="cpf"
           value="<?= $editar['cpf'] ?? '' ?>">

    <label>Telefone</label>
    <input name="telefone"
           value="<?= $editar['telefone'] ?? '' ?>">

    <label>E-mail</label>
    <input type="email" name="email"
           value="<?= $editar['email'] ?? '' ?>">

    <button type="submit">
        <?= $editar ? 'Atualizar' : 'Salvar' ?>
    </button>

    <?php if ($editar): ?>
        <a href="proprietarios.php">Cancelar</a>
    <?php endif; ?>
</form>

<h2>Lista de Proprietários</h2>

<table>
    <tr>
        <th>Nome</th>
        <th>CPF</th>
        <th>Telefone</th>
        <th>E-mail</th>
        <th>Ações</th>
    </tr>

    <?php foreach ($proprietarios as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['nome']) ?></td>
            <td><?= htmlspecialchars($p['cpf']) ?></td>
            <td><?= htmlspecialchars($p['telefone']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td>
                <a href="proprietarios.php?edit=<?= $p['id'] ?>">Editar</a>

                <a href="proprietarios.php?delete=<?= $p['id'] ?>"
                   onclick="return confirm('Tem certeza que deseja excluir este proprietário?')">
                   Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
