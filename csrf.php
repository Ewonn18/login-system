<?php
function ensure_csrf_token(): void
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
}

function get_csrf_token(): string
{
    ensure_csrf_token();
    return $_SESSION["csrf_token"];
}

function is_valid_csrf_token(?string $token): bool
{
    $sessionToken = $_SESSION["csrf_token"] ?? "";
    return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
}