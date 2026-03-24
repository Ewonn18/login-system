<?php
require_once "session.php";
require_once "csrf.php";

$configPath = __DIR__ . "/config.php";
$exampleConfigPath = __DIR__ . "/config.example.php";
$appConfig = file_exists($configPath) ? require $configPath : require $exampleConfigPath;
$rememberCookieName = $appConfig["remember_cookie_name"] ?? "techtrail_remember";

function redirectToSignin(string $type, string $message): void
{
    header("Location: index.php?panel=signin&type=" . urlencode($type) . "&message=" . urlencode($message));
    exit();
}

function getClientIp(): string
{
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $parts = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
        return trim($parts[0]);
    }

    return $_SERVER["REMOTE_ADDR"] ?? "unknown";
}

function recordLoginAttempt(mysqli $conn, string $identifier): void
{
    $stmt = $conn->prepare("INSERT INTO login_attempts (identifier) VALUES (?)");
    if ($stmt) {
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $stmt->close();
    }
}

function clearLoginAttempts(mysqli $conn, string $identifier): void
{
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ?");
    if ($stmt) {
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $stmt->close();
    }
}

function tooManyRecentAttempts(mysqli $conn, string $identifier, int $maxAttempts = 5, int $windowMinutes = 15): bool
{
    $sql = "SELECT COUNT(*) AS total
            FROM login_attempts
            WHERE identifier = ?
              AND attempt_time >= (NOW() - INTERVAL ? MINUTE)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("si", $identifier, $windowMinutes);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : ["total" => 0];
    $stmt->close();

    return (int)($row["total"] ?? 0) >= $maxAttempts;
}

function cleanupOldLoginAttempts(mysqli $conn): void
{
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL 1 DAY)");
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $conn->close();
    header("Location: index.php");
    exit();
}

$csrfToken = $_POST["csrf_token"] ?? "";

if (!is_valid_csrf_token($csrfToken)) {
    $conn->close();
    redirectToSignin("error", "Invalid request. Please try again.");
}

unset($_SESSION["csrf_token"]);

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$remember = !empty($_POST["remember"]);

if ($email === "" || $password === "") {
    $conn->close();
    redirectToSignin("error", "Email and password are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $conn->close();
    redirectToSignin("error", "Please enter a valid email address.");
}

cleanupOldLoginAttempts($conn);

$clientIp = getClientIp();
$emailKey = "email:" . strtolower($email);
$ipKey = "ip:" . $clientIp;

if (tooManyRecentAttempts($conn, $emailKey) || tooManyRecentAttempts($conn, $ipKey)) {
    $conn->close();
    redirectToSignin("error", "Too many failed login attempts. Please wait 15 minutes and try again.");
}

$sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $conn->close();
    redirectToSignin("error", "Something went wrong. Please try again.");
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$loginSuccessful = false;
$user = null;

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user["password"])) {
        $loginSuccessful = true;
    }
}

$stmt->close();

if (!$loginSuccessful || $user === null) {
    recordLoginAttempt($conn, $emailKey);
    recordLoginAttempt($conn, $ipKey);
    $conn->close();
    redirectToSignin("error", "Invalid email or password.");
}

clearLoginAttempts($conn, $emailKey);
clearLoginAttempts($conn, $ipKey);

session_regenerate_id(true);

$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["user_role"] = $user["role"];
$_SESSION["remember_me"] = $remember;

if ($remember) {
    $selector = bin2hex(random_bytes(8));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $validator);
    $expiresAt = date("Y-m-d H:i:s", time() + 60 * 60 * 24 * 30);

    $deleteOld = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($deleteOld) {
        $deleteOld->bind_param("i", $user["id"]);
        $deleteOld->execute();
        $deleteOld->close();
    }

    $insert = $conn->prepare("INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
    if ($insert) {
        $insert->bind_param("isss", $user["id"], $selector, $tokenHash, $expiresAt);
        $insert->execute();
        $insert->close();

        setcookie(
            $rememberCookieName,
            $selector . ":" . $validator,
            [
                "expires" => time() + 60 * 60 * 24 * 30,
                "path" => "/",
                "httponly" => true,
                "samesite" => "Lax",
                "secure" => (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"),
            ]
        );
    }
}

$conn->close();
header("Location: dashboard.php");
exit();