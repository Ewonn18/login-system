<?php
function require_auth(): void
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: index.php?panel=signin&type=error&message=" . urlencode("Please sign in first."));
        exit();
    }
}
?>