<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =====================
   SALVAR / EDITAR
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id       = $_POST['id'] ?? null;
    $nome     = $_POST['nome'] ?? '';
    $cpf      = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email    = $_POST['email'] ?? '';

    if ($id) {
        $stmt = $conn->prepare("
            UPDATE proprietarios
            SET nome = ?, cpf = ?, telefone = ?, email = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssi", $nome, $cpf, $telefone, $email, $id);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO proprietarios (nome, cpf, telefone, email)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $nome, $cpf, $telefone, $email);
    }

    $stmt->execute();
    header("Location: proprietarios.php");
    exit;
}

/* =====================
   EXCLUIR
===================== */
if (isset($_GET['delete'])) {

    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM proprietarios WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: proprietarios.php");
    exit;
}

/* =====================
   EDITAR
===================== */
$editar = null;

if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM proprietarios WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =====================
   LISTAR
===================== */
$proprietarios = [];

$result = $conn->query("SELECT * FROM proprietarios ORDER BY nome");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $proprietarios[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Proprietários</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { margin-bottom: 30px; }
        input { margin: 5px 0; padding: 6px; width: 300px; display: block; }
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
    <input name="nome" required value="<?= htmlspecialchars($editar['nome'] ?? '') ?>">

    <label>CPF</label>
    <input name="cpf" value="<?= htmlspecialchars($editar['cpf'] ?? '') ?>">

    <label>Telefone</label>
    <input name="telefone" value="<?= htmlspecialchars($editar['telefone'] ?? '') ?>">

    <label>E-mail</label>
    <input type="email" name="email" value="<?= htmlspecialchars($editar['email'] ?? '') ?>">

    <button type="submit"><?= $editar ? 'Atualizar' : 'Salvar' ?></button>

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
                   onclick="return confirm('Deseja excluir este proprietário?')">
                   Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
