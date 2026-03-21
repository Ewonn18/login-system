<?php
require_once "session.php";
require_once "csrf.php";

$configPath = __DIR__ . "/config.php";
$exampleConfigPath = __DIR__ . "/config.example.php";
$appConfig = file_exists($configPath) ? require $configPath : require $exampleConfigPath;
$rememberCookieName = $appConfig["remember_cookie_name"] ?? "techtrail_remember";

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

    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
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

    if ($loginSuccessful && $user !== null) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["user_role"] = $user["role"];
        $_SESSION["remember_me"] = !empty($_POST["remember"]);

        if (!empty($_POST["remember"])) {
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
                    ]
                );
            }
        }

        $conn->close();
        header("Location: dashboard.php");
        exit();
    }

    $conn->close();
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Invalid email or password."));
    exit();
}

header("Location: index.php");
exit();
?>