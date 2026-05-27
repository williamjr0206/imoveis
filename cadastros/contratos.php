<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =========================
   FUNÇÃO: GERAR PAGAMENTOS
========================= */
function gerarPagamentosContrato($conn, $contrato_id, $imovel_id, $data_inicio, $data_fim, $dia_vencimento, $prazo_meses)
{
    $stmtValor = $conn->prepare("
        SELECT valor_aluguel 
        FROM imoveis 
        WHERE id = ?
    ");
    $stmtValor->bind_param("i", $imovel_id);
    $stmtValor->execute();

    $resValor = $stmtValor->get_result()->fetch_assoc();

    if (!$resValor) {
        return;
    }

    $valor_aluguel = $resValor['valor_aluguel'];

    if (empty($prazo_meses) || $prazo_meses <= 0) {
        return;
    }

    $criado_por = $_SESSION['id_usuario'] ?? null;
    $dataBase = new DateTime(substr($data_inicio, 0, 7) . '-01');

    for ($i = 0; $i < $prazo_meses; $i++) {

        $base = clone $dataBase;
        $base->modify("+$i month");

        $ano = $base->format('Y');
        $mes = $base->format('m');

        $mes_referencia = $ano . '-' . $mes . '-01';

        $ultimo_dia_mes = cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$ano);
        $dia_final = min((int)$dia_vencimento, $ultimo_dia_mes);

        $data_vencimento = $ano . '-' . $mes . '-' . str_pad($dia_final, 2, '0', STR_PAD_LEFT);

        if (!empty($data_fim) && $data_vencimento > $data_fim) {
            break;
        }

        $stmtPag = $conn->prepare("
            INSERT INTO pagamentos
            (
                contrato_id,
                mes_referencia,
                multa,
                juros,
                valor_total,
                data_vencimento,
                valor_pago,
                data_pagamento,
                observacao,
                status,
                criado_por
            )
            VALUES (?, ?, 0.00, 0.00, ?, ?, NULL, NULL, NULL, 'PENDENTE', ?)
        ");

        $stmtPag->bind_param(
            "isdsi",
            $contrato_id,
            $mes_referencia,
            $valor_aluguel,
            $data_vencimento,
            $criado_por
        );

        $stmtPag->execute();
    }
}

/* =========================
   1) SALVAR / EDITAR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id                 = $_POST['id'] ?? null;
    $imovel_id          = $_POST['imovel_id'];
    $inquilino_id       = $_POST['inquilino_id'];
    $data_inicio        = $_POST['data_inicio'];
    $data_fim           = $_POST['data_fim'] ?: null;
    $dia_vencimento     = $_POST['dia_vencimento'];
    $prazo_meses        = $_POST['prazo_meses'];
    $finalidade         = $_POST['finalidade'];
    $indice_reajuste    = $_POST['indice_reajuste'];
    $tipo_contrato      = $_POST['tipo_contrato'];
    $tipo_garantia      = $_POST['tipo_garantia'];
    $valor_caucao       = $_POST['valor_caucao'] ?: null;
    $multa_percentual   = $_POST['multa_percentual'] ?: 0;
    $juros_percentual   = $_POST['juros_percentual'] ?: 0;
    $ativo              = isset($_POST['ativo']) ? 1 : 0;

    if ($id) {

        $stmt = $conn->prepare("
            UPDATE contratos SET
                imovel_id = ?,
                inquilino_id = ?,
                data_inicio = ?,
                data_fim = ?,
                dia_vencimento = ?,
                prazo_meses = ?,
                finalidade = ?,
                indice_reajuste = ?,
                tipo_contrato = ?,
                tipo_garantia = ?,
                valor_caucao = ?,
                multa_percentual = ?,
                juros_percentual = ?,
                ativo = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "iissiiisssdddii",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $dia_vencimento,
            $prazo_meses,
            $finalidade,
            $indice_reajuste,
            $tipo_contrato,
            $tipo_garantia,
            $valor_caucao,
            $multa_percentual,
            $juros_percentual,
            $ativo,
            $id
        );

        $stmt->execute();

    } else {

        $stmt = $conn->prepare("
            INSERT INTO contratos
            (
                imovel_id,
                inquilino_id,
                data_inicio,
                data_fim,
                dia_vencimento,
                prazo_meses,
                finalidade,
                indice_reajuste,
                tipo_contrato,
                tipo_garantia,
                valor_caucao,
                multa_percentual,
                juros_percentual,
                ativo
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "iissiiisssdddi",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $dia_vencimento,
            $prazo_meses,
            $finalidade,
            $indice_reajuste,
            $tipo_contrato,
            $tipo_garantia,
            $valor_caucao,
            $multa_percentual,
            $juros_percentual,
            $ativo
        );

        $stmt->execute();

        $novo_contrato_id = $conn->insert_id;

        gerarPagamentosContrato(
            $conn,
            $novo_contrato_id,
            $imovel_id,
            $data_inicio,
            $data_fim,
            $dia_vencimento,
            $prazo_meses
        );
    }

    header("Location: contratos.php");
    exit;
}

/* =========================
   2) EXCLUIR
========================= */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: contratos.php");
    exit;
}

/* =========================
   3) EDIÇÃO
========================= */
$editar = null;

if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =========================
   4) DADOS AUXILIARES
========================= */
$imoveis = $conn->query("
    SELECT 
        i.id, 
        CONCAT(p.nome,' - ',i.descricao) AS imovel
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE i.ativo = 1
    ORDER BY p.nome, i.descricao
")->fetch_all(MYSQLI_ASSOC);

$inquilinos = $conn->query("
    SELECT id, nome 
    FROM inquilinos 
    ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

/* =========================
   5) LISTAGEM
========================= */
$contratos = $conn->query("
    SELECT 
        c.id, 
        c.data_inicio, 
        c.dia_vencimento, 
        c.ativo,
        c.multa_percentual,
        c.juros_percentual,
        iq.nome AS inquilino,
        CONCAT(p.nome,' - ',i.descricao) AS imovel,
        i.valor_aluguel
    FROM contratos c
    JOIN inquilinos iq ON iq.id = c.inquilino_id
    JOIN imoveis i ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY c.data_inicio DESC
")->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../includes/menu.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/mobile.css">

<title>Contratos</title>

<style>
body { font-family: Arial; margin: 20px; }
input, select { width: 360px; padding: 6px; margin: 5px 0; display: block; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 8px; }
th { background: #eee; }
a { margin-right: 8px; }
button { padding: 8px 16px; margin-top: 10px; }
</style>
</head>

<body>

<h2><?= $editar ? 'Editar Contrato' : 'Novo Contrato' ?></h2>

<form method="post">

<input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

<select name="imovel_id" required>
    <option value="">Imóvel</option>
    <?php foreach ($imoveis as $i): ?>
        <option value="<?= $i['id'] ?>" <?= ($editar && $editar['imovel_id'] == $i['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($i['imovel']) ?>
        </option>
    <?php endforeach; ?>
</select>

<select name="inquilino_id" required>
    <option value="">Inquilino</option>
    <?php foreach ($inquilinos as $iq): ?>
        <option value="<?= $iq['id'] ?>" <?= ($editar && $editar['inquilino_id'] == $iq['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($iq['nome']) ?>
        </option>
    <?php endforeach; ?>
</select>

<input type="date" name="data_inicio" required value="<?= $editar['data_inicio'] ?? '' ?>">

<input type="date" name="data_fim" value="<?= $editar['data_fim'] ?? '' ?>">

<input type="number" name="dia_vencimento" min="1" max="31" required placeholder="Dia do vencimento" value="<?= $editar['dia_vencimento'] ?? '' ?>">

<input type="number" name="prazo_meses" required placeholder="Prazo (meses)" value="<?= $editar['prazo_meses'] ?? '' ?>">

<input type="text" name="finalidade" placeholder="Finalidade do contrato" value="<?= $editar['finalidade'] ?? '' ?>">

<input type="text" name="indice_reajuste" placeholder="Índice de reajuste" value="<?= $editar['indice_reajuste'] ?? '' ?>">

<input type="text" name="tipo_contrato" placeholder="Tipo de contrato" value="<?= $editar['tipo_contrato'] ?? '' ?>">

<input type="text" name="tipo_garantia" placeholder="Tipo de garantia" value="<?= $editar['tipo_garantia'] ?? '' ?>">

<input type="number" step="0.01" name="valor_caucao" placeholder="Valor da caução" value="<?= $editar['valor_caucao'] ?? '' ?>">

<input type="number" step="0.01" name="multa_percentual" placeholder="Multa por atraso (%)" value="<?= $editar['multa_percentual'] ?? '' ?>">

<input type="number" step="0.01" name="juros_percentual" placeholder="Juros ao mês (%)" value="<?= $editar['juros_percentual'] ?? '' ?>">

<label>
    <input type="checkbox" name="ativo" style="width:auto; display:inline;" <?= (!$editar || $editar['ativo']) ? 'checked' : '' ?>>
    Contrato ativo
</label>

<br>

<button type="submit">Salvar</button>

</form>

<h2>Contratos</h2>

<table>
<tr>
    <th>Imóvel</th>
    <th>Inquilino</th>
    <th>Aluguel</th>
    <th>Início</th>
    <th>Venc.</th>
    <th>Multa %</th>
    <th>Juros % mês</th>
    <th>Status</th>
    <th>Ações</th>
</tr>

<?php foreach ($contratos as $c): ?>
<tr>
    <td><?= htmlspecialchars($c['imovel']) ?></td>
    <td><?= htmlspecialchars($c['inquilino']) ?></td>
    <td>R$ <?= number_format((float)$c['valor_aluguel'], 2, ',', '.') ?></td>
    <td><?= date('d/m/Y', strtotime($c['data_inicio'])) ?></td>
    <td><?= $c['dia_vencimento'] ?></td>
    <td><?= number_format((float)$c['multa_percentual'], 2, ',', '.') ?>%</td>
    <td><?= number_format((float)$c['juros_percentual'], 2, ',', '.') ?>%</td>
    <td><?= $c['ativo'] ? 'Ativo' : 'Inativo' ?></td>
    <td>
        <a href="contratos.php?edit=<?= $c['id'] ?>">Editar</a>
        <a href="contrato_pdf.php?id=<?= $c['id'] ?>" target="_blank">Modelo 1</a>
        <a href="contrato_modelo2_pdf.php?id=<?= $c['id'] ?>" target="_blank">Modelo 2</a>
        <a href="contrato_modelo3_pdf.php?id=<?= $c['id'] ?>" target="_blank">Modelo 3</a>
        <a href="contrato_docx.php?id=<?= $c['id'] ?>" target="_blank">Word</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>