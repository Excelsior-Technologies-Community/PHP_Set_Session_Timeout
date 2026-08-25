<?php

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Generate CSRF Token
|--------------------------------------------------------------------------
*/

function csrfToken(): string
{
    if (
        empty($_SESSION['_csrf_token'])
    ) {
        $_SESSION['_csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['_csrf_token'];
}


/*
|--------------------------------------------------------------------------
| Verify CSRF Token
|--------------------------------------------------------------------------
*/

function verifyCsrfToken(?string $token): bool
{
    if (
        empty($token) ||
        empty($_SESSION['_csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['_csrf_token'],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| Require CSRF Token
|--------------------------------------------------------------------------
*/

function requireCsrfToken(): void
{
    $token =
        $_POST['_csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? null;

    if (!verifyCsrfToken($token)) {

        http_response_code(419);

        exit('CSRF token validation failed.');
    }
}


/*
|--------------------------------------------------------------------------
| CSRF Hidden Input
|--------------------------------------------------------------------------
*/

function csrfInput(): string
{
    return
        '<input type="hidden" name="_csrf_token" value="' .
        htmlspecialchars(
            csrfToken(),
            ENT_QUOTES,
            'UTF-8'
        ) .
        '">';
}