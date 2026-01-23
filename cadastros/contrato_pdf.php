<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../vendedor/fpdf/fpdf.php';

verificaPerfil(['ADMIN','OPERADOR']);

$id = $_GET['id'] ?? null;
if (!$id) {
    die('Contrato inválido.');
}

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.data_inicio,
        c.data_fim,
        c.dia_vencimento,

        iq.nome AS inquilino,
        iq.cpf  AS inquilino_cpf,

        p.nome AS proprietario,

        i.descricao AS imovel,
        i.endereco,
        i.valor_aluguel

    FROM contratos c
    JOIN inquilinos iq   ON iq.id = c.inquilino_id
    JOIN imoveis i       ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    die('Contrato não encontrado.');
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

/* ===== TÍTULO ===== */
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'CONTRATO DE LOCACAO DE IMOVEL',0,1,'C');
$pdf->Ln(5);

/* ===== TEXTO ===== */
$pdf->SetFont('Arial','',11);

$texto = "
Pelo presente instrumento particular, de um lado:

LOCADOR: {$dados['proprietario']}

e de outro:

LOCATARIO: {$dados['inquilino']} (CPF: {$dados['inquilino_cpf']})

têm entre si justo e contratado o que segue:

CLÁUSULA 1ª – DO IMÓVEL
O imóvel objeto deste contrato é: {$dados['imovel']}, localizado em {$dados['endereco']}.

CLÁUSULA 2ª – DO PRAZO
O prazo da locação tem início em ".date('d/m/Y', strtotime($dados['data_inicio']))."
".($dados['data_fim'] ? "e término em ".date('d/m/Y', strtotime($dados['data_fim'])) : "por prazo indeterminado").".

CLÁUSULA 3ª – DO VALOR
O valor mensal do aluguel é de R$ ".number_format($dados['valor_aluguel'], 2, ',', '.').",
com vencimento todo dia {$dados['dia_vencimento']} de cada mês.

CLÁUSULA 4ª – DAS DISPOSIÇÕES GERAIS
O locatário compromete-se a conservar o imóvel, arcando com danos que causar.

E por estarem assim justos e contratados, firmam o presente instrumento.
";

$pdf->MultiCell(0,7,utf8_decode($texto));

$pdf->Ln(20);
$pdf->Cell(0,8,'_________________________________________',0,1,'C');
$pdf->Cell(0,8,'Assinatura do Locador',0,1,'C');

$pdf->Ln(15);
$pdf->Cell(0,8,'_________________________________________',0,1,'C');
$pdf->Cell(0,8,'Assinatura do Locatario',0,1,'C');

$pdf->Output("I", "Contrato_{$id}.pdf");
