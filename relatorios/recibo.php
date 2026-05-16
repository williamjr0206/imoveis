<?php
require __DIR__ . '/../vendedor/fpdf/fpdf.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

verificaPerfil(['ADMIN', 'OPERADOR']);

$id = $_GET['id'] ?? null;

if (!$id) {
    die('Pagamento não informado.');
}

$stmt = $conn->prepare("
    SELECT 
        pg.id,
        pg.mes_referencia,
        pg.valor_pago,
        pg.data_pagamento,
        pg.observacao,
        i.descricao AS imovel,
        p.nome AS proprietario,
        iq.nome AS inquilino
    FROM pagamentos pg
    JOIN contratos c      ON pg.contrato_id = c.id
    JOIN imoveis i        ON c.imovel_id = i.id
    JOIN proprietarios p  ON i.proprietario_id = p.id
    JOIN inquilinos iq    ON c.inquilino_id = iq.id
    WHERE pg.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    die('Pagamento não encontrado.');
}

function texto_pdf($texto) {
    return utf8_decode($texto ?? '');
}

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, texto_pdf('RECIBO DE ALUGUEL'), 0, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(0, 8, texto_pdf('Recibo Nº: ' . $dados['id']), 0, 1);
$pdf->Cell(0, 8, texto_pdf('Proprietário: ' . $dados['proprietario']), 0, 1);
$pdf->Cell(0, 8, texto_pdf('Inquilino: ' . $dados['inquilino']), 0, 1);
$pdf->Cell(0, 8, texto_pdf('Imóvel: ' . $dados['imovel']), 0, 1);
$pdf->Cell(0, 8, texto_pdf('Mês de referência: ' . date('m/Y', strtotime($dados['mes_referencia']))), 0, 1);
$pdf->Cell(0, 8, texto_pdf('Valor pago: R$ ' . number_format($dados['valor_pago'], 2, ',', '.')), 0, 1);
$pdf->Cell(0, 8, texto_pdf('Data do pagamento: ' . date('d/m/Y', strtotime($dados['data_pagamento']))), 0, 1);

if (!empty($dados['observacao'])) {
    $pdf->Ln(3);
    $pdf->MultiCell(0, 8, texto_pdf('Observação: ' . $dados['observacao']));
}

$pdf->Ln(15);

$pdf->MultiCell(0, 8, texto_pdf(
    'Recebi do inquilino acima identificado o valor referente ao pagamento de aluguel do imóvel descrito neste recibo.'
));

$pdf->Ln(20);

$pdf->Cell(0, 8, texto_pdf('________________________________________'), 0, 1, 'C');
$pdf->Cell(0, 8, texto_pdf($dados['proprietario']), 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 8, texto_pdf('Documento emitido eletronicamente pelo sistema de imóveis.'), 0, 1, 'C');

$pdf->Output('I', 'recibo_aluguel_' . $dados['id'] . '.pdf');
exit;