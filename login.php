<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';


$username = trim(
    $_POST['username'] ?? ''
);

$password = $_POST['password'] ?? '';

$remember = isset($_POST['remember']);


/*
|--------------------------------------------------------------------------
| Validate Input
|--------------------------------------------------------------------------
*/

if (
    $username === '' ||
    $password === ''
) {

    logSessionEvent(
        $pdo,
        null,
        'failed_login'
    );

    header("Location: index.php?error=1");

    exit();
}


/*
|--------------------------------------------------------------------------
| Find User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id, username, password, role
     FROM users
     WHERE username = ?"
);

$stmt->execute([
    $username
]);

$user = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Verify Login
|--------------------------------------------------------------------------
*/

if (
    $user &&
    password_verify(
        $password,
        $user['password']
    )
) {

    /*
    |--------------------------------------------------------------------------
    | Prevent Session Fixation
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | Create Session
    |--------------------------------------------------------------------------
    */

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
    | Log Successful Login
    |--------------------------------------------------------------------------
    */

    logSessionEvent(
        $pdo,
        (int) $user['id'],
        'login'
    );


    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    */

    if ($remember) {

        setRememberCookie(
            $pdo,
            (int) $user['id']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    if ($user['role'] === 'admin') {

        header("Location: admin.php");

    } else {

        header("Location: dashboard.php");
    }

    exit();
}


/*
|--------------------------------------------------------------------------
| Failed Login
|--------------------------------------------------------------------------
*/

logSessionEvent(
    $pdo,
    $user ? (int) $user['id'] : null,
    'failed_login'
);


header("Location: index.php?error=1");

exit();