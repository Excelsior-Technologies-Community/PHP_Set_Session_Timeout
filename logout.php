<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (session_status() !== PHP_SESSION_NONE) {
    session_unset();
    session_destroy();
}
clearRememberCookie($pdo);

header("Location: index.php");
exit();
?>
