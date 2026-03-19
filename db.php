<?php
$configPath = __DIR__ . "/config.php";
$exampleConfigPath = __DIR__ . "/config.example.php";

$config = file_exists($configPath)
    ? require $configPath
    : require $exampleConfigPath;

$host = $config["db_host"] ?? "127.0.0.1";
$user = $config["db_user"] ?? "root";
$pass = $config["db_pass"] ?? "";
$dbname = $config["db_name"] ?? "login_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");