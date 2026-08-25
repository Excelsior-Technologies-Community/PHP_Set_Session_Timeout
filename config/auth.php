<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';


/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

const SESSION_TIMEOUT = 120;          // 2 minutes
const SESSION_WARNING_TIME = 30;      // Warning at 30 seconds
const REMEMBER_ME_DAYS = 30;


/*
|--------------------------------------------------------------------------
| Authentication Helpers
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

function currentUser(PDO $pdo): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT id, username, role
         FROM users
         WHERE id = ?"
    );

    $stmt->execute([
        $_SESSION['user_id']
    ]);

    $user = $stmt->fetch();

    return $user ?: null;
}


/*
|--------------------------------------------------------------------------
| Get Client IP Address
|--------------------------------------------------------------------------
*/

function getClientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}


/*
|--------------------------------------------------------------------------
| Get User Agent
|--------------------------------------------------------------------------
*/

function getUserAgent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
}


/*
|--------------------------------------------------------------------------
| Session Activity Logger
|--------------------------------------------------------------------------
*/

function logSessionEvent(
    PDO $pdo,
    ?int $userId,
    string $event
): void {

    try {

        $stmt = $pdo->prepare(
            "INSERT INTO session_logs
            (user_id, event, ip_address, user_agent)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $userId,
            $event,
            getClientIp(),
            getUserAgent()
        ]);

    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Logging must never break authentication
        |--------------------------------------------------------------------------
        */

        error_log(
            'Session logging error: ' . $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| Check Session Expiration
|--------------------------------------------------------------------------
*/

function isSessionExpired(): bool
{
    if (!isset($_SESSION['LAST_ACTIVITY'])) {
        return false;
    }

    return (
        time() - $_SESSION['LAST_ACTIVITY']
    ) > SESSION_TIMEOUT;
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
    bool $logEvent = true
): void {

    /*
    |--------------------------------------------------------------------------
    | Save User ID Before Destroying Session
    |--------------------------------------------------------------------------
    */

    $userId = isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : null;


    /*
    |--------------------------------------------------------------------------
    | Log Event
    |--------------------------------------------------------------------------
    */

    if ($logEvent && $userId !== null) {

        logSessionEvent(
            $pdo,
            $userId,
            'session_expired'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Remember Me
    |--------------------------------------------------------------------------
    */

    clearRememberCookie($pdo);


    /*
    |--------------------------------------------------------------------------
    | Clear Session
    |--------------------------------------------------------------------------
    */

    $_SESSION = [];


    /*
    |--------------------------------------------------------------------------
    | Remove Session Cookie
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Destroy Session
    |--------------------------------------------------------------------------
    */

    session_destroy();
}


/*
|--------------------------------------------------------------------------
| Require Authentication
|--------------------------------------------------------------------------
*/

function requireAuth(
    PDO $pdo,
    ?string $role = null
): array {

    /*
    |--------------------------------------------------------------------------
    | Try Remember Me
    |--------------------------------------------------------------------------
    */

    if (!isLoggedIn()) {

        if (!checkRememberMe($pdo)) {

            header("Location: index.php");

            exit();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Session Timeout
    |--------------------------------------------------------------------------
    */

    if (isSessionExpired()) {

        destroyUserSession($pdo);

        header("Location: index.php?expired=1");

        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | Get User
    |--------------------------------------------------------------------------
    */

    $user = currentUser($pdo);

    if ($user === null) {

        destroyUserSession(
            $pdo,
            false
        );

        header("Location: index.php");

        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | Role Check
    |--------------------------------------------------------------------------
    */

    if (
        $role !== null &&
        $user['role'] !== $role
    ) {

        $dest = $user['role'] === 'admin'
            ? 'admin.php'
            : 'dashboard.php';

        header("Location: $dest");

        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Activity
    |--------------------------------------------------------------------------
    */

    refreshSessionActivity();


    return $user;
}


/*
|--------------------------------------------------------------------------
| Remember Me
|--------------------------------------------------------------------------
*/

function checkRememberMe(PDO $pdo): bool
{
    if (isset($_SESSION['user_id'])) {
        return true;
    }


    if (!isset($_COOKIE['remember'])) {
        return false;
    }


    $parts = explode(
        ':',
        $_COOKIE['remember'],
        2
    );


    if (count($parts) !== 2) {

        clearRememberCookie($pdo);

        return false;
    }


    [$selector, $token] = $parts;


    $tokenHash = hash(
        'sha256',
        $token
    );


    /*
    |--------------------------------------------------------------------------
    | Find Token
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT *
         FROM remember_tokens
         WHERE selector = ?
         AND expires_at > ?"
    );

    $stmt->execute([
        $selector,
        time()
    ]);

    $row = $stmt->fetch();


    if (!$row) {

        clearRememberCookie($pdo);

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Token
    |--------------------------------------------------------------------------
    */

    if (
        !hash_equals(
            $row['token_hash'],
            $tokenHash
        )
    ) {

        clearRememberCookie($pdo);

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Find User
    |--------------------------------------------------------------------------
    */

    $userStmt = $pdo->prepare(
        "SELECT id, username, role
         FROM users
         WHERE id = ?"
    );

    $userStmt->execute([
        $row['user_id']
    ]);

    $user = $userStmt->fetch();


    if (!$user) {

        clearRememberCookie($pdo);

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Regenerate Session
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    $_SESSION['user_id'] =
        $user['id'];

    $_SESSION['user'] =
        $user['username'];

    $_SESSION['role'] =
        $user['role'];

    $_SESSION['LAST_ACTIVITY'] =
        time();


    /*
    |--------------------------------------------------------------------------
    | Log Remember Me Login
    |--------------------------------------------------------------------------
    */

    logSessionEvent(
        $pdo,
        (int) $user['id'],
        'remember_me_login'
    );


    /*
    |--------------------------------------------------------------------------
    | Extend Remember Token
    |--------------------------------------------------------------------------
    */

    $newExpiry =
        time() +
        (86400 * REMEMBER_ME_DAYS);


    $updateStmt = $pdo->prepare(
        "UPDATE remember_tokens
         SET expires_at = ?
         WHERE id = ?"
    );

    $updateStmt->execute([
        $newExpiry,
        $row['id']
    ]);


    /*
    |--------------------------------------------------------------------------
    | Refresh Cookie
    |--------------------------------------------------------------------------
    */

    setcookie(
        'remember',
        "$selector:$token",
        [
            'expires' => $newExpiry,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );


    return true;
}


/*
|--------------------------------------------------------------------------
| Set Remember Me Cookie
|--------------------------------------------------------------------------
*/

function setRememberCookie(
    PDO $pdo,
    int $userId
): void {

    $selector =
        bin2hex(random_bytes(12));

    $token =
        bin2hex(random_bytes(32));

    $tokenHash =
        hash('sha256', $token);

    $expires =
        time() +
        (86400 * REMEMBER_ME_DAYS);


    /*
    |--------------------------------------------------------------------------
    | Delete Existing Tokens
    |--------------------------------------------------------------------------
    */

    $deleteStmt = $pdo->prepare(
        "DELETE FROM remember_tokens
         WHERE user_id = ?"
    );

    $deleteStmt->execute([
        $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Store New Token
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO remember_tokens
        (user_id, selector, token_hash, expires_at)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $userId,
        $selector,
        $tokenHash,
        $expires
    ]);


    /*
    |--------------------------------------------------------------------------
    | Cookie
    |--------------------------------------------------------------------------
    */

    setcookie(
        'remember',
        "$selector:$token",
        [
            'expires' => $expires,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| Clear Remember Me Cookie
|--------------------------------------------------------------------------
*/

function clearRememberCookie(PDO $pdo): void
{
    if (!isset($_COOKIE['remember'])) {
        return;
    }


    $parts = explode(
        ':',
        $_COOKIE['remember'],
        2
    );


    if (count($parts) === 2) {

        [$selector] = $parts;


        $stmt = $pdo->prepare(
            "DELETE FROM remember_tokens
             WHERE selector = ?"
        );

        $stmt->execute([
            $selector
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Cookie
    |--------------------------------------------------------------------------
    */

    setcookie(
        'remember',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}