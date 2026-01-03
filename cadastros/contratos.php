<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../includes/menu.php';

verificaPerfil(['ADMIN','OPERADOR']);

/* =====================
   VARIÁVEIS PADRÃO
===================== */
$id             = $_POST['id']            ?? null;
$imovel_id      = $_POST['imovel_id']     ?? null;
$inquilino_id   = $_POST['inquilino_id']  ?? null;
$data_inicio    = $_POST['data_inicio']   ?? null;
$data_fim       = $_POST['data_fim']      ?? null;
$valor_aluguel  = $_POST['valor_aluguel'] ?? null;
$dia_vencimento = $_POST['dia_vencimento']?? null;
$ativo          = isset($_POST['ativo']) ? 1 : 0;

/* =====================
   SALVAR / EDITAR
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($id) {
        $stmt = $conn->prepare("
            UPDATE contratos SET
                imovel_id = ?,
                inquilino_id = ?,
                data_inicio = ?,
                data_fim = ?,
                valor_aluguel = ?,
                dia_vencimento = ?,
                ativo = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "iissdiii",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $valor_aluguel,
            $dia_vencimento,
            $ativo,
            $id
        );
    } else {
        $stmt = $conn->prepare("
            INSERT INTO contratos
            (imovel_id, inquilino_id, data_inicio, data_fim, valor_aluguel, dia_vencimento, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissdii",
            $imovel_id,
            $inquilino_id,
            $data_inicio,
            $data_fim,
            $valor_aluguel,
            $dia_vencimento,
            $ativo
        );
    }

    $stmt->execute();
    header("Location: contratos.php");
    exit;
}

/* =====================
   EXCLUIR
===================== */
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: contratos.php");
    exit;
}

/* =====================
   EDITAR
===================== */
$editar = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

/* =====================
   LISTA DE IMÓVEIS
===================== */
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

/* =====================
   LISTA DE INQUILINOS
===================== */
$inquilinos = [];
$result = $conn->query("
    SELECT id, nome
    FROM inquilinos
    ORDER BY nome
");

while ($row = $result->fetch_assoc()) {
    $inquilinos[] = $row;
}

/* =====================
   LISTA DE CONTRATOS
===================== */
$contratos = [];
$result = $conn->query("
    SELECT c.*,
           iq.nome AS inquilino,
           CONCAT(p.nome, ' - ', i.descricao) AS imovel
    FROM contratos c
    JOIN inquilinos iq   ON iq.id = c.inquilino_id
    JOIN imoveis i       ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY c.data_inicio DESC
");

while ($row = $result->fetch_assoc()) {
    $contratos[] = $row;
}
