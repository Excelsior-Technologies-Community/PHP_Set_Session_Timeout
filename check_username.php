<?php

require_once __DIR__ . '/config/db.php';
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


requireCsrfToken();


$username =
    trim($_POST['username'] ?? '');


if (
    $username === '' ||
    strlen($username) < 3
) {

    echo json_encode([
        'success' => true,
        'available' => false,
        'message' => 'Username must be at least 3 characters.'
    ]);

    exit();
}


if (
    !preg_match(
        '/^[A-Za-z0-9_]+$/',
        $username
    )
) {

    echo json_encode([
        'success' => true,
        'available' => false,
        'message' => 'Invalid username format.'
    ]);

    exit();
}


$stmt =
    $pdo->prepare(
        "SELECT COUNT(*)
         FROM users
         WHERE username = ?"
    );

$stmt->execute([
    $username
]);


$exists =
    (int) $stmt->fetchColumn() > 0;


echo json_encode([
    'success' => true,
    'available' => !$exists,
    'message' =>
    $exists
        ? 'Username already taken.'
        : 'Username available.'
]);

exit();
