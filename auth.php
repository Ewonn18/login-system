<?php
function require_auth(): void
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Please sign in first."));
        exit();
    }
}

function is_admin(): bool
{
    return isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin";
}

function require_admin(): void
{
    if (!is_admin()) {
        header("Location: dashboard.php?type=error&message=" . urlencode("Admin access only."));
        exit();
    }
}