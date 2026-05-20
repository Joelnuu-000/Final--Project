<?php
session_start();
$host = 'localhost';
$db   = 'pos_system';
$user = 'root';
$pass = ''; // 請依你的環境修改

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, $options);
?>