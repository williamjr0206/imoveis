<?php
$hostAtual = $_SERVER['HTTP_HOST'] ?? '';
define('BASE_URL', '/imoveis/');
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
