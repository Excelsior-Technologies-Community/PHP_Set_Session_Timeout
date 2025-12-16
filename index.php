<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h3>Login</h3>

<form method="post" action="dashboard.php">
    Username: <input type="text" name="username" required><br><br>
    <input type="submit" value="Login">
</form>

</body>
</html>
