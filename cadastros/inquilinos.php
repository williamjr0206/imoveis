<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

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
    $ativo    = isset($_POST['ativo']) ? 1 : 0;

    if ($id) {
        // EDITAR
        $stmt = $conn->prepare("
            UPDATE inquilinos
            SET nome = ?, cpf = ?, telefone = ?, email = ?, ativo = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssii",
            $nome,
            $cpf,
            $telefone,
            $email,
            $ativo,
            $id
        );
    } else {
        // INSERIR
        $stmt = $conn->prepare("
            INSERT INTO inquilinos
            (nome, cpf, telefone, email, ativo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssi",
            $nome,
            $cpf,
            $telefone,
            $email,
            $ativo
        );
    }

    $stmt->execute();
    header("Location: inquilinos.php");
    exit;
}

/* =====================
   2) EXCLUIR
===================== */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM inquilinos WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: inquilinos.php");
    exit;
}

/* =====================
   3) CARREGAR PARA EDIÇÃO
===================== */
$editar = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM inquilinos WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =====================
   4) LISTAR INQUILINOS
===================== */
$inquilinos = [];
$result = $conn->query("
    SELECT *
    FROM inquilinos
    ORDER BY nome
");

while ($row = $result->fetch_assoc()) {
    $inquilinos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inquilinos</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { margin-bottom: 30px; }
        input { margin: 6px 0; padding: 6px; width: 360px; display: block; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        a { margin-right: 10px; }
    </style>
</head>
<body>

<h2><?= $editar ? 'Editar Inquilino' : 'Novo Inquilino' ?></h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <label>Nome</label>
    <input name="nome" required value="<?= $editar['nome'] ?? '' ?>">

    <label>CPF</label>
    <input name="cpf" value="<?= $editar['cpf'] ?? '' ?>">

    <label>Telefone</label>
    <input name="telefone" value="<?= $editar['telefone'] ?? '' ?>">

    <label>E-mail</label>
    <input type="email" name="email" value="<?= $editar['email'] ?? '' ?>">

    <label>
        <input type="checkbox" name="ativo"
            <?= (!isset($editar) || ($editar['ativo'] ?? 1)) ? 'checked' : '' ?>>
        Ativo
    </label>

    <button type="submit">
        <?= $editar ? 'Atualizar' : 'Salvar' ?>
    </button>

    <?php if ($editar): ?>
        <a href="inquilinos.php">Cancelar</a>
    <?php endif; ?>
</form>

<h2>Lista de Inquilinos</h2>

<table>
    <tr>
        <th>Nome</th>
        <th>CPF</th>
        <th>Telefone</th>
        <th>E-mail</th>
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
                <a href="inquilinos.php?edit=<?= $i['id'] ?>">Editar</a>

                <a href="inquilinos.php?delete=<?= $i['id'] ?>"
                   onclick="return confirm('Deseja excluir este inquilino?')">
                   Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
