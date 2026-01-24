<?php
ob_clean();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../vendedor/fpdf/fpdf.php';

verificaPerfil(['ADMIN','OPERADOR']);

function pdfText($txt) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $txt);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die('Contrato não informado');
}

/* =========================
   QUERY BASE DO CONTRATO
========================= */
$stmt = $conn->prepare("
    SELECT
        c.data_inicio,
        c.data_fim,
        c.dia_vencimento,

        i.descricao     AS imovel_descricao,
        i.endereco      AS imovel_endereco,
        i.valor_aluguel,

        p.nome          AS proprietario_nome,
        p.cpf           AS proprietario_cpf,

        iq.nome         AS inquilino_nome,
        iq.tipo         AS inquilino_tipo,
        iq.documento    AS inquilino_documento

    FROM contratos c
    JOIN imoveis i       ON i.id = c.imovel_id
    JOIN proprietarios p ON p.id = i.proprietario_id
    JOIN inquilinos iq   ON iq.id = c.inquilino_id
    WHERE c.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    die('Contrato não encontrado');
}

/* =========================
   FORMATAÇÕES
========================= */
$dataContrato = date('d/m/Y');
$dataInicio   = date('d/m/Y', strtotime($dados['data_inicio']));
$dataFim      = $dados['data_fim']
    ? date('d/m/Y', strtotime($dados['data_fim']))
    : 'prazo indeterminado';

$valorAluguel = number_format($dados['valor_aluguel'], 2, ',', '.');

/* =========================
   LOCATÁRIO
========================= */
if ($dados['inquilino_tipo'] === 'PJ') {
    $locatario = "{$dados['inquilino_nome']}, pessoa jurídica, inscrita no CNPJ {$dados['inquilino_documento']}";
} else {
    $locatario = "{$dados['inquilino_nome']}, inscrito no CPF {$dados['inquilino_documento']}";
}

/* =========================
   TEXTO JURÍDICO
========================= */
$textoContrato = "
CONTRATO DE LOCAÇÃO DE IMÓVEL RESIDENCIAL

Pelo presente instrumento particular, de um lado:

LOCADOR:
{$dados['proprietario_nome']}, inscrito no CPF {$dados['proprietario_cpf']}.

E de outro lado:

LOCATÁRIO:
{$locatario}.

Têm entre si justo e contratado o que segue:

CLÁUSULA PRIMEIRA – DO OBJETO
O presente contrato tem por objeto a locação do imóvel localizado em:
{$dados['imovel_descricao']}, situado à {$dados['imovel_endereco']}.

CLÁUSULA SEGUNDA – DO PRAZO
A locação inicia-se em {$dataInicio}, com término em {$dataFim}.

CLÁUSULA TERCEIRA – DO VALOR DO ALUGUEL
O aluguel mensal é fixado em R$ {$valorAluguel}, devendo ser pago até o dia {$dados['dia_vencimento']} de cada mês.

CLÁUSULA QUARTA – DAS OBRIGAÇÕES DO LOCATÁRIO
São obrigações do LOCATÁRIO:
I – Pagar pontualmente o aluguel e encargos;
II – Manter o imóvel em perfeito estado;
III – Restituir o imóvel ao final da locação nas condições recebidas.

CLÁUSULA QUINTA – DISPOSIÇÕES GERAIS
Este contrato rege-se pela Lei nº 8.245/91 (Lei do Inquilinato).
Fica eleito o foro da comarca do imóvel para dirimir quaisquer controvérsias.

E, por estarem justos e contratados, assinam o presente instrumento.

Data do contrato: {$dataContrato}.
";

/* =========================
   PDF
========================= */
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0, 7, pdfText($textoContrato));

$pdf->Ln(15);
$pdf->Cell(0, 7, pdfText("____________________________________________"), 0, 1);
$pdf->Cell(0, 7, pdfText("LOCADOR"), 0, 1);

$pdf->Ln(10);
$pdf->Cell(0, 7, pdfText("____________________________________________"), 0, 1);
$pdf->Cell(0, 7, pdfText("LOCATÁRIO"), 0, 1);

$pdf->Output('I', 'contrato_locacao.pdf');
exit;
