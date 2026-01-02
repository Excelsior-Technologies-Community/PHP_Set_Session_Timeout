<?php
session_start();

// Session timeout in seconds
$timeout = 10;

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Check timeout
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
    <script>
        // JavaScript to auto reload page after timeout (10 seconds)
        setTimeout(function(){
            alert("Session expired! Logging out.");
            window.location.href = 'logout.php';
        }, <?php echo $timeout * 1000; ?>);
    </script>
</head>
<body>

<h3>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></h3>
<p>Session timeout: <?php echo $timeout; ?> seconds</p>

<a href="logout.php">Logout</a>

</body>
</html>
