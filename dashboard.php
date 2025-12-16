<?php
session_start();

// Set timeout duration (seconds)
$timeout = 30; // 30 seconds

// Check login
if (!isset($_POST['username']) && !isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Set session if coming from login page
if (isset($_POST['username'])) {
    $_SESSION['user'] = $_POST['username'];
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Check session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    echo "Session Expired. <a href='index.php'>Login Again</a>";
    exit();
}

// Update last activity
$_SESSION['LAST_ACTIVITY'] = time();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>

<h3>Welcome, <?php echo $_SESSION['user']; ?></h3>
<p>Session timeout: 30 seconds</p>

<a href="logout.php">Logout</a>

</body>
</html>
