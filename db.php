<?php
$host = "127.0.0.1";
$user = "root";
$pass = "Saix1818!!!";
$dbname = "login_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>