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

    $id             = $_POST['id'] ?? null;
    $imovel_id      = $_POST['imovel_id'];
    $inquilino_id   = $_POST['inquilino_id'];
    $data_inicio    = $_POST['data_inicio'];
    $data_fim       = $_POST['data_fim'] ?: null;
    $dia_vencimento = $_POST['dia_vencimento'];
    $ativo          = isset($_POST['ativo']) ? 1 : 0;

    // novos campos jurídicos
    $tipo_contrato   = $_POST['tipo_contrato'];
    $finalidade      = $_POST['finalidade'];
    $prazo_meses     = $_POST['prazo_meses'];
    $indice_reajuste = $_POST['indice_reajuste'];
    $tipo_garantia   = $_POST['tipo_garantia'];
    $valor_caucao    = $_POST['valor_caucao'] ?: null;

    if ($id) {
        $stmt = $conn->prepare("
            UPDATE contratos SET
                imovel_id = ?,
                inquilino_id = ?,
                data_inicio = ?,
                data_fim = ?,
                dia_vencimento = ?,
                ativo = ?,
                tipo_contrato = ?,
                finalidade = ?,
                prazo_meses = ?,
                indice_reajuste = ?,
                tipo_garantia = ?,
                valor_caucao = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "iissii ssissdi",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $dia_vencimento,
            $ativo,
            $tipo_contrato,
            $finalidade,
            $prazo_meses,
            $indice_reajuste,
            $tipo_garantia,
            $valor_caucao,
            $id
        );
    } else {
        $stmt = $conn->prepare("
            INSERT INTO contratos
            (
                imovel_id, inquilino_id, data_inicio, data_fim,
                dia_vencimento, ativo,
                tipo_contrato, finalidade, prazo_meses,
                indice_reajuste, tipo_garantia, valor_caucao
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissii ssissd",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $dia_vencimento,
            $ativo,
            $tipo_contrato,
            $finalidade,
            $prazo_meses,
            $indice_reajuste,
            $tipo_garantia,
            $valor_caucao
        );
    }

    $stmt->execute();
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
   4) IMÓVEIS ATIVOS
========================= */
$imoveis = [];
$res = $conn->query("
    SELECT i.id,
           CONCAT(p.nome, ' - ', i.descricao) AS imovel
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE i.ativo = 1
    ORDER BY p.nome
");
while ($r = $res->fetch_assoc()) $imoveis[] = $r;

/* =========================
   5) INQUILINOS
========================= */
$inquilinos = [];
$res = $conn->query("SELECT id, nome FROM inquilinos ORDER BY nome");
while ($r = $res->fetch_assoc()) $inquilinos[] = $r;

/* =========================
   6) LISTAGEM
========================= */
$contratos = [];
$res = $conn->query("
    SELECT c.*,
           iq.nome AS inquilino,
           CONCAT(p.nome, ' - ', i.descricao) AS imovel,
           i.valor_aluguel
    FROM contratos c
    JOIN inquilinos iq   ON iq.id = c.inquilino_id
    JOIN imoveis i       ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY c.data_inicio DESC
");
while ($r = $res->fetch_assoc()) $contratos[] = $r;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Contratos</title>
<style>
body { font-family: Arial; margin: 20px; }
input, select { margin: 6px 0; padding: 6px; width: 360px; }
table { border-collapse: collapse; width: 100%; margin-top: 30px; }
th, td { border: 1px solid #ccc; padding: 8px; }
th { background: #eee; }
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
<option value="<?= $i['id'] ?>" <?= ($editar && $editar['imovel_id']==$i['id'])?'selected':'' ?>>
<?= htmlspecialchars($i['imovel']) ?>
</option>
<?php endforeach; ?>
</select>

<label>Inquilino</label>
<select name="inquilino_id" required>
<option value="">Selecione</option>
<?php foreach ($inquilinos as $iq): ?>
<option value="<?= $iq['id'] ?>" <?= ($editar && $editar['inquilino_id']==$iq['id'])?'selected':'' ?>>
<?= htmlspecialchars($iq['nome']) ?>
</option>
<?php endforeach; ?>
</select>

<label>Tipo de Contrato</label>
<select name="tipo_contrato">
<option value="RESIDENCIAL">Residencial</option>
<option value="NAO_RESIDENCIAL">Não Residencial</option>
</select>

<label>Finalidade</label>
<input type="text" name="finalidade" value="<?= $editar['finalidade'] ?? '' ?>">

<label>Prazo (meses)</label>
<input type="number" name="prazo_meses" value="<?= $editar['prazo_meses'] ?? 12 ?>">

<label>Índice de Reajuste</label>
<input type="text" name="indice_reajuste" value="<?= $editar['indice_reajuste'] ?? 'IGP-M' ?>">

<label>Garantia</label>
<select name="tipo_garantia">
<option value="CAUCAO">Caução</option>
<option value="FIADOR">Fiador</option>
<option value="SEGURO">Seguro Fiança</option>
</select>

<label>Valor Caução</label>
<input type="number" step="0.01" name="valor_caucao" value="<?= $editar['valor_caucao'] ?? '' ?>">

<label>Início</label>
<input type="date" name="data_inicio" required value="<?= $editar['data_inicio'] ?? '' ?>">

<label>Fim</label>
<input type="date" name="data_fim" value="<?= $editar['data_fim'] ?? '' ?>">

<label>Dia Vencimento</label>
<input type="number" name="dia_vencimento" min="1" max="31" value="<?= $editar['dia_vencimento'] ?? 5 ?>">

<label>
<input type="checkbox" name="ativo" <?= (!$editar || $editar['ativo'])?'checked':'' ?>>
Ativo
</label>

<button type="submit">Salvar</button>
</form>

<h2>Contratos</h2>
<table>
<tr>
<th>Imóvel</th>
<th>Inquilino</th>
<th>Aluguel</th>
<th>Início</th>
<th>Status</th>
<th>Ações</th>
</tr>
<?php foreach ($contratos as $c): ?>
<tr>
<td><?= htmlspecialchars($c['imovel']) ?></td>
<td><?= htmlspecialchars($c['inquilino']) ?></td>
<td>R$ <?= number_format($c['valor_aluguel'],2,',','.') ?></td>
<td><?= date('d/m/Y', strtotime($c['data_inicio'])) ?></td>
<td><?= $c['ativo']?'Ativo':'Inativo' ?></td>
<td>
    <a href="contratos.php?edit=<?= $c['id'] ?>">Editar</a>

    <a href="contrato_pdf.php?id=<?= $c['id'] ?>" target="_blank">
        Contrato Padrão
    </a>

    <a href="contrato_felipe_pdf.php?id=<?= $c['id'] ?>" target="_blank">
        Contrato Jurídico
    </a>

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
