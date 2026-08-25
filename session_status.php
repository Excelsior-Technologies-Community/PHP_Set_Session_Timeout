<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
*/

if (!isLoggedIn()) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'expired' => true,
        'message' => 'Session not found.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Session Expiration Check
|--------------------------------------------------------------------------
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


/*
|--------------------------------------------------------------------------
| Log Session Renewal
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];

logSessionEvent(
    $pdo,
    $userId,
    'session_renewed'
);


/*
|--------------------------------------------------------------------------
| Calculate Remaining Time
|--------------------------------------------------------------------------
*/

$remaining =
    SESSION_TIMEOUT -
    (
        time() -
        $_SESSION['LAST_ACTIVITY']
    );


if ($remaining < 0) {
    $remaining = 0;
}


/*
|--------------------------------------------------------------------------
| Return Status
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'expired' => false,
    'remaining' => $remaining,
    'timeout' => SESSION_TIMEOUT,
    'warning' => SESSION_WARNING_TIME,
    'last_activity' => $_SESSION['LAST_ACTIVITY']
]);

exit();