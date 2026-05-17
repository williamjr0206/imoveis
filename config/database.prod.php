
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

return [

        'host' => 'localhost',
        'db'  => 'szjw_fazevedo',
        'user' => 'szjw_wia',
        'pass' => 'Wia685618&zenilda'
];

if ($conn->connect_error) {
    die("Erro de conexão com o banco: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

