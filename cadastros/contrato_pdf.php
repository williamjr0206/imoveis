<?php
ob_start();
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../vendedor/fpdf/fpdf.php';

verificaPerfil(['ADMIN','OPERADOR']);

$contratoId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT *
    FROM vw_contrato_base
    WHERE contrato_id = ?
");
$stmt->bind_param("i", $contratoId);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    die('Contrato não encontrado.');
}

/* ===============================
   TRATAMENTO DE DADOS
=============================== */

// LOCADOR
$locadorNome = $dados['locador_nome'];
$locadorCpf  = $dados['locador_cpf'];

// LOCATÁRIO
if ($dados['tipo_pessoa'] === 'PJ') {
    $locatario = $dados['inquilino_nome'];
    $docLocatario = 'CNPJ: ' . $dados['cnpj'];
    $representante = 'Representado por ' . $dados['representante_nome'] .
                     ', CPF ' . $dados['representante_cpf'];
} else {
    $locatario = $dados['inquilino_nome'];
    $docLocatario = 'CPF: ' . $dados['cpf'];
    $representante = '';
}

// IMÓVEL
$imovel = $dados['imovel_descricao'];
$endereco = $dados['endereco'];

// CONTRATO
$dataInicio = date('d/m/Y', strtotime($dados['data_inicio']));
$dataFim    = $dados['data_fim'] ? date('d/m/Y', strtotime($dados['data_fim'])) : 'prazo indeterminado';
$valor      = number_format($dados['valor_aluguel'], 2, ',', '.');
$vencimento = $dados['dia_vencimento'];

/* ===============================
   PDF
=============================== */

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',11);

$pdf->MultiCell(0, 6,
"CONTRATO DE LOCACAO DE IMOVEL RESIDENCIAL

LOCADOR: $locadorNome, CPF $locadorCpf.

LOCATARIO: $locatario, $docLocatario.
$representante

IMOVEL: $imovel, situado em $endereco.

O presente contrato tem inicio em $dataInicio, com termino em $dataFim.

O valor mensal do aluguel e de R$ $valor, com vencimento todo dia $vencimento de cada mes.

O contrato sera reajustado conforme indice previsto em contrato.

As partes elegem o foro da comarca do imovel para dirimir quaisquer duvidas oriundas deste contrato.

E por estarem justas e contratadas, assinam o presente instrumento.

____________________________________
LOCADOR

____________________________________
LOCATARIO
");

$pdf->Output('I', 'contrato_locacao.pdf');

ob_end_flush();
