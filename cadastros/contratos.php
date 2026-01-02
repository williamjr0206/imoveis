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
        $stmt = $conn->prepare("
            UPDATE contratos
            SET imovel_id = ?, inquilino = ?, data_inicio = ?, data_fim = ?,
                valor_aluguel = ?, dia_vencimento = ?, ativo = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "isssdiii",
            $imovel_id,
            $inquilino,
            $data_inicio,
            $data_fim,
            $valor_aluguel,
            $dia_vencimento,
            $ativo,
            $id
        );
        $stmt->execute();
    } else {
        // SALVAR
        $stmt = $conn->prepare("
            INSERT INTO contratos
            (imovel_id, inquilino, data_inicio, data_fim, valor_aluguel, dia_vencimento, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssdii",
            $imovel_id,
            $inquilino,
            $data_inicio,
            $data_fim,
            $valor_aluguel,
            $dia_vencimento,
            $ativo
        );
        $stmt->execute();
    }

    header("Location: contratos.php");
    exit;
}
if (isset($_GET['delete'])) {
    verificaPerfil(['ADMIN']);

    $stmt = $conn->prepare("DELETE FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();

    header("Location: contratos.php");
    exit;
}
$editar = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}
$imoveis = [];
$result = $conn->query("
    SELECT i.id, CONCAT(p.nome, ' - ', i.descricao) AS imovel
    FROM imoveis i
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE i.ativo = 1
    ORDER BY p.nome
");

while ($row = $result->fetch_assoc()) {
    $imoveis[] = $row;
}
$contratos = [];
$result = $conn->query("
    SELECT c.*, 
           CONCAT(p.nome, ' - ', i.descricao) AS imovel
    FROM contratos c
    JOIN imoveis i ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    ORDER BY c.data_inicio DESC
");

while ($row = $result->fetch_assoc()) {
    $contratos[] = $row;
}
