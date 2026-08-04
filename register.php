<?php
require_once __DIR__ . '/config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = (int) $countStmt->fetchColumn();
        $role = ($userCount === 0) ? 'admin' : 'user';

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt->execute([$username, $hash, $role]);
            header("Location: index.php?registered=1");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() === 23000) {
                $errors[] = 'Username already taken.';
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-sm" style="width: 100%; max-width: 400px;">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Sign Up</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
            <?php endif; ?>

            <form method="post" action="register.php">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($username ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Create Account</button>
            </form>
            <p class="text-center mt-3 mb-0">
                Already have an account? <a href="index.php">Login</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
