<?php
session_start();
require_once "csrf.php";
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!is_valid_csrf_token($csrfToken)) {
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid request. Please try again."));
        exit();
    }

    unset($_SESSION["csrf_token"]);

    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (empty($email) || empty($password)) {
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Email and password are required."));
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Please enter a valid email address."));
        exit();
    }

    $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $conn->close();
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Something went wrong. Please try again."));
        exit();
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
    $conn->close();

    if ($loginSuccessful && $user !== null) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["remember_me"] = !empty($_POST["remember"]);

        header("Location: dashboard.php");
        exit();
    }

    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid email or password."));
    exit();
}

header("Location: index.php");
exit();
?>