<?php
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('<div style="padding:20px;color:red;">Error de conexión: ' . $conn->connect_error . '</div>');
}
