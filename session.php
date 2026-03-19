<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

$configPath = __DIR__ . "/config.php";
$exampleConfigPath = __DIR__ . "/config.example.php";
$appConfig = file_exists($configPath) ? require $configPath : require $exampleConfigPath;

$rememberCookieName = $appConfig["remember_cookie_name"] ?? "techtrail_remember";

function restore_remembered_login(mysqli $conn, string $cookieName): void
{
    if (isset($_SESSION["user_id"])) {
        return;
    }

    if (empty($_COOKIE[$cookieName])) {
        return;
    }

    $parts = explode(":", $_COOKIE[$cookieName]);
    if (count($parts) !== 2) {
        setcookie($cookieName, "", time() - 3600, "/");
        return;
    }

    [$selector, $validator] = $parts;

    $sql = "SELECT rt.user_id, rt.token_hash, rt.expires_at, u.name, u.email, u.role
            FROM remember_tokens rt
            INNER JOIN users u ON u.id = rt.user_id
            WHERE rt.selector = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return;
    }

    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (
            strtotime($row["expires_at"]) > time() &&
            hash_equals($row["token_hash"], hash("sha256", $validator))
        ) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["user_name"] = $row["name"];
            $_SESSION["user_email"] = $row["email"];
            $_SESSION["user_role"] = $row["role"];
            $_SESSION["remember_me"] = true;
        } else {
            $deleteStmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            if ($deleteStmt) {
                $deleteStmt->bind_param("s", $selector);
                $deleteStmt->execute();
                $deleteStmt->close();
            }
            setcookie($cookieName, "", time() - 3600, "/");
        }
    }

    $stmt->close();
}

restore_remembered_login($conn, $rememberCookieName);