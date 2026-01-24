<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =========================
   1) SALVAR / EDITAR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id           = $_POST['id'] ?? null;
    $nome         = $_POST['nome'];
    $tipo_pessoa  = $_POST['tipo_pessoa']; // F ou J
    $documento    = $_POST['documento'];
    $telefone     = $_POST['telefone'] ?? null;
    $email        = $_POST['email'] ?? null;

    if ($id) {
        // EDITAR
        $stmt = $conn->prepare("
            UPDATE inquilinos
            SET nome = ?, tipo_pessoa = ?, documento = ?, telefone = ?, email = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssi",
            $nome,
            $tipo_pessoa,
            $documento,
            $telefone,
            $email,
            $id
        );
    } else {
        // INSERIR
        $stmt = $conn->prepare("
            INSERT INTO inquilinos
            (nome, tipo_pessoa, documento, telefone, email)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssss",
            $nome,
            $tipo_pessoa,
            $documento,
            $telefone,
            $email
        );
    }

    $stmt->execute();
    header("Location: inquilinos.php");
    exit;
}

/* =========================
   2) EXCLUIR
========================= */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM inquilinos WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: inquilinos.php");
    exit;
}

/* =========================
   3) CARREGAR EDIÇÃO
========================= */
$editar = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM inquilinos WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =========================
   4) LISTAGEM
========================= */
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
        input, select { margin: 6px 0; padding: 6px; width: 350px; display: block; }
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

    <label>Nome / Razão Social</label>
    <input type="text" name="nome" required
           value="<?= htmlspecialchars($editar['nome'] ?? '') ?>">

    <label>Tipo de Pessoa</label>
    <select name="tipo_pessoa" required>
        <option value="F" <?= ($editar['tipo_pessoa'] ?? 'F') === 'F' ? 'selected' : '' ?>>
            Pessoa Física (CPF)
        </option>
        <option value="J" <?= ($editar['tipo_pessoa'] ?? '') === 'J' ? 'selected' : '' ?>>
            Pessoa Jurídica (CNPJ)
        </option>
    </select>

    <label>CPF / CNPJ</label>
    <input type="text" name="documento" required
           value="<?= htmlspecialchars($editar['documento'] ?? '') ?>"
           placeholder="CPF ou CNPJ">

    <label>Telefone</label>
    <input type="text" name="telefone"
           value="<?= htmlspecialchars($editar['telefone'] ?? '') ?>">

    <label>Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($editar['email'] ?? '') ?>">

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
        <th>Tipo</th>
        <th>Documento</th>
        <th>Telefone</th>
        <th>Email</th>
        <th>Ações</th>
    </tr>

    <?php foreach ($inquilinos as $i): ?>
        <tr>
            <td><?= htmlspecialchars($i['nome']) ?></td>
            <td><?= $i['tipo_pessoa'] === 'F' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></td>
            <td><?= htmlspecialchars($i['documento']) ?></td>
            <td><?= htmlspecialchars($i['telefone']) ?></td>
            <td><?= htmlspecialchars($i['email']) ?></td>
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
