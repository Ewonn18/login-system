<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    $sessionToken = $_SESSION["csrf_token"] ?? "";

    if (empty($csrfToken) || empty($sessionToken) || !hash_equals($sessionToken, $csrfToken)) {
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
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid email format."));
        exit();
    }

    $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Something went wrong."));
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["remember_me"] = !empty($_POST["remember"]);

            $stmt->close();
            $conn->close();

            header("Location: dashboard.php");
            exit();
        } else {
            $stmt->close();
            $conn->close();

            header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid password."));
            exit();
        }
    } else {
        $stmt->close();
        $conn->close();

        header("Location: index.php?panel=signin&type=error&message=" . urlencode("No account found with that email."));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>