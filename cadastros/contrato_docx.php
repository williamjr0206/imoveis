<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (ob_get_level()) {
    ob_end_clean();
}

require __DIR__ . '/../config/database.php';

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die('ERRO: vendor/autoload.php não encontrado em: ' . $autoload);
}

require $autoload;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

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

function texto($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function dataBR($data) {
    if (empty($data)) {
        return '';
    }
    return date('d/m/Y', strtotime($data));
}

if (($d['tipo_pessoa'] ?? '') === 'PJ') {
    $docLocatario = [
        'CNPJ: ' . ($d['cnpj'] ?? ''),
        'Representante: ' . ($d['representante_nome'] ?? ''),
        'CPF: ' . ($d['representante_cpf'] ?? '')
    ];
} else {
    $docLocatario = [
        'CPF: ' . ($d['cpf'] ?? '')
    ];
}

$multa_percentual = $d['multa_percentual'] ?? '10.00';
$juros_percentual = $d['juros_percentual'] ?? '1.00';

$phpWord = new PhpWord();
$phpWord->setDefaultFontName('Arial');
$phpWord->setDefaultFontSize(11);

$section = $phpWord->addSection([
    'marginTop'    => 1200,
    'marginBottom' => 1200,
    'marginLeft'   => 1200,
    'marginRight'  => 1200
]);

$titulo = ['bold' => true, 'size' => 14];
$negrito = ['bold' => true];

$section->addText('CONTRATO DE LOCAÇÃO NÃO RESIDENCIAL', $titulo, ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);

$section->addText('I – QUADRO RESUMO', $negrito);
$section->addTextBreak(1);

$section->addText('1. PARTES CONTRATANTES', $negrito);
$section->addTextBreak(1);

$section->addText('1.1 LOCADORES:', $negrito);
$section->addText('JOSE KAISER AZEVEDO, CPF 919.970.118-91');
$section->addText('PAULO ROBERTO AZEVEDO, CPF 006.264.438-65');

$section->addTextBreak(1);

$section->addText('1.2 LOCATÁRIO:', $negrito);
$section->addText(texto($d['inquilino_nome'] ?? ''));

foreach ($docLocatario as $linha) {
    $section->addText(texto($linha));
}

$section->addTextBreak(1);

$section->addText('Endereço:', $negrito);
$section->addText(texto($d['inquilino_endereco'] ?? ''));

$section->addTextBreak(1);

$section->addText('2. OBJETO DA LOCAÇÃO:', $negrito);
$section->addText(texto($d['imovel_descricao'] ?? ''));
$section->addText(texto($d['endereco'] ?? ''));

$section->addTextBreak(1);

$section->addText('3. PRAZO:', $negrito);
$section->addText('Início: ' . dataBR($d['data_inicio'] ?? ''));
$section->addText('Término: ' . dataBR($d['data_fim'] ?? ''));

$section->addTextBreak(1);

$section->addText('4. ALUGUEL:', $negrito);
$section->addText('R$ ' . number_format((float)($d['valor_aluguel'] ?? 0), 2, ',', '.'));
$section->addText('Dia de vencimento: ' . texto($d['dia_vencimento'] ?? ''));

$section->addTextBreak(1);

$section->addText('5. GARANTIA:', $negrito);
$section->addText(texto($d['tipo_garantia'] ?? ''));

$section->addTextBreak(2);

$section->addText('II – CLÁUSULAS E CONDIÇÕES', $negrito);
$section->addTextBreak(1);

$section->addText('Cláusula Primeira – Do Objeto', $negrito);
$section->addText('O LOCADOR dá em locação ao LOCATÁRIO o imóvel descrito neste contrato, obrigando-se o LOCATÁRIO a devolvê-lo nas mesmas condições em que recebeu.');

$section->addTextBreak(1);

$section->addText('Cláusula Segunda – Do Valor e Reajuste', $negrito);
$section->addText('O aluguel será pago até o dia ' . texto($d['dia_vencimento'] ?? '') . ' de cada mês, sendo reajustado anualmente conforme índice contratual.');

$section->addText(
    'Em caso de atraso incidirá multa de ' .
    number_format((float)$multa_percentual, 2, ',', '.') .
    '%, juros de ' .
    number_format((float)$juros_percentual, 2, ',', '.') .
    '% ao mês e honorários advocatícios de 20%.'
);

$section->addTextBreak(1);

$section->addText('Cláusula Terceira – Encargos', $negrito);
$section->addText('São de responsabilidade do LOCATÁRIO todos os encargos como IPTU, condomínio, água, luz e demais despesas.');

$section->addTextBreak(1);

$section->addText('Cláusula Quarta – Conservação', $negrito);
$section->addText('O LOCATÁRIO declara ter recebido o imóvel em perfeito estado, obrigando-se a devolvê-lo nas mesmas condições.');

$section->addTextBreak(1);

$section->addText('Cláusula Quinta – Multa', $negrito);
$section->addText('Fica estipulada multa equivalente a 3 (três) aluguéis vigentes em caso de descumprimento contratual.');

$section->addTextBreak(1);

$section->addText('Cláusula Sexta – Foro', $negrito);
$section->addText('Fica eleito o foro da comarca do imóvel para dirimir quaisquer controvérsias.');

$section->addTextBreak(3);

$section->addText('______________________________________________', [], ['alignment' => Jc::CENTER]);
$section->addText('JOSE KAISER AZEVEDO', [], ['alignment' => Jc::CENTER]);

$section->addTextBreak(2);

$section->addText('______________________________________________', [], ['alignment' => Jc::CENTER]);
$section->addText('PAULO ROBERTO AZEVEDO', [], ['alignment' => Jc::CENTER]);

$section->addTextBreak(2);

$section->addText('______________________________________________', [], ['alignment' => Jc::CENTER]);
$section->addText(texto($d['inquilino_nome'] ?? ''), [], ['alignment' => Jc::CENTER]);

$tempFile = tempnam(sys_get_temp_dir(), 'contrato_');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($tempFile);

$nomeArquivo = 'contrato_' . $id . '.docx';

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Content-Length: ' . filesize($tempFile));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($tempFile);
unlink($tempFile);
exit;