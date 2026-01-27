<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendedor/fpdf/fpdf.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die('Contrato inválido');
}

/* ==========================
   BUSCA BASE DO CONTRATO
========================== */
$stmt = $conn->prepare("
    SELECT *
    FROM vw_contrato_base
    WHERE contrato_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    die('Contrato não encontrado');
}

/* ==========================
   TRATAMENTO PF / PJ
========================== */
$locatario = $dados['inquilino_nome'];

if ($dados['tipo_pessoa'] === 'PJ') {
    $locatario .= "\nCNPJ: {$dados['cnpj']}";
    $locatario .= "\nRepresentante Legal: {$dados['representante_nome']}";
    $locatario .= "\nCPF do Representante: {$dados['representante_cpf']}";
} else {
    $locatario .= "\nCPF: {$dados['cpf']}";
}

/* ==========================
   PDF
========================== */
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

/* Título */
$pdf->SetFont('Arial', 'B', 14);
$pdf->MultiCell(0, 8, 'CONTRATO DE LOCAÇÃO DE IMÓVEL URBANO');
$pdf->Ln(4);

/* Corpo */
$pdf->SetFont('Arial', '', 11);

$pdf->MultiCell(0, 7,
"LOCADOR: {$dados['locador_nome']}, CPF {$dados['locador_cpf']}.

LOCATÁRIO:
{$locatario}

IMÓVEL:
{$dados['imovel_descricao']}
Endereço: {$dados['endereco']} – {$dados['cidade']}/{$dados['estado']}.

VALOR DO ALUGUEL:
R$ " . number_format($dados['valor_aluguel'], 2, ',', '.') . "

DATA DE INÍCIO:
" . date('d/m/Y', strtotime($dados['data_inicio'])) . "

DATA DE TÉRMINO:
" . ($dados['data_fim'] ? date('d/m/Y', strtotime($dados['data_fim'])) : 'Prazo indeterminado') . "

DIA DE VENCIMENTO:
Todo dia {$dados['dia_vencimento']} de cada mês.
");

/* Cláusulas */
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 11);
$pdf->MultiCell(0, 7, 'CLÁUSULAS CONTRATUAIS');
$pdf->SetFont('Arial', '', 11);

$pdf->MultiCell(0, 7,
"1. O imóvel destina-se exclusivamente à finalidade declarada no contrato.

2. O LOCATÁRIO obriga-se a manter o imóvel em perfeito estado de conservação.

3. O pagamento do aluguel será reajustado conforme índice {$dados['indice_reajuste']}.

4. O inadimplemento sujeitará o LOCATÁRIO às penalidades legais.

5. Este contrato obriga as partes e seus sucessores, a qualquer título."
);

/* Assinaturas */
$pdf->Ln(10);
$pdf->MultiCell(0, 7,
"Local e Data: ________________________________

LOCADOR: ________________________________

LOCATÁRIO: ________________________________"
);

/* Saída */
$pdf->Output('I', 'contrato_locacao_juridico.pdf');
exit;
