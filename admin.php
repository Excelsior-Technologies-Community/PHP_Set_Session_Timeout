<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$user = requireAuth($pdo, 'admin');

$timeout = 600;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    clearRememberCookie($pdo);
    header("Location: index.php?expired=1");
    exit();
}

$_SESSION['LAST_ACTIVITY'] = time();

$countStmt = $pdo->query("SELECT COUNT(*) FROM users");
$userCount = $countStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        setTimeout(function(){
            alert("Session expired! Logging out.");
            window.location.href = 'logout.php';
        }, <?php echo $timeout * 1000; ?>);
    </script>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Welcome, <?= htmlspecialchars($user['username']); ?> (Admin)</h3>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="mb-1"><strong>Role:</strong> <?= htmlspecialchars($user['role']); ?></p>
            <p class="mb-1"><strong>Total Users:</strong> <?= $userCount; ?></p>
            <p class="mb-0"><strong>Session ID:</strong> <?= session_id(); ?></p>
        </div>
    </div>
</div>
</body>
</html>
