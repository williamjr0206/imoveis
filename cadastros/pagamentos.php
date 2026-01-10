<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

verificaPerfil(['ADMIN', 'OPERADOR']);

/* =========================
   TRATAMENTO POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id             = $_POST['id'] ?? null;
    $contrato_id    = $_POST['contrato_id'];
    $mes_referencia = $_POST['mes_referencia'];
    $valor_pago     = $_POST['valor_pago'];
    $data_pagamento = $_POST['data_pagamento'];
    $observacao     = $_POST['observacao'] ?? null;

    // Corrige YYYY-MM para DATE
    if (strlen($mes_referencia) === 7) {
        $mes_referencia .= '-01';
    }

    if ($id) {
        // EDITAR
        $stmt = $conn->prepare("
            UPDATE pagamentos
            SET contrato_id = ?, mes_referencia = ?, valor_pago = ?, 
                data_pagamento = ?, observacao = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "isdssi",
            $contrato_id,
            $mes_referencia,
            $valor_pago,
            $data_pagamento,
            $observacao,
            $id
        );
    } else {
        // SALVAR
        $stmt = $conn->prepare("
            INSERT INTO pagamentos
            (contrato_id, mes_referencia, valor_pago, data_pagamento, observacao)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isdss",
            $contrato_id,
            $mes_referencia,
            $valor_pago,
            $data_pagamento,
            $observacao
        );
    }

    $stmt->execute();
    header("Location: pagamentos.php");
    exit;
}

/* =========================
   EXCLUIR
========================= */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM pagamentos WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: pagamentos.php");
    exit;
}

/* =========================
   EDITAR
========================= */
$editar = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM pagamentos WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();

    if ($editar) {
        $editar['mes_referencia'] = substr($editar['mes_referencia'], 0, 7);
    }
}

/* =========================
   CONTRATOS ATIVOS
========================= */
$contratos = [];
$result = $conn->query("
    SELECT 
        c.id,
        CONCAT(
            p.nome, ' - ',
            i.descricao, ' (',
            iq.nome, ')'
        ) AS contrato,
        i.valor_aluguel
    FROM contratos c
    JOIN imoveis i       ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    JOIN inquilinos iq   ON iq.id = c.inquilino_id
    WHERE c.ativo = 1
    ORDER BY p.nome
");

while ($row = $result->fetch_assoc()) {
    $contratos[] = $row;
}

/* =========================
   LISTAGEM PAGAMENTOS
========================= */
$pagamentos = [];
$result = $conn->query("
    SELECT 
        pg.id,
        pg.mes_referencia,
        pg.valor_pago,
        pg.data_pagamento,
        pg.observacao,
        CONCAT(
            p.nome, ' - ',
            i.descricao, ' (',
            iq.nome, ')'
        ) AS contrato
    FROM pagamentos pg
    JOIN contratos c      ON c.id = pg.contrato_id
    JOIN imoveis i        ON i.id = c.imovel_id
    JOIN proprietarios p  ON p.id = i.proprietario_id
    JOIN inquilinos iq    ON iq.id = c.inquilino_id
    ORDER BY pg.mes_referencia DESC
");

while ($row = $result->fetch_assoc()) {
    $pagamentos[] = $row;
}
?>

<h2>Pagamentos</h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <select name="contrato_id" required>
        <option value="">Selecione o contrato</option>
        <?php foreach ($contratos as $c): ?>
            <option value="<?= $c['id'] ?>"
                <?= ($editar && $editar['contrato_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= $c['contrato'] ?> — R$ <?= number_format($c['valor_aluguel'], 2, ',', '.') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="month" name="mes_referencia"
           value="<?= $editar['mes_referencia'] ?? '' ?>" required>

    <input type="number" step="0.01" name="valor_pago"
           value="<?= $editar['valor_pago'] ?? '' ?>" required>

    <input type="date" name="data_pagamento"
           value="<?= $editar['data_pagamento'] ?? '' ?>" required>

    <input type="text" name="observacao"
           value="<?= $editar['observacao'] ?? '' ?>" placeholder="Observação">

    <button type="submit">Salvar</button>
</form>

<hr>

<table border="1" cellpadding="5">
<tr>
    <th>Contrato</th>
    <th>Mês</th>
    <th>Valor Pago</th>
    <th>Data</th>
    <th>Ações</th>
</tr>

<?php foreach ($pagamentos as $p): ?>
<tr>
    <td><?= $p['contrato'] ?></td>
    <td><?= date('m/Y', strtotime($p['mes_referencia'])) ?></td>
    <td>R$ <?= number_format($p['valor_pago'], 2, ',', '.') ?></td>
    <td><?= date('d/m/Y', strtotime($p['data_pagamento'])) ?></td>
    <td>
        <a href="?edit=<?= $p['id'] ?>">Editar</a> |
        <a href="?delete=<?= $p['id'] ?>"
           onclick="return confirm('Excluir pagamento?')">Excluir</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
