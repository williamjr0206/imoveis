<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';


verificaPerfil(['ADMIN','OPERADOR']);

/* =====================
   1) SALVAR / EDITAR
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id              = $_POST['id'] ?? null;
    $proprietario_id = $_POST['proprietario_id'];
    $descricao       = $_POST['descricao'];
    $tipo            = $_POST['tipo'];
    $endereco        = $_POST['endereco'];
    $valor_aluguel   = $_POST['valor_aluguel'];
    $status = $_POST['status'];


    if ($id) {
        // EDITAR
$stmt = $conn->prepare("
    UPDATE imoveis
    SET proprietario_id = ?, descricao = ?, tipo = ?, endereco = ?, valor_aluguel = ?, status = ?
    WHERE id = ?
");
$stmt->bind_param(
    "isssdsi",
    $proprietario_id,
    $descricao,
    $tipo,
    $endereco,
    $valor_aluguel,
    $status,
    $id
);
$stmt->execute();
    } else {
        // SALVAR
$stmt = $conn->prepare("
    INSERT INTO imoveis
    (proprietario_id, descricao, tipo, endereco, valor_aluguel, status)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "isssds",
    $proprietario_id,
    $descricao,
    $tipo,
    $endereco,
    $valor_aluguel,
    $status
);
        $stmt->execute();
    }

    header("Location: imoveis.php");
    exit;
}

/* =====================
   2) EXCLUIR
===================== */
if (isset($_GET['delete'])) {

    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM imoveis WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: imoveis.php");
    exit;
}

/* =====================
   3) CARREGAR EDIÇÃO
===================== */
$editar = null;
if (isset($_GET['edit'])) {

    $stmt = $conn->prepare("SELECT * FROM imoveis WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =====================
   4) LISTAR PROPRIETÁRIOS
===================== */
$proprietarios = [];
$result = $conn->query("SELECT id, nome FROM proprietarios ORDER BY nome");
while ($row = $result->fetch_assoc()) {
    $proprietarios[] = $row;
}

/* =====================
   5) LISTAR IMÓVEIS
===================== */
$imoveis = [];
$result = $conn->query("
    SELECT i.*, p.nome AS proprietario
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY p.nome
");
while ($row = $result->fetch_assoc()) {
    $imoveis[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Imóveis</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { margin-bottom: 30px; }
        input, select { margin: 5px 0; padding: 6px; width: 350px; display: block; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        a { margin-right: 10px; }
    </style>
</head>
<body>

<h2><?= $editar ? 'Editar Imóvel' : 'Novo Imóvel' ?></h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <label>Proprietário</label>
    <select name="proprietario_id" required>
        <option value="">Selecione</option>
        <?php foreach ($proprietarios as $p): ?>
            <option value="<?= $p['id'] ?>"
                <?= ($editar && $editar['proprietario_id'] == $p['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Descrição</label>
    <input name="descricao" required value="<?= $editar['descricao'] ?? '' ?>">

    <label>Tipo</label>
    <input name="tipo" value="<?= $editar['tipo'] ?? '' ?>">

    <label>Endereço</label>
    <input name="endereco" value="<?= $editar['endereco'] ?? '' ?>">

    <label>Valor do Aluguel (R$)</label>
    <input type="number" step="0.01" name="valor_aluguel"
           value="<?= $editar['valor_aluguel'] ?? '' ?>">

    <label>Status</label>
<select name="status" required>
    <option value="ATIVO" <?= ($editar['status'] ?? '') === 'ATIVO' ? 'selected' : '' ?>>Ativo</option>
    <option value="LOCADO" <?= ($editar['status'] ?? '') === 'LOCADO' ? 'selected' : '' ?>>Locado</option>
    <option value="INATIVO" <?= ($editar['status'] ?? '') === 'INATIVO' ? 'selected' : '' ?>>Inativo</option>
</select>

    <button type="submit"><?= $editar ? 'Atualizar' : 'Salvar' ?></button>

    <?php if ($editar): ?>
        <a href="imoveis.php">Cancelar</a>
    <?php endif; ?>
</form>

<h2>Lista de Imóveis</h2>

<table>
    <tr>
        <th>Proprietário</th>
        <th>Descrição</th>
        <th>Tipo</th>
        <th>Endereço</th>
        <th>Aluguel (R$)</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>

    <?php foreach ($imoveis as $i): ?>
        <tr>
            <td><?= htmlspecialchars($i['proprietario']) ?></td>
            <td><?= htmlspecialchars($i['descricao']) ?></td>
            <td><?= htmlspecialchars($i['tipo']) ?></td>
            <td><?= htmlspecialchars($i['endereco']) ?></td>
            <td><?= number_format($i['valor_aluguel'], 2, ',', '.') ?></td>
            <td><?= $i['status'] === 'ATIVO' ? 'Ativo' : $i['status'] ?>
</td>

            <td>
                <a href="imoveis.php?edit=<?= $i['id'] ?>">Editar</a>
                <a href="imoveis.php?delete=<?= $i['id'] ?>"
                   onclick="return confirm('Deseja excluir este imóvel?')">
                   Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
