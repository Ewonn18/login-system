<?php
require_once "session.php";
require_once "csrf.php";

function redirectToSignup(string $type, string $message): void
{
    header("Location: index.php?panel=signup&type=" . urlencode($type) . "&message=" . urlencode($message));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $conn->close();
    header("Location: index.php");
    exit();
}

$csrfToken = $_POST["csrf_token"] ?? "";

if (!is_valid_csrf_token($csrfToken)) {
    $conn->close();
    redirectToSignup("error", "Invalid request. Please try again.");
}

unset($_SESSION["csrf_token"]);

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

if ($name === "" || $email === "" || $password === "" || $confirmPassword === "") {
    $conn->close();
    redirectToSignup("error", "All fields are required.");
}

if (mb_strlen($name) > 100) {
    $conn->close();
    redirectToSignup("error", "Full name is too long.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $conn->close();
    redirectToSignup("error", "Please enter a valid email address.");
}

if ($password !== $confirmPassword) {
    $conn->close();
    redirectToSignup("error", "Passwords do not match.");
}

if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[^A-Za-z0-9]/', $password)
) {
    $conn->close();
    redirectToSignup("error", "Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.");
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$checkStmt) {
    $conn->close();
    redirectToSignup("error", "Something went wrong. Please try again.");
}

$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    $conn->close();
    redirectToSignup("error", "An account with that email already exists.");
}
$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
if (!$stmt) {
    $conn->close();
    redirectToSignup("error", "Something went wrong. Please try again.");
}

$stmt->bind_param("sss", $name, $email, $hashedPassword);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    redirectToSignup("error", "Registration failed. Please try again.");
}

$userId = (int)$stmt->insert_id;
$stmt->close();

session_regenerate_id(true);
$_SESSION["user_id"] = $userId;
$_SESSION["user_name"] = $name;
$_SESSION["user_email"] = $email;
$_SESSION["user_role"] = "user";
$_SESSION["remember_me"] = false;

$conn->close();
header("Location: dashboard.php");
exit();