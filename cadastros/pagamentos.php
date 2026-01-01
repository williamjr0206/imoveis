<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =====================
   1) SALVAR / EDITAR
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = $_POST['id'] ?? null;
    $contrato_id    = $_POST['contrato_id'];
    $mes_referencia = $_POST['mes_referencia'];
    $valor_pago     = $_POST['valor_pago'];
    $data_pagamento = $_POST['data_pagamento'];
    $status         = $_POST['status'];
    $criado_por     = $_SESSION['usuario_id'];

    if ($id) {
        // EDITAR
        $sql = $pdo->prepare("
            UPDATE pagamentos
            SET contrato_id = ?, mes_referencia = ?, valor_pago = ?,
                data_pagamento = ?, status = ?
            WHERE id = ?
        ");
        $sql->execute([
            $contrato_id, $mes_referencia, $valor_pago,
            $data_pagamento, $status, $id
        ]);
    } else {
        // SALVAR
        $sql = $pdo->prepare("
            INSERT INTO pagamentos
            (contrato_id, mes_referencia, valor_pago, data_pagamento, status, criado_por)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $sql->execute([
            $contrato_id, $mes_referencia, $valor_pago,
            $data_pagamento, $status, $criado_por
        ]);
    }

    header("Location: pagamentos.php");
    exit;
}

/* =====================
   2) EXCLUIR
===================== */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $sql = $pdo->prepare("DELETE FROM pagamentos WHERE id = ?");
    $sql->execute([$_GET['delete']]);

    header("Location: pagamentos.php");
    exit;
}

/* =====================
   3) CARREGAR EDIÇÃO
===================== */
$editar = null;
if (isset($_GET['edit'])) {
    $sql = $pdo->prepare("SELECT * FROM pagamentos WHERE id = ?");
    $sql->execute([$_GET['edit']]);
    $editar = $sql->fetch();
}

/* =====================
   4) LISTAR CONTRATOS (SELECT)
===================== */
$contratos = $pdo->query("
    SELECT c.id,
           CONCAT(p.nome, ' - ', i.descricao, ' (', c.inquilino, ')') AS contrato
    FROM contratos c
    JOIN imoveis i ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE c.ativo = 1
    ORDER BY p.nome
")->fetchAll();

/* =====================
   5) LISTAR PAGAMENTOS
===================== */
$pagamentos = $pdo->query("
    SELECT pg.*,
           c.inquilino,
           i.descricao AS imovel,
           p.nome AS proprietario
    FROM pagamentos pg
    JOIN contratos c ON c.id = pg.contrato_id
    JOIN imoveis i ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY pg.mes_referencia DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagamentos</title>
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

<h2><?= $editar ? 'Editar Pagamento' : 'Novo Pagamento' ?></h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <label>Contrato</label>
    <select name="contrato_id" required>
        <option value="">Selecione</option>
        <?php foreach ($contratos as $c): ?>
            <option value="<?= $c['id'] ?>"
                <?= ($editar && $editar['contrato_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['contrato']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Mês de Referência</label>
    <input type="month" name="mes_referencia" required
           value="<?= $editar['mes_referencia'] ?? '' ?>">

    <label>Valor Pago (R$)</label>
    <input type="number" step="0.01" name="valor_pago" required
           value="<?= $editar['valor_pago'] ?? '' ?>">

    <label>Data do Pagamento</label>
    <input type="date" name="data_pagamento"
           value="<?= $editar['data_pagamento'] ?? '' ?>">

    <label>Status</label>
    <select name="status">
        <?php
        $statusList = ['PAGO','ATRASADO','PENDENTE'];
        foreach ($statusList as $s):
        ?>
            <option value="<?= $s ?>"
                <?= ($editar && $editar['status'] == $s) ? 'selected' : '' ?>>
                <?= $s ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">
        <?= $editar ? 'Atualizar' : 'Salvar' ?>
    </button>

    <?php if ($editar): ?>
        <a href="pagamentos.php">Cancelar</a>
    <?php endif; ?>
</form>

<h2>Histórico de Pagamentos</h2>

<table>
    <tr>
        <th>Proprietário</th>
        <th>Imóvel</th>
        <th>Inquilino</th>
        <th>Mês Ref.</th>
        <th>Valor (R$)</th>
        <th>Pago em</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>

    <?php foreach ($pagamentos as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['proprietario']) ?></td>
            <td><?= htmlspecialchars($p['imovel']) ?></td>
            <td><?= htmlspecialchars($p['inquilino']) ?></td>
            <td><?= date('m/Y', strtotime($p['mes_referencia'])) ?></td>
            <td><?= number_format($p['valor_pago'], 2, ',', '.') ?></td>
            <td><?= $p['data_pagamento'] ? date('d/m/Y', strtotime($p['data_pagamento'])) : '-' ?></td>
            <td><?= $p['status'] ?></td>
            <td>
                <a href="pagamentos.php?edit=<?= $p['id'] ?>">Editar</a>
                <a href="pagamentos.php?delete=<?= $p['id'] ?>"
                   onclick="return confirm('Deseja excluir este pagamento?')">
                   Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
