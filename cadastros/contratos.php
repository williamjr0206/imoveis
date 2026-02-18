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

    $id              = $_POST['id'] ?? null;
    $imovel_id       = $_POST['imovel_id'];
    $inquilino_id    = $_POST['inquilino_id'];
    $data_inicio     = $_POST['data_inicio'];
    $data_fim        = $_POST['data_fim'] ?: null;
    $dia_vencimento  = $_POST['dia_vencimento'];
    $prazo_meses     = $_POST['prazo_meses'];
    $finalidade      = $_POST['finalidade'];
    $indice_reajuste = $_POST['indice_reajuste'];
    $tipo_contrato   = $_POST['tipo_contrato'];
    $tipo_garantia   = $_POST['tipo_garantia'];
    $valor_caucao    = $_POST['valor_caucao'] ?: null;
    $ativo           = isset($_POST['ativo']) ? 1 : 0;

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
                ativo = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "iissiiisssdii",
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
            $ativo,
            $id
        );
    } else {
        $stmt = $conn->prepare("
            INSERT INTO contratos
            (imovel_id, inquilino_id, data_inicio, data_fim, dia_vencimento,
             prazo_meses, finalidade, indice_reajuste, tipo_contrato,
             tipo_garantia, valor_caucao, ativo)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "iissiiisssdi",
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
            $ativo
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
   4) DADOS AUXILIARES
========================= */
$imoveis = $conn->query("
    SELECT i.id, CONCAT(p.nome,' - ',i.descricao) AS imovel
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE i.ativo = 1
")->fetch_all(MYSQLI_ASSOC);

$inquilinos = $conn->query("
    SELECT id, nome FROM inquilinos ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

/* =========================
   5) LISTAGEM
========================= */
$contratos = $conn->query("
    SELECT c.id, c.data_inicio, c.dia_vencimento, c.ativo,
           iq.nome AS inquilino,
           CONCAT(p.nome,' - ',i.descricao) AS imovel,
           i.valor_aluguel
    FROM contratos c
    JOIN inquilinos iq ON iq.id = c.inquilino_id
    JOIN imoveis i ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY c.data_inicio DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Contratos</title>
<style>
body { font-family: Arial; margin: 20px; }
input, select { width: 360px; padding: 6px; margin: 5px 0; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 8px; }
th { background: #eee; }
a { margin-right: 8px; }
</style>
</head>

<body>

<h2><?= $editar ? 'Editar Contrato' : 'Novo Contrato' ?></h2>

<form method="post">
<input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

<select name="imovel_id" required>
<option value="">Imóvel</option>
<?php foreach ($imoveis as $i): ?>
<option value="<?= $i['id'] ?>" <?= ($editar && $editar['imovel_id']==$i['id'])?'selected':'' ?>>
<?= htmlspecialchars($i['imovel']) ?>
</option>
<?php endforeach; ?>
</select>

<select name="inquilino_id" required>
<option value="">Inquilino</option>
<?php foreach ($inquilinos as $iq): ?>
<option value="<?= $iq['id'] ?>" <?= ($editar && $editar['inquilino_id']==$iq['id'])?'selected':'' ?>>
<?= htmlspecialchars($iq['nome']) ?>
</option>
<?php endforeach; ?>
</select>

<input type="date" name="data_inicio" required value="<?= $editar['data_inicio'] ?? '' ?>">
<input type="date" name="data_fim" value="<?= $editar['data_fim'] ?? '' ?>">
<input type="number" name="dia_vencimento" min="1" max="31" required value="<?= $editar['dia_vencimento'] ?? '' ?>">
<input type="number" name="prazo_meses" required placeholder="Prazo (meses)" value="<?= $editar['prazo_meses'] ?? '' ?>">

<input type="text" name="finalidade" placeholder="Finalidade do contrato" value="<?= $editar['finalidade'] ?? '' ?>">
<input type="text" name="indice_reajuste" placeholder="Índice de reajuste" value="<?= $editar['indice_reajuste'] ?? '' ?>">
<input type="text" name="tipo_contrato" placeholder="Tipo de contrato" value="<?= $editar['tipo_contrato'] ?? '' ?>">
<input type="text" name="tipo_garantia" placeholder="Tipo de garantia" value="<?= $editar['tipo_garantia'] ?? '' ?>">
<input type="number" step="0.01" name="valor_caucao" placeholder="Valor da caução" value="<?= $editar['valor_caucao'] ?? '' ?>">

<label>
<input type="checkbox" name="ativo" <?= (!$editar || $editar['ativo'])?'checked':'' ?>>
 Contrato ativo
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
<th>Venc.</th>
<th>Status</th>
<th>Ações</th>
</tr>

<?php foreach ($contratos as $c): ?>
<tr>
<td><?= htmlspecialchars($c['imovel']) ?></td>
<td><?= htmlspecialchars($c['inquilino']) ?></td>
<td>R$ <?= number_format($c['valor_aluguel'],2,',','.') ?></td>
<td><?= date('d/m/Y',strtotime($c['data_inicio'])) ?></td>
<td><?= $c['dia_vencimento'] ?></td>
<td><?= $c['ativo']?'Ativo':'Inativo' ?></td>
<td>
<a href="contratos.php?edit=<?= $c['id'] ?>">Editar</a>
<a href="contrato_pdf.php?id=<?= $c['id'] ?>" target="_blank">Modelo 1</a>
<a href="contrato_modelo2_pdf.php?id=<?= $c['id'] ?>" target="_blank">Modelo 2</a>
</td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
