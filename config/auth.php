<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(PDO $pdo): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireAuth(PDO $pdo, ?string $role = null): array
{
    if (!isLoggedIn()) {
        if (!checkRememberMe($pdo)) {
            header("Location: index.php");
            exit();
        }
    }

    $user = currentUser($pdo);

    if ($user === null) {
        header("Location: index.php");
        exit();
    }

    if ($role !== null && $user['role'] !== $role) {
        $dest = $user['role'] === 'admin' ? 'admin.php' : 'dashboard.php';
        header("Location: $dest");
        exit();
    }

    return $user;
}

function checkRememberMe(PDO $pdo): bool
{
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    if (!isset($_COOKIE['remember'])) {
        return false;
    }

    [$selector, $token] = explode(':', $_COOKIE['remember'], 2);
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        "SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > ?"
    );
    $stmt->execute([$selector, time()]);
    $row = $stmt->fetch();

    if ($row && hash_equals($row['token_hash'], $tokenHash)) {
        $userStmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $userStmt->execute([$row['user_id']]);
        $user = $userStmt->fetch();

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user']      = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['LAST_ACTIVITY'] = time();

            $updateStmt = $pdo->prepare(
                "UPDATE remember_tokens SET expires_at = ? WHERE id = ?"
            );
            $updateStmt->execute([time() + (86400 * 30), $row['id']]);
        }
    }

    if (!$row || ($row && !$user)) {
        $deleteStmt = $pdo->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        $deleteStmt->execute([$selector]);
        setcookie('remember', '', time() - 3600, '/');
        return false;
    }

    return true;
}

function setRememberCookie(PDO $pdo, int $userId): void
{
    $selector   = bin2hex(random_bytes(12));
    $token      = bin2hex(random_bytes(32));
    $tokenHash  = hash('sha256', $token);
    $expires    = time() + (86400 * 30);

    $stmt = $pdo->prepare(
        "INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $selector, $tokenHash, $expires]);

    setcookie('remember', "$selector:$token", $expires, '/', '', false, true);
}

function clearRememberCookie(PDO $pdo): void
{
    if (isset($_COOKIE['remember'])) {
        [$selector] = explode(':', $_COOKIE['remember'], 2);
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        $stmt->execute([$selector]);
        setcookie('remember', '', time() - 3600, '/', '', false, true);
    }
}
?>
