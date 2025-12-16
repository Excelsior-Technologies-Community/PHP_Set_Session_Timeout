# PHP_Set_Session_Timeout



---

## Step 1: Create Project Folder

First, create a new project folder inside your local server directory (XAMPP).

Example path:

```
XAMPP/htdocs/PHP_Set_Session_Timeout
```

Explanation:  
This folder will contain all PHP files required for the session timeout project.

---

## Step 2: Create Required Files

Inside the project folder, create the following files:

```
index.php
dashboard.php
logout.php
```

Explanation:  
Each file has a specific purpose:
- `index.php` → Login page  
- `dashboard.php` → Protected page with session timeout logic  
- `logout.php` → Manual logout  

---

## Step 3: Create Login Page (index.php)

### File: `index.php`

```php
<?php
// Start PHP session
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
```

### Explanation:

- `session_start()` initializes the PHP session.
- User enters a username.
- Form submits data to `dashboard.php`.
- Session handling begins after login.

---

## Step 4: Create Dashboard with Session Timeout (dashboard.php)

### File: `dashboard.php`

```php
<?php
// Start session
session_start();

// Set timeout duration (in seconds)
$timeout = 30; // 30 seconds

// Check if user is logged in
if (!isset($_POST['username']) && !isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Set session values when user logs in
if (isset($_POST['username'])) {
    $_SESSION['user'] = $_POST['username'];
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Check session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();   // Remove session variables
    session_destroy(); // Destroy session
    echo "Session Expired. <a href='index.php'>Login Again</a>";
    exit();
}

// Update last activity time
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
```

### Explanation:

- `$timeout` defines how long the session stays active without user activity.
- `LAST_ACTIVITY` stores the last request time.
- If the user is inactive for more than **30 seconds**, the session expires.
- Session is destroyed automatically for security.
- User is forced to log in again.

---

## Step 5: Create Logout File (logout.php)

### File: `logout.php`

```php
<?php
// Start session
session_start();

// Remove all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect user to login page
header("Location: index.php");
?>
```

### Explanation:

- `session_unset()` clears all session variables.
- `session_destroy()` ends the session completely.
- User is redirected to the login page.

---

## Step 6: Run the Project

1. Start **Apache Server** from XAMPP.
2. Open browser.
3. Enter URL:

```
http://localhost/PHP_Set_Session_Timeout
```
