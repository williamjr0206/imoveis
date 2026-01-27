<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =========================
   SALVAR / EDITAR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? null;

    $tipo_pessoa = $_POST['tipo_pessoa'];
    $nome        = $_POST['nome'];
    $cpf         = $_POST['cpf'] ?? null;
    $cnpj        = $_POST['cnpj'] ?? null;
    $endereco    = $_POST['endereco'];

    $rep_nome = $_POST['representante_nome'] ?? null;
    $rep_cpf  = $_POST['representante_cpf'] ?? null;

    if ($tipo_pessoa === 'PF') {
        $cnpj = null;
        $rep_nome = null;
        $rep_cpf = null;
    } else {
        $cpf = null;
    }

    if ($id) {
        $stmt = $conn->prepare("
            UPDATE inquilinos
            SET tipo_pessoa = ?, nome = ?, cpf = ?, cnpj = ?, 
                representante_nome = ?, representante_cpf = ?, endereco = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssssi",
            $tipo_pessoa,
            $nome,
            $cpf,
            $cnpj,
            $rep_nome,
            $rep_cpf,
            $endereco,
            $id
        );
    } else {
        $stmt = $conn->prepare("
            INSERT INTO inquilinos
            (tipo_pessoa, nome, cpf, cnpj, representante_nome, representante_cpf, endereco)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssss",
            $tipo_pessoa,
            $nome,
            $cpf,
            $cnpj,
            $rep_nome,
            $rep_cpf,
            $endereco
        );
    }

    $stmt->execute();
    header("Location: inquilinos.php");
    exit;
}

/* =========================
   EXCLUIR
========================= */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM inquilinos WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: inquilinos.php");
    exit;
}

/* =========================
   EDITAR
========================= */
$editar = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM inquilinos WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =========================
   LISTAGEM
========================= */
$inquilinos = [];
$result = $conn->query("SELECT * FROM inquilinos ORDER BY nome");
while ($row = $result->fetch_assoc()) {
    $inquilinos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Inquilinos</title>
<style>
body { font-family: Arial; margin: 20px; }
input, select, textarea { width: 360px; padding: 6px; margin: 5px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 8px; }
th { background: #eee; }
</style>

<script>
function togglePessoa() {
    let tipo = document.getElementById('tipo_pessoa').value;
    document.getElementById('pf').style.display = (tipo === 'PF') ? 'block' : 'none';
    document.getElementById('pj').style.display = (tipo === 'PJ') ? 'block' : 'none';
}
</script>
</head>
<body>

<h2><?= $editar ? 'Editar Inquilino' : 'Novo Inquilino' ?></h2>

<form method="post">
<input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">

<label>Tipo de Pessoa</label>
<select name="tipo_pessoa" id="tipo_pessoa" onchange="togglePessoa()" required>
    <option value="PF" <?= ($editar['tipo_pessoa'] ?? '') === 'PF' ? 'selected' : '' ?>>Pessoa Física</option>
    <option value="PJ" <?= ($editar['tipo_pessoa'] ?? '') === 'PJ' ? 'selected' : '' ?>>Pessoa Jurídica</option>
</select>

<label>Nome / Razão Social</label>
<input type="text" name="nome" required value="<?= $editar['nome'] ?? '' ?>">

<div id="pf">
    <label>CPF</label>
    <input type="text" name="cpf" value="<?= $editar['cpf'] ?? '' ?>">
</div>

<div id="pj" style="display:none">
    <label>CNPJ</label>
    <input type="text" name="cnpj" value="<?= $editar['cnpj'] ?? '' ?>">

    <label>Representante Legal</label>
    <input type="text" name="representante_nome" value="<?= $editar['representante_nome'] ?? '' ?>">

    <label>CPF do Representante</label>
    <input type="text" name="representante_cpf" value="<?= $editar['representante_cpf'] ?? '' ?>">
</div>

<label>Endereço</label>
<textarea name="endereco" required><?= $editar['endereco'] ?? '' ?></textarea>

<button type="submit">Salvar</button>
<?php if ($editar): ?>
<a href="inquilinos.php">Cancelar</a>
<?php endif; ?>
</form>

<h2>Lista de Inquilinos</h2>

<table>
<tr>
    <th>Nome</th>
    <th>Tipo</th>
    <th>Documento</th>
    <th>Ações</th>
</tr>

<?php foreach ($inquilinos as $i): ?>
<tr>
    <td><?= htmlspecialchars($i['nome']) ?></td>
    <td><?= $i['tipo_pessoa'] ?></td>
    <td><?= $i['tipo_pessoa'] === 'PF' ? $i['cpf'] : $i['cnpj'] ?></td>
    <td>
        <a href="inquilinos.php?edit=<?= $i['id'] ?>">Editar</a>
        <a href="inquilinos.php?delete=<?= $i['id'] ?>"
           onclick="return confirm('Deseja excluir este inquilino?')">
           Excluir
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<script>togglePessoa();</script>
</body>
</html>
