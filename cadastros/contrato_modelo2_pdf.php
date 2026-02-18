<?php
ob_start();
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

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',11);

/* ===============================
   CONTRATO MODELO 2 (MAIS FORTE)
=============================== */

$pdf->MultiCell(0, 6,
"CONTRATO PARTICULAR DE LOCACAO DE IMOVEL

Pelo presente instrumento particular, de um lado:

LOCADOR: {$d['locador_nome']}, CPF {$d['locador_cpf']}

E de outro lado:

LOCATARIO: {$d['inquilino_nome']}

Tem entre si justo e contratado o que segue:

CLÁUSULA PRIMEIRA – DO IMÓVEL
O imóvel objeto deste contrato é {$d['imovel_descricao']}, situado em {$d['endereco']}.

CLÁUSULA SEGUNDA – DO PRAZO
O prazo da locação inicia-se em " . date('d/m/Y', strtotime($d['data_inicio'])) . "
e encerra-se em " . date('d/m/Y', strtotime($d['data_fim'])) . ".

CLÁUSULA TERCEIRA – DO VALOR
O aluguel mensal é de R$ " . number_format($d['valor_aluguel'], 2, ',', '.') . ",
com vencimento todo dia {$d['dia_vencimento']}.

CLÁUSULA QUARTA – DA GARANTIA
A garantia ajustada é {$d['tipo_garantia']}.

CLÁUSULA QUINTA – DO FORO
Fica eleito o foro da comarca do imóvel para dirimir quaisquer controvérsias.

E por estarem de acordo, assinam o presente instrumento.
");

$pdf->Output('I', 'contrato_modelo2.pdf');
ob_end_flush();
