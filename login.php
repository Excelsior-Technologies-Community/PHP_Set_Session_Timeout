<?php
session_start();

if (isset($_POST['username'])) {
    $_SESSION['user'] = $_POST['username'];
    $_SESSION['LAST_ACTIVITY'] = time(); // session last activity time
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
