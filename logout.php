<?php
session_start();
session_unset();
session_destroy();

header("Location: index.php?panel=signin&type=success&message=" . urlencode("You have been logged out."));
exit();
?>