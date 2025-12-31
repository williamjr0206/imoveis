<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/auth.php';

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
    $ativo           = isset($_POST['ativo']) ? 1 : 0;

    if ($id) {
        // EDITAR
        $sql = $pdo->prepare("
            UPDATE imoveis
            SET proprietario_id = ?, descricao = ?, tipo = ?, endereco = ?, valor_aluguel = ?, ativo = ?
            WHERE id = ?
        ");
        $sql->execute([
            $proprietario_id, $descricao, $tipo,
            $endereco, $valor_aluguel, $ativo, $id
        ]);
    } else {
        // SALVAR
        $sql = $pdo->prepare("
            INSERT INTO imoveis
            (proprietario_id, descricao, tipo, endereco, valor_aluguel, ativo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $sql->execute([
            $proprietario_id, $descricao, $tipo,
            $endereco, $valor_aluguel, $ativo
        ]);
    }

    header("Location: imoveis.php");
    exit;
}

/* =====================
   2) EXCLUIR
===================== */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $sql = $pdo->prepare("DELETE FROM imoveis WHERE id = ?");
    $sql->execute([$_GET['delete']]);

    header("Location: imoveis.php");
    exit;
}

/* =====================
   3) CARREGAR EDIÇÃO
===================== */
$editar = null;
if (isset($_GET['edit'])) {
    $sql = $pdo->prepare("SELECT * FROM imoveis WHERE id = ?");
    $sql->execute([$_GET['edit']]);
    $editar = $sql->fetch();
}

/* =====================
   4) LISTAR PROPRIETÁRIOS (SELECT)
===================== */
$proprietarios = $pdo
    ->query("SELECT id, nome FROM proprietarios ORDER BY nome")
    ->fetchAll();

/* =====================
   5) LISTAR IMÓVEIS
===================== */
$imoveis = $pdo->query("
    SELECT i.*, p.nome AS proprietario
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY p.nome
")->fetchAll();
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
    <input name="descricao" required
           value="<?= $editar['descricao'] ?? '' ?>">

    <label>Tipo</label>
    <input name="tipo"
           placeholder="Casa, Apto, Sala..."
           value="<?= $editar['tipo'] ?? '' ?>">

    <label>Endereço</label>
    <input name="endereco"
           value="<?= $editar['endereco'] ?? '' ?>">

    <label>Valor do Aluguel (R$)</label>
    <input type="number" step="0.01" name="valor_aluguel"
           value="<?= $editar['valor_aluguel'] ?? '' ?>">

    <label>
        <input type="checkbox" name="ativo"
            <?= (!$editar || $editar['ativo']) ? 'checked' : '' ?>>
        Ativo
    </label>

    <button type="submit">
        <?= $editar ? 'Atualizar' : 'Salvar' ?>
    </button>

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
            <td><?= $i['ativo'] ? 'Ativo' : 'Inativo' ?></td>
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
