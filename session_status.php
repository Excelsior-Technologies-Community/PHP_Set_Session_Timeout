<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/security.php';

applySecurityHeaders();

header(
    'Content-Type: application/json'
);


if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit();
}


if (!isLoggedIn()) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'expired' => true,
        'message' => 'Session not found.'
    ]);

    exit();
}


$token =
    $_SERVER['HTTP_X_CSRF_TOKEN']
    ?? $_POST['_csrf_token']
    ?? null;


if (!verifyCsrfToken($token)) {

    http_response_code(419);

    echo json_encode([
        'success' => false,
        'message' => 'CSRF token validation failed.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Session Expiration
|--------------------------------------------------------------------------
*/

if (isSessionExpired()) {

    $userId =
        (int) $_SESSION['user_id'];


    logSessionEvent(
        $pdo,
        $userId,
        'session_expired'
    );


    destroyUserSession(
        $pdo,
        false
    );


    http_response_code(401);

    echo json_encode([
        'success' => false,
        'expired' => true,
        'message' => 'Your session has expired.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Refresh Session
|--------------------------------------------------------------------------
*/

refreshSessionActivity();


$userId =
    (int) $_SESSION['user_id'];


logSessionEvent(
    $pdo,
    $userId,
    'session_renewed'
);


$remaining =
    SESSION_TIMEOUT -
    (
        time() -
        $_SESSION['LAST_ACTIVITY']
    );


$remaining =
    max(
        0,
        $remaining
    );


echo json_encode([
    'success' => true,
    'expired' => false,
    'remaining' => $remaining,
    'timeout' => SESSION_TIMEOUT,
    'warning' => SESSION_WARNING_TIME,
    'last_activity' =>
    $_SESSION['LAST_ACTIVITY']
]);

exit();
