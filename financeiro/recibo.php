<?php
require __DIR__.'/../vendor/fpdf/fpdf.php';
require __DIR__.'/../config/db.php';

$id = $_GET['id'];

$sql = $pdo->prepare("
SELECT pg.*, i.descricao, p.nome AS proprietario, iq.nome AS inquilino
FROM pagamentos pg
JOIN contratos c ON pg.contrato_id = c.id
JOIN imoveis i ON c.imovel_id = i.id
JOIN proprietarios p ON i.proprietario_id = p.id
JOIN inquilinos iq ON c.inquilino_id = iq.id
WHERE pg.id = ?
");
$sql->execute([$id]);
$dados = $sql->fetch();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'RECIBO DE ALUGUEL',0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,8,"Proprietario: {$dados['proprietario']}",0,1);
$pdf->Cell(0,8,"Inquilino: {$dados['inquilino']}",0,1);
$pdf->Cell(0,8,"Imovel: {$dados['descricao']}",0,1);
$pdf->Cell(0,8,"Valor Total: R$ {$dados['valor_total']}",0,1);
$pdf->Cell(0,8,"Data Pagamento: {$dados['data_pagamento']}",0,1);

$pdf->Ln(10);
$pdf->Cell(0,8,"Documento emitido eletronicamente.",0,1);

$pdf->Output();
