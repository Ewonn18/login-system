<?php
$host = "127.0.0.1";
$user = "root";
$pass = "Saix1818!!!";
$dbname = "login_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>