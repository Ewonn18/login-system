<?php
require_once "session.php";
require_once "csrf.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!is_valid_csrf_token($csrfToken)) {
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Invalid request. Please try again."));
        exit();
    }

    unset($_SESSION["csrf_token"]);

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirmPassword = trim($_POST["confirm_password"] ?? "");

    if ($name === "" || $email === "" || $password === "" || $confirmPassword === "") {
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("All fields are required."));
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Please enter a valid email address."));
        exit();
    }

    if ($password !== $confirmPassword) {
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Passwords do not match."));
        exit();
    }

    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Password must be at least 8 characters and include uppercase, lowercase, number, and symbol."));
        exit();
    }

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$checkStmt) {
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Something went wrong. Please try again."));
        exit();
    }

    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("An account with that email already exists."));
        exit();
    }
    $checkStmt->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    if (!$stmt) {
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Something went wrong. Please try again."));
        exit();
    }

    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Registration failed. Please try again."));
        exit();
    }

    $userId = $stmt->insert_id;
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
}

$conn->close();
header("Location: index.php");
exit();