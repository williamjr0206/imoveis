<?php
ob_start();
ini_set('display_errors', 0);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendedor/fpdf/fpdf.php';

mysqli_set_charset($conn, "utf8");

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT *
    FROM vw_contrato_base
    WHERE contrato_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();

if (!$d) {
    die('Contrato não encontrado');
}

/* ============================
   DOCUMENTO DO LOCATÁRIO
============================ */

if ($d['tipo_pessoa'] === 'PJ') {
    $docLocatario = "CNPJ: " . $d['cnpj'] .
                    "\nRepresentante: " . $d['representante_nome'] .
                    "\nCPF: " . $d['representante_cpf'];
} else {
    $docLocatario = "CPF: " . $d['cpf'];
}

/* ============================
   DATA DE EMISSÃO POR EXTENSO
============================ */

$meses = [
    1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',
    5=>'maio',6=>'junho',7=>'julho',8=>'agosto',
    9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'
];

$dataEmissao = date('d') . ' de ' . $meses[date('n')] . ' de ' . date('Y');

/* ============================
   PDF
============================ */

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',11);

/* ===============================
   CONTRATO MODELO 2
=============================== */

$texto = "
CONTRATO PARTICULAR DE LOCACAO DE IMOVEL

Pelo presente instrumento particular, de um lado:

LOCADOR: {$d['locador_nome']}, CPF {$d['locador_cpf']}

E de outro lado:

LOCATARIO: {$d['inquilino_nome']}
{$docLocatario}

Tem entre si justo e contratado o que segue:

CLAUSULA PRIMEIRA - DO IMOVEL
O imovel objeto deste contrato e {$d['imovel_descricao']}, situado em {$d['endereco']}.

CLAUSULA SEGUNDA - DO PRAZO
O prazo da locacao inicia-se em " . date('d/m/Y', strtotime($d['data_inicio'])) . "
e encerra-se em " . date('d/m/Y', strtotime($d['data_fim'])) . ".

CLAUSULA TERCEIRA - DO VALOR
O aluguel mensal e de R$ " . number_format($d['valor_aluguel'], 2, ',', '.') . ",
com vencimento todo dia {$d['dia_vencimento']}.

CLAUSULA QUARTA - DA GARANTIA
A garantia ajustada e {$d['tipo_garantia']}.

CLAUSULA QUINTA - DO FORO
Fica eleito o foro da comarca do imovel para dirimir quaisquer controversias.

E por estarem de acordo, assinam o presente instrumento.
";

/* Cidade + Data */
if (!empty($d['cidade'])) {
    $texto .= "\n\n{$d['cidade']}, {$dataEmissao}.";
} else {
    $texto .= "\n\n{$dataEmissao}.";
}

$pdf->MultiCell(0, 6, utf8_decode($texto));

/* ============================
   ASSINATURAS
============================ */

$pdf->Ln(15);
$pdf->Cell(0,5,"______________________________________________",0,1);
$pdf->Cell(0,5,utf8_decode($d['locador_nome']),0,1);

$pdf->Ln(10);
$pdf->Cell(0,5,"______________________________________________",0,1);
$pdf->Cell(0,5,utf8_decode($d['inquilino_nome']),0,1);

$pdf->Output('I', 'contrato_modelo2.pdf');
ob_end_flush();