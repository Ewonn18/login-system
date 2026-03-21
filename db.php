<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$configPath = __DIR__ . "/config.php";
$exampleConfigPath = __DIR__ . "/config.example.php";

$config = file_exists($configPath)
    ? require $configPath
    : require $exampleConfigPath;

$host = $config["db_host"] ?? "127.0.0.1";
$user = $config["db_user"] ?? "root";
$pass = $config["db_pass"] ?? "";
$dbname = $config["db_name"] ?? "login_system";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}