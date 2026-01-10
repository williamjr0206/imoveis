<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

verificaPerfil(['ADMIN']);

/* =====================
   1) SALVAR / EDITAR
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id     = $_POST['id'] ?? null;
    $nome   = $_POST['nome'];
    $email  = $_POST['email'];
    $perfil = $_POST['perfil'];
    $ativo  = isset($_POST['ativo']) ? 1 : 0;

    // senha só é atualizada se for informada
    $senha = !empty($_POST['senha'])
        ? password_hash($_POST['senha'], PASSWORD_DEFAULT)
        : null;

    if ($id) {
        if ($senha) {
            $stmt = $conn->prepare("
                UPDATE usuarios
                SET nome = ?, email = ?, perfil = ?, senha = ?, ativo = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssssii",
                $nome,
                $email,
                $perfil,
                $senha,
                $ativo,
                $id
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE usuarios
                SET nome = ?, email = ?, perfil = ?, ativo = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssii",
                $nome,
                $email,
                $perfil,
                $ativo,
                $id
            );
        }
    } else {
        $stmt = $conn->prepare("
            INSERT INTO usuarios
            (nome, email, senha, perfil, ativo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssi",
            $nome,
            $email,
            password_hash($_POST['senha'], PASSWORD_DEFAULT),
            $perfil,
            $ativo
        );
    }

    $stmt->execute();
    header("Location: usuarios.php");
    exit;
}

/* =====================
   2) EXCLUIR
===================== */
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: usuarios.php");
    exit;
}

/* =====================
   3) CARREGAR EDIÇÃO
===================== */
$editar = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =====================
   4) LISTAR USUÁRIOS
===================== */
$usuarios = [];
$result = $conn->query("
    SELECT id, nome, email, perfil, ativo
    FROM usuarios
    ORDER BY nome
");

while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Usuários</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { margin-bottom: 30px; }
        input, select { margin: 6px 0; padding: 6px; width: 360px; display: block; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        a { margin-right: 10px; }
    </style>
</head>
<body>

<h2><?= $editar ? 'Editar Usuário' : 'Novo Usuário' ?></h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <label>Nome</label>
    <input name="nome" required value="<?= $editar['nome'] ?? '' ?>">

    <label>Email</label>
    <input type="email" name="email" required value="<?= $editar['email'] ?? '' ?>">

    <label>Senha <?= $editar ? '(deixe em branco para manter)' : '' ?></label>
    <input type="password" name="senha" <?= $editar ? '' : 'required' ?>>

    <label>Perfil</label>
    <select name="perfil" required>
        <?php foreach (['ADMIN','OPERADOR','CONSULTA'] as $p): ?>
            <option value="<?= $p ?>"
                <?= ($editar && $editar['perfil'] === $p) ? 'selected' : '' ?>>
                <?= $p ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>
        <input type="checkbox" name="ativo"
            <?= (!isset($editar) || ($editar['ativo'] ?? 1)) ? 'checked' : '' ?>>
        Ativo
    </label>

    <button type="submit">
        <?= $editar ? 'Atualizar' : 'Salvar' ?>
    </button>

    <?php if ($editar): ?>
        <a href="usuarios.php">Cancelar</a>
    <?php endif; ?>
</form>

<h2>Lista de Usuários</h2>

<table>
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
                <a href="usuarios.php?edit=<?= $u['id'] ?>">Editar</a>

                <a href="usuarios.php?delete=<?= $u['id'] ?>"
                   onclick="return confirm('Deseja excluir este usuário?')">
                   Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
