<?php
require_once "session.php";

$selector = trim($_GET["selector"] ?? "");
$validator = trim($_GET["validator"] ?? "");

if ($selector === "" || $validator === "") {
    $conn->close();
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid verification link."));
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, user_id, token_hash, expires_at, used_at
     FROM email_verifications
     WHERE selector = ?
     LIMIT 1"
);

if (!$stmt) {
    $conn->close();
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Something went wrong."));
    exit();
}

$stmt->bind_param("s", $selector);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid or expired verification link."));
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

$isExpired = strtotime($row["expires_at"]) < time();
$isUsed = !empty($row["used_at"]);
$isValidToken = hash_equals($row["token_hash"], hash("sha256", $validator));

if ($isExpired || $isUsed || !$isValidToken) {
    $conn->close();
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid or expired verification link."));
    exit();
}

$updateUser = $conn->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?");
if ($updateUser) {
    $userId = (int)$row["user_id"];
    $updateUser->bind_param("i", $userId);
    $updateUser->execute();
    $updateUser->close();
}

$markUsed = $conn->prepare("UPDATE email_verifications SET used_at = NOW() WHERE id = ?");
if ($markUsed) {
    $verificationId = (int)$row["id"];
    $markUsed->bind_param("i", $verificationId);
    $markUsed->execute();
    $markUsed->close();
}

$conn->close();

header("Location: index.php?panel=signin&type=success&message=" . urlencode("Email verified successfully. You can now sign in."));
exit();