<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Email and password are required."));
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
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];

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
}
?>