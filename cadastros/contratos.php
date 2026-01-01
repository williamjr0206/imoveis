<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =====================
   1) SALVAR / EDITAR
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = $_POST['id'] ?? null;
    $imovel_id      = $_POST['imovel_id'];
    $inquilino      = $_POST['inquilino'];
    $data_inicio    = $_POST['data_inicio'];
    $data_fim       = $_POST['data_fim'];
    $valor_aluguel  = $_POST['valor_aluguel'];
    $dia_vencimento = $_POST['dia_vencimento'];
    $ativo          = isset($_POST['ativo']) ? 1 : 0;

    if ($id) {
        // EDITAR
        $sql = $pdo->prepare("
            UPDATE contratos
            SET imovel_id = ?, inquilino = ?, data_inicio = ?, data_fim = ?,
                valor_aluguel = ?, dia_vencimento = ?, ativo = ?
            WHERE id = ?
        ");
        $sql->execute([
            $imovel_id, $inquilino, $data_inicio, $data_fim,
            $valor_aluguel, $dia_vencimento, $ativo, $id
        ]);
    } else {
        // SALVAR
        $sql = $pdo->prepare("
            INSERT INTO contratos
            (imovel_id, inquilino, data_inicio, data_fim, valor_aluguel, dia_vencimento, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $sql->execute([
            $imovel_id, $inquilino, $data_inicio, $data_fim,
            $valor_aluguel, $dia_vencimento, $ativo
        ]);
    }

    header("Location: contratos.php");
    exit;
}

/* =====================
   2) EXCLUIR
===================== */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $sql = $pdo->prepare("DELETE FROM contratos WHERE id = ?");
    $sql->execute([$_GET['delete']]);

    header("Location: contratos.php");
    exit;
}

/* =====================
   3) CARREGAR EDIÇÃO
===================== */
$editar = null;
if (isset($_GET['edit'])) {
    $sql = $pdo->prepare("SELECT * FROM contratos WHERE id = ?");
    $sql->execute([$_GET['edit']]);
    $editar = $sql->fetch();
}

/* =====================
   4) LISTAR IMÓVEIS (SELECT)
===================== */
$imoveis = $pdo->query("
    SELECT i.id, CONCAT(p.nome, ' - ', i.descricao) AS imovel
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE i.ativo = 1
    ORDER BY p.nome
")->fetchAll();

/* =====================
   5) LISTAR CONTRATOS
===================== */
$contratos = $pdo->query("
    SELECT c.*, 
           CONCAT(p.nome, ' - ', i.descricao) AS imovel
    FROM contratos c
    JOIN imoveis i ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY c.data_inicio DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Contratos de Aluguel</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { margin-bottom: 30px; }
        input, select { margin: 5px 0; padding: 6px; width: 360px; display: block; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        a { margin-right: 10px; }
    </style>
</head>
<body>

<h2><?= $editar ? 'Editar Contrato' : 'Novo Contrato' ?></h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <label>Imóvel</label>
    <select name="imovel_id" required>
        <option value="">Selecione</option>
        <?php foreach ($imoveis as $i): ?>
            <option value="<?= $i['id'] ?>"
                <?= ($editar && $editar['imovel_id'] == $i['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($i['imovel']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Inquilino</label>
    <input name="inquilino" required
           value="<?= $editar['inquilino'] ?? '' ?>">

    <label>Data Início</label>
    <input type="date" name="data_inicio" required
           value="<?= $editar['data_inicio'] ?? '' ?>">

    <label>Data Fim</label>
    <input type="date" name="data_fim"
           value="<?= $editar['data_fim'] ?? '' ?>">

    <label>Valor do Aluguel (R$)</label>
    <input type="number" step="0.01" name="valor_aluguel" required
           value="<?= $editar['valor_aluguel'] ?? '' ?>">

    <label>Dia de Vencimento</label>
    <input type="number" min="1" max="31" name="dia_vencimento" required
           value="<?= $editar['dia_vencimento'] ?? '' ?>">

    <label>
        <input type="checkbox" name="ativo"
            <?= (!$editar || $editar['ativo']) ? 'checked' : '' ?>>
        Contrato Ativo
    </label>

    <button type="submit">
        <?= $editar ? 'Atualizar' : 'Salvar' ?>
    </button>

    <?php if ($editar): ?>
        <a href="contratos.php">Cancelar</a>
    <?php endif; ?>
</form>

<h2>Lista de Contratos</h2>

<table>
    <tr>
        <th>Imóvel</th>
        <th>Inquilino</th>
        <th>Início</th>
        <th>Fim</th>
        <th>Aluguel (R$)</th>
        <th>Venc.</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>

    <?php foreach ($contratos as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['imovel']) ?></td>
            <td><?= htmlspecialchars($c['inquilino']) ?></td>
            <td><?= date('d/m/Y', strtotime($c['data_inicio'])) ?></td>
            <td><?= $c['data_fim'] ? date('d/m/Y', strtotime($c['data_fim'])) : '-' ?></td>
            <td><?= number_format($c['valor_aluguel'], 2, ',', '.') ?></td>
            <td><?= $c['dia_vencimento'] ?></td>
            <td><?= $c['ativo'] ? 'Ativo' : 'Encerrado' ?></td>
            <td>
                <a href="contratos.php?edit=<?= $c['id'] ?>">Editar</a>
                <a href="contratos.php?delete=<?= $c['id'] ?>"
                   onclick="return confirm('Deseja excluir este contrato?')">
                   Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
