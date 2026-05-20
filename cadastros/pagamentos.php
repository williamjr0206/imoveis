<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

verificaPerfil(['ADMIN', 'OPERADOR']);

$hoje = date('Y-m-d');

/* =========================
   ATUALIZA ATRASADOS
========================= */
$stmtAtr = $conn->prepare("
    SELECT 
        pg.id,
        pg.valor_total,
        pg.data_vencimento,
        c.multa_percentual,
        c.juros_percentual
    FROM pagamentos pg
    JOIN contratos c ON c.id = pg.contrato_id
    WHERE pg.status <> 'PAGO'
      AND pg.data_vencimento < ?
");
$stmtAtr->bind_param("s", $hoje);
$stmtAtr->execute();
$resAtr = $stmtAtr->get_result();

while ($p = $resAtr->fetch_assoc()) {

    $valor_base = (float)$p['valor_total'];
    $multa_percentual = (float)$p['multa_percentual'];
    $juros_percentual = (float)$p['juros_percentual'];

    $multa = $valor_base * ($multa_percentual / 100);

    $venc = new DateTime($p['data_vencimento']);
    $hj = new DateTime($hoje);
    $dias_atraso = $venc->diff($hj)->days;

    $juros = ($valor_base * ($juros_percentual / 100) / 30) * $dias_atraso;

    $valor_corrigido = $valor_base + $multa + $juros;

    $stmtUp = $conn->prepare("
        UPDATE pagamentos
        SET multa = ?,
            juros = ?,
            valor_total = ?,
            status = 'ATRASADO'
        WHERE id = ?
    ");
    $stmtUp->bind_param("dddi", $multa, $juros, $valor_corrigido, $p['id']);
    $stmtUp->execute();
}

/* =========================
   TRATAMENTO POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id             = $_POST['id'] ?? null;
    $contrato_id    = $_POST['contrato_id'];
    $mes_referencia = $_POST['mes_referencia'];
    $valor_pago     = $_POST['valor_pago'] !== '' ? $_POST['valor_pago'] : null;
    $data_pagamento = $_POST['data_pagamento'] !== '' ? $_POST['data_pagamento'] : null;
    $observacao     = $_POST['observacao'] ?? null;

    if (strlen($mes_referencia) === 7) {
        $mes_referencia .= '-01';
    }

    if ($id) {

        $stmtBusca = $conn->prepare("
            SELECT 
                pg.valor_total,
                pg.data_vencimento,
                c.multa_percentual,
                c.juros_percentual
            FROM pagamentos pg
            JOIN contratos c ON c.id = pg.contrato_id
            WHERE pg.id = ?
        ");
        $stmtBusca->bind_param("i", $id);
        $stmtBusca->execute();
        $pag = $stmtBusca->get_result()->fetch_assoc();

        $valor_base = (float)$pag['valor_total'];
        $data_vencimento = $pag['data_vencimento'];
        $multa_percentual = (float)$pag['multa_percentual'];
        $juros_percentual = (float)$pag['juros_percentual'];

        $multa = 0.00;
        $juros = 0.00;
        $status = 'PENDENTE';

        if (!empty($valor_pago) && !empty($data_pagamento)) {

            $status = 'PAGO';

            if ($data_pagamento > $data_vencimento) {
                $multa = $valor_base * ($multa_percentual / 100);

                $venc = new DateTime($data_vencimento);
                $pagto = new DateTime($data_pagamento);
                $dias_atraso = $venc->diff($pagto)->days;

                $juros = ($valor_base * ($juros_percentual / 100) / 30) * $dias_atraso;
            }

        } else {

            if ($hoje > $data_vencimento) {
                $status = 'ATRASADO';

                $multa = $valor_base * ($multa_percentual / 100);

                $venc = new DateTime($data_vencimento);
                $hj = new DateTime($hoje);
                $dias_atraso = $venc->diff($hj)->days;

                $juros = ($valor_base * ($juros_percentual / 100) / 30) * $dias_atraso;
            }
        }

        $valor_total_corrigido = $valor_base + $multa + $juros;

        $stmt = $conn->prepare("
            UPDATE pagamentos
            SET contrato_id = ?,
                mes_referencia = ?,
                multa = ?,
                juros = ?,
                valor_total = ?,
                valor_pago = ?,
                data_pagamento = ?,
                observacao = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "isddddsssi",
            $contrato_id,
            $mes_referencia,
            $multa,
            $juros,
            $valor_total_corrigido,
            $valor_pago,
            $data_pagamento,
            $observacao,
            $status,
            $id
        );

        $stmt->execute();
    }

    header("Location: pagamentos.php?contrato_id=" . urlencode($contrato_id));
    exit;
}

/* =========================
   EXCLUIR
========================= */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $idDelete = $_GET['delete'];
    $contratoFiltro = $_GET['contrato_id'] ?? '';

    $stmt = $conn->prepare("DELETE FROM pagamentos WHERE id = ?");
    $stmt->bind_param("i", $idDelete);
    $stmt->execute();

    header("Location: pagamentos.php?contrato_id=" . urlencode($contratoFiltro));
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
    ORDER BY p.nome, i.descricao, iq.nome
");

while ($row = $result->fetch_assoc()) {
    $contratos[] = $row;
}

/* =========================
   FILTRO POR CONTRATO
========================= */
$filtro_contrato = $_GET['contrato_id'] ?? '';

$where = "";
if (!empty($filtro_contrato)) {
    $where = " WHERE pg.contrato_id = " . intval($filtro_contrato);
}

/* =========================
   LISTAGEM PAGAMENTOS
========================= */
$pagamentos = [];

$result = $conn->query("
    SELECT 
        pg.id,
        pg.contrato_id,
        pg.mes_referencia,
        pg.multa,
        pg.juros,
        pg.valor_total,
        pg.valor_pago,
        pg.data_vencimento,
        pg.data_pagamento,
        pg.observacao,
        pg.status,
        c.multa_percentual,
        c.juros_percentual,
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
    $where
    ORDER BY pg.mes_referencia DESC
");

while ($row = $result->fetch_assoc()) {
    $pagamentos[] = $row;
}
?>

<h2>Pagamentos</h2>
<meta name="viewport" content="width=device-width, initial-scale=1.0" charset="UTF-8">
<link rel="stylesheet" href="../assets/css/mobile.css">
<form method="get" style="margin-bottom:15px;">
    <select name="contrato_id">
        <option value="">Todos os contratos</option>
        <?php foreach ($contratos as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($filtro_contrato == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['contrato']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filtrar</button>
    <a href="pagamentos.php">Limpar</a>
</form>

<hr>

<h3><?= $editar ? 'Editar Pagamento' : 'Pagamento' ?></h3>

<form method="post">
    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

    <select name="contrato_id" required>
        <option value="">Selecione o contrato</option>
        <?php foreach ($contratos as $c): ?>
            <option value="<?= $c['id'] ?>"
                <?= ($editar && $editar['contrato_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['contrato']) ?> — R$ <?= number_format((float)$c['valor_aluguel'], 2, ',', '.') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="month" name="mes_referencia"
           value="<?= $editar['mes_referencia'] ?? '' ?>" required>

    <input type="number" step="0.01" name="valor_pago"
           value="<?= $editar['valor_pago'] ?? '' ?>" placeholder="Valor pago">

    <input type="date" name="data_pagamento"
           value="<?= $editar['data_pagamento'] ?? '' ?>">

    <input type="text" name="observacao"
           value="<?= htmlspecialchars($editar['observacao'] ?? '') ?>" placeholder="Observação">

    <button type="submit">Salvar</button>
</form>

<hr>

<table border="1" cellpadding="5">
<tr>
    <th>Contrato</th>
    <th>Mês</th>
    <th>Vencimento</th>
    <th>Multa</th>
    <th>Juros</th>
    <th>Valor Atualizado</th>
    <th>Valor Pago</th>
    <th>Data Pagamento</th>
    <th>Status</th>
    <th>Ações</th>
</tr>

<?php foreach ($pagamentos as $p): ?>
<tr>
    <td><?= htmlspecialchars($p['contrato']) ?></td>

    <td><?= !empty($p['mes_referencia']) ? date('m/Y', strtotime($p['mes_referencia'])) : '-' ?></td>

    <td><?= !empty($p['data_vencimento']) ? date('d/m/Y', strtotime($p['data_vencimento'])) : '-' ?></td>

    <td>
        R$ <?= number_format((float)($p['multa'] ?? 0), 2, ',', '.') ?>
        <br>
        <small><?= number_format((float)$p['multa_percentual'], 2, ',', '.') ?>%</small>
    </td>

    <td>
        R$ <?= number_format((float)($p['juros'] ?? 0), 2, ',', '.') ?>
        <br>
        <small><?= number_format((float)$p['juros_percentual'], 2, ',', '.') ?>% mês</small>
    </td>

    <td>R$ <?= number_format((float)($p['valor_total'] ?? 0), 2, ',', '.') ?></td>

    <td>
        <?= $p['valor_pago'] !== null
            ? 'R$ ' . number_format((float)$p['valor_pago'], 2, ',', '.')
            : '-' ?>
    </td>

    <td>
        <?= !empty($p['data_pagamento'])
            ? date('d/m/Y', strtotime($p['data_pagamento']))
            : '-' ?>
    </td>

    <td><?= htmlspecialchars($p['status'] ?? 'PENDENTE') ?></td>

    <td>
        <a href="?edit=<?= $p['id'] ?>&contrato_id=<?= $filtro_contrato ?>">Editar</a> |
        <a href="?delete=<?= $p['id'] ?>&contrato_id=<?= $filtro_contrato ?>"
           onclick="return confirm('Excluir pagamento?')">Excluir</a> |
        <a href="../relatorios/recibo.php?id=<?= $p['id'] ?>" target="_blank">Recibo PDF</a>
    </td>
</tr>
<?php endforeach; ?>
</table>