<?php

/*
|--------------------------------------------------------------------------
| Authentication Configuration
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Login Security
|--------------------------------------------------------------------------
*/

define('MAX_LOGIN_ATTEMPTS', 5);

define('LOGIN_LOCK_SECONDS', 60);


/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
|
| 120 seconds = 2 minutes
| 20 seconds = warning before expiry
|
*/

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 120);
}

if (!defined('SESSION_WARNING_TIME')) {
    define('SESSION_WARNING_TIME', 20);
}


/*
|--------------------------------------------------------------------------
| Check User Logged In
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| Require Authentication
|--------------------------------------------------------------------------
*/

function requireAuth(
    PDO $pdo,
    ?string $requiredRole = null
): array {

    /*
    |----------------------------------------------------------------------
    | Not Logged In
    |----------------------------------------------------------------------
    */

    if (!isLoggedIn()) {

        header('Location: index.php');

        exit();
    }


    /*
    |----------------------------------------------------------------------
    | Check Session Expiration
    |----------------------------------------------------------------------
    */

    if (isSessionExpired()) {

        $userId = (int) $_SESSION['user_id'];

        logSessionEvent(
            $pdo,
            $userId,
            'session_expired'
        );

        destroyUserSession(
            $pdo,
            false
        );

        header('Location: index.php?expired=1');

        exit();
    }


    /*
    |----------------------------------------------------------------------
    | Role Check
    |----------------------------------------------------------------------
    */

    if (
        $requiredRole !== null &&
        ($_SESSION['role'] ?? null) !== $requiredRole
    ) {

        http_response_code(403);

        exit('Access denied.');
    }


    /*
    |----------------------------------------------------------------------
    | Get User
    |----------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $_SESSION['user_id']
    ]);

    $user = $stmt->fetch();


    /*
    |----------------------------------------------------------------------
    | User No Longer Exists
    |----------------------------------------------------------------------
    */

    if (!$user) {

        destroyUserSession(
            $pdo,
            false
        );

        header('Location: index.php');

        exit();
    }


    return $user;
}


/*
|--------------------------------------------------------------------------
| Session Expired Check
|--------------------------------------------------------------------------
*/

function isSessionExpired(): bool
{
    if (!isset($_SESSION['LAST_ACTIVITY'])) {
        return true;
    }

    return (
        time() -
        $_SESSION['LAST_ACTIVITY']
    ) >= SESSION_TIMEOUT;
}


/*
|--------------------------------------------------------------------------
| Refresh Session Activity
|--------------------------------------------------------------------------
*/

function refreshSessionActivity(): void
{
    $_SESSION['LAST_ACTIVITY'] = time();
}


/*
|--------------------------------------------------------------------------
| Destroy User Session
|--------------------------------------------------------------------------
*/

function destroyUserSession(
    PDO $pdo,
    bool $logLogout = true
): void {

    if (
        $logLogout &&
        isset($_SESSION['user_id'])
    ) {

        logSessionEvent(
            $pdo,
            (int) $_SESSION['user_id'],
            'logout'
        );
    }


    /*
    |----------------------------------------------------------------------
    | Clear Remember Me Cookie
    |----------------------------------------------------------------------
    */

    if (isset($_COOKIE['remember_token'])) {

        setcookie(
            'remember_token',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }


    /*
    |----------------------------------------------------------------------
    | Destroy Session
    |----------------------------------------------------------------------
    */

    $_SESSION = [];


    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }


    session_destroy();
}


/*
|--------------------------------------------------------------------------
| Session Event Logging
|--------------------------------------------------------------------------
*/

function logSessionEvent(
    PDO $pdo,
    ?int $userId,
    string $event
): void {

    /*
    |----------------------------------------------------------------------
    | Make sure session_logs table exists
    |----------------------------------------------------------------------
    */

    $ipAddress =
        $_SERVER['REMOTE_ADDR']
        ?? null;


    $stmt = $pdo->prepare("
        INSERT INTO session_logs
        (
            user_id,
            event,
            ip_address,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW()
        )
    ");


    $stmt->execute([
        $userId,
        $event,
        $ipAddress
    ]);
}


/*
|--------------------------------------------------------------------------
| Reset Expired Login Lock
|--------------------------------------------------------------------------
*/

function resetExpiredLoginLock(
    PDO $pdo,
    array &$user
): void {

    if (
        !empty($user['locked_until']) &&
        strtotime($user['locked_until']) <= time()
    ) {

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                failed_attempts = 0,
                locked_until = NULL
            WHERE id = ?
        ");

        $stmt->execute([
            $user['id']
        ]);


        $user['failed_attempts'] = 0;

        $user['locked_until'] = null;
    }
}


/*
|--------------------------------------------------------------------------
| Check Account Locked
|--------------------------------------------------------------------------
*/

function isAccountLocked(
    array $user
): bool {

    if (
        empty($user['locked_until'])
    ) {

        return false;
    }


    return
        strtotime(
            $user['locked_until']
        ) > time();
}


/*
|--------------------------------------------------------------------------
| Get Lock Remaining Seconds
|--------------------------------------------------------------------------
*/

function getLockRemainingSeconds(
    array $user
): int {

    if (
        empty($user['locked_until'])
    ) {

        return 0;
    }


    return max(
        0,
        strtotime(
            $user['locked_until']
        ) - time()
    );
}


/*
|--------------------------------------------------------------------------
| Record Failed Login
|--------------------------------------------------------------------------
*/

function recordFailedLogin(
    PDO $pdo,
    int $userId
): int {

    $stmt = $pdo->prepare("
        SELECT failed_attempts
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $userId
    ]);


    $attempts =
        (int) $stmt->fetchColumn();


    $attempts++;


    /*
    |----------------------------------------------------------------------
    | Lock After 5 Attempts
    |----------------------------------------------------------------------
    */

    if (
        $attempts >= MAX_LOGIN_ATTEMPTS
    ) {

        $lockedUntil =
            date(
                'Y-m-d H:i:s',
                time() + LOGIN_LOCK_SECONDS
            );


        $stmt = $pdo->prepare("
            UPDATE users
            SET
                failed_attempts = ?,
                locked_until = ?
            WHERE id = ?
        ");


        $stmt->execute([
            $attempts,
            $lockedUntil,
            $userId
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE users
            SET failed_attempts = ?
            WHERE id = ?
        ");


        $stmt->execute([
            $attempts,
            $userId
        ]);
    }


    return $attempts;
}


/*
|--------------------------------------------------------------------------
| Reset Failed Login Attempts
|--------------------------------------------------------------------------
*/

function resetFailedLoginAttempts(
    PDO $pdo,
    int $userId
): void {

    $stmt = $pdo->prepare("
        UPDATE users
        SET
            failed_attempts = 0,
            locked_until = NULL
        WHERE id = ?
    ");


    $stmt->execute([
        $userId
    ]);
}

/*
|--------------------------------------------------------------------------
| Clear Remember Me Cookie
|--------------------------------------------------------------------------
*/

function clearRememberCookie(PDO $pdo): void
{
    /*
    |----------------------------------------------------------------------
    | Delete Remember Token From Database
    |----------------------------------------------------------------------
    */

    if (!empty($_COOKIE['remember_token'])) {

        $token = $_COOKIE['remember_token'];

        /*
        |------------------------------------------------------------------
        | Delete token
        |------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            DELETE FROM remember_tokens
            WHERE token_hash = ?
        ");

        $stmt->execute([
            hash('sha256', $token)
        ]);
    }


    /*
    |----------------------------------------------------------------------
    | Delete Browser Cookie
    |----------------------------------------------------------------------
    */

    setcookie(
        'remember_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );


    /*
    |----------------------------------------------------------------------
    | Remove Current Request Cookie
    |----------------------------------------------------------------------
    */

    unset($_COOKIE['remember_token']);
}