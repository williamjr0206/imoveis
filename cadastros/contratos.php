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

    if ($id) {
        // EDITAR
        $stmt = $conn->prepare("
            UPDATE contratos
            SET imovel_id = ?, inquilino_id = ?, data_inicio = ?, data_fim = ?, 
                dia_vencimento = ?, ativo = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "iissiii",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $dia_vencimento,
            $ativo,
            $id
        );
    } else {
        // INSERIR
        $stmt = $conn->prepare("
            INSERT INTO contratos
            (imovel_id, inquilino_id, data_inicio, data_fim, dia_vencimento, ativo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissii",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $dia_vencimento,
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
   3) CARREGAR EDIÇÃO
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
$result = $conn->query("
    SELECT i.id,
           CONCAT(p.nome, ' - ', i.descricao) AS imovel
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE i.ativo = 1
    ORDER BY p.nome
");
while ($row = $result->fetch_assoc()) {
    $imoveis[] = $row;
}

/* =========================
   5) INQUILINOS
========================= */
$inquilinos = [];
$result = $conn->query("SELECT id, nome FROM inquilinos ORDER BY nome");
while ($row = $result->fetch_assoc()) {
    $inquilinos[] = $row;
}

/* =========================
   6) LISTAGEM
========================= */
$contratos = [];
$result = $conn->query("
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
while ($row = $result->fetch_assoc()) {
    $contratos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Contratos</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { margin-bottom: 30px; }
        input, select { margin: 6px 0; padding: 6px; width: 350px; display: block; }
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
    <select name="inquilino_id" required>
        <option value="">Selecione</option>
        <?php foreach ($inquilinos as $iq): ?>
            <option value="<?= $iq['id'] ?>"
                <?= ($editar && $editar['inquilino_id'] == $iq['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($iq['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Data de Início</label>
    <input type="date" name="data_inicio" required
           value="<?= $editar['data_inicio'] ?? '' ?>">

    <label>Data de Fim</label>
    <input type="date" name="data_fim"
           value="<?= $editar['data_fim'] ?? '' ?>">

    <label>Dia de Vencimento</label>
    <input type="number" name="dia_vencimento" min="1" max="31" required
           value="<?= $editar['dia_vencimento'] ?? '' ?>">

    <label>
        <input type="checkbox" name="ativo"
            <?= (!$editar || $editar['ativo']) ? 'checked' : '' ?>>
        Contrato ativo
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
        <th>Aluguel (R$)</th>
        <th>Início</th>
        <th>Venc.</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>

    <?php foreach ($contratos as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['imovel']) ?></td>
            <td><?= htmlspecialchars($c['inquilino']) ?></td>
            <td><?= number_format($c['valor_aluguel'], 2, ',', '.') ?></td>
            <td><?= date('d/m/Y', strtotime($c['data_inicio'])) ?></td>
            <td><?= $c['dia_vencimento'] ?></td>
            <td><?= $c['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <a href="contratos.php?edit=<?= $c['id'] ?>">Editar</a>

                <a href="contratos.php?delete=<?= $c['id'] ?>"
                onclick="return confirm('Deseja excluir este contrato?')">
                Excluir
                </a>

                <a href="contrato_pdf.php?id=<?= $c['id'] ?>" target="_blank">
                    📄 Emitir Contrato
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
