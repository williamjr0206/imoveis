<?php
ob_start();
ini_set('display_errors', 0);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendedor/fpdf/fpdf.php';

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
   PDF
============================ */

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',12);
        $this->Cell(0,8,utf8_decode('CONTRATO DE LOCAÇÃO NÃO RESIDENCIAL'),0,1,'C');
        $this->Ln(5);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

/* ============================
   I – QUADRO RESUMO
============================ */

$quadro = "
I – QUADRO RESUMO

1. PARTES CONTRATANTES

1.1 LOCADORES:

JOSE KAISER AZEVEDO, CPF 919.970.118-91
PAULO ROBERTO AZEVEDO, CPF 006.264.438-65

1.2 LOCATÁRIO:

{$d['inquilino_nome']}
{$docLocatario}

Endereço:
{$d['inquilino_endereco']}

2. OBJETO DA LOCAÇÃO:

{$d['imovel_descricao']}
{$d['endereco']}

3. PRAZO:

Início: " . date('d/m/Y', strtotime($d['data_inicio'])) . "
Término: " . date('d/m/Y', strtotime($d['data_fim'])) . "

4. ALUGUEL:

R$ " . number_format($d['valor_aluguel'], 2, ',', '.') . "

Dia de vencimento: {$d['dia_vencimento']}

5. GARANTIA:

{$d['tipo_garantia']}
";

$pdf->MultiCell(0,5,utf8_decode($quadro));
$pdf->Ln(5);

/* ============================
   II – CLÁUSULAS
============================ */

$clausulas = "
II – CLÁUSULAS E CONDIÇÕES

Cláusula Primeira – Do Objeto
O LOCADOR dá em locação ao LOCATÁRIO o imóvel descrito neste contrato,
obrigando-se o LOCATÁRIO a devolvê-lo nas mesmas condições em que recebeu.

Cláusula Segunda – Do Valor e Reajuste
O aluguel será pago até o dia {$d['dia_vencimento']} de cada mês,
sendo reajustado anualmente conforme índice contratual.

Em caso de atraso incidirá multa de 10%, juros de 1% ao mês
e honorários advocatícios de 20%.

Cláusula Terceira – Encargos
São de responsabilidade do LOCATÁRIO todos os encargos
como IPTU, condomínio, água, luz e demais despesas.

Cláusula Quarta – Conservação
O LOCATÁRIO declara ter recebido o imóvel em perfeito estado,
obrigando-se a devolvê-lo nas mesmas condições.

Cláusula Quinta – Multa
Fica estipulada multa equivalente a 3 (três) aluguéis vigentes
em caso de descumprimento contratual.

Cláusula Sexta – Foro
Fica eleito o foro da comarca do imóvel para dirimir quaisquer controvérsias.
";

$pdf->MultiCell(0,5,utf8_decode($clausulas));

$pdf->Ln(15);

$pdf->Cell(0,5,"______________________________________________",0,1);
$pdf->Cell(0,5,"JOSE KAISER AZEVEDO",0,1);

$pdf->Ln(10);

$pdf->Cell(0,5,"______________________________________________",0,1);
$pdf->Cell(0,5,utf8_decode($d['inquilino_nome']),0,1);

$pdf->Output('I', 'contrato_modelo3.pdf');
ob_end_flush();