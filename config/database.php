<?php
$hostAtual = $_SERVER['HTTP_HOST'] ?? '';

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$pastaProjeto = '/imoveis/'; // só mude isso se mudar o nome da pasta

define('BASE_URL', $protocolo . $host . $pastaProjeto);
$ambiente = (
    $hostAtual === 'localhost' ||
    $hostAtual === 'localhost'
) ? 'local' : 'prod';

$config = require __DIR__ . "/database.$ambiente.php";

$conn = new mysqli(
    $config['host'],
    $config['user'],
    $config['pass'],
    $config['db']
);

if ($conn->connect_error) {
    die("Erro de conexão com o banco");
}
