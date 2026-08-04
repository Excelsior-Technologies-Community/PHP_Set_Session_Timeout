<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if ($username === '' || $password === '') {
    header("Location: index.php?error=1");
    exit();
}

$stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user']      = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['LAST_ACTIVITY'] = time();

    if ($remember) {
        setRememberCookie($pdo, $user['id']);
    }

    if ($user['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

header("Location: index.php?error=1");
exit();
?>
