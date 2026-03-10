<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirmPassword = trim($_POST["confirm_password"] ?? "");

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("All fields are required."));
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Invalid email format."));
        exit();
    }

    if ($password !== $confirmPassword) {
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
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Password must be at least 8 characters and include uppercase, lowercase, number, and symbol."));
        exit();
    }

    $checkSql = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkSql);

    if (!$checkStmt) {
        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Something went wrong."));
        exit();
    }

    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        $conn->close();

        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Email already exists."));
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $checkStmt->close();
        $conn->close();

        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Something went wrong."));
        exit();
    }

    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {
        $stmt->close();
        $checkStmt->close();
        $conn->close();

        header("Location: index.php?panel=signin&type=success&message=" . urlencode("Registration successful. You can now sign in."));
        exit();
    } else {
        $stmt->close();
        $checkStmt->close();
        $conn->close();

        header("Location: index.php?panel=signup&type=error&message=" . urlencode("Registration failed."));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>