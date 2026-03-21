<?php
require_once "session.php";

$configPath = __DIR__ . "/config.php";
$exampleConfigPath = __DIR__ . "/config.example.php";
$appConfig = file_exists($configPath) ? require $configPath : require $exampleConfigPath;
$rememberCookieName = $appConfig["remember_cookie_name"] ?? "techtrail_remember";

if (isset($_SESSION["user_id"])) {
    $userId = (int)$_SESSION["user_id"];

    $deleteStmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($deleteStmt) {
        $deleteStmt->bind_param("i", $userId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
}

setcookie(
    $rememberCookieName,
    "",
    [
        "expires" => time() - 3600,
        "path" => "/",
        "httponly" => true,
        "samesite" => "Lax",
    ]
);

session_unset();
session_destroy();

$conn->close();

header("Location: index.php?panel=signin&type=success&message=" . urlencode("You have been signed out successfully."));
exit();