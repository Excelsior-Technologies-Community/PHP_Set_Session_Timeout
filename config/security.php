<?php

/*
|--------------------------------------------------------------------------
| Security Headers
|--------------------------------------------------------------------------
*/

function applySecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Clickjacking Protection
    |--------------------------------------------------------------------------
    */

    header(
        'X-Frame-Options: DENY'
    );


    /*
    |--------------------------------------------------------------------------
    | MIME Sniffing Protection
    |--------------------------------------------------------------------------
    */

    header(
        'X-Content-Type-Options: nosniff'
    );


    /*
    |--------------------------------------------------------------------------
    | Referrer Policy
    |--------------------------------------------------------------------------
    */

    header(
        'Referrer-Policy: strict-origin-when-cross-origin'
    );


    /*
    |--------------------------------------------------------------------------
    | Permissions Policy
    |--------------------------------------------------------------------------
    */

    header(
        'Permissions-Policy: camera=(), microphone=(), geolocation=()'
    );


    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Opener Policy
    |--------------------------------------------------------------------------
    */

    header(
        'Cross-Origin-Opener-Policy: same-origin'
    );


    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Policy
    |--------------------------------------------------------------------------
    */

    header(
        'Cross-Origin-Resource-Policy: same-origin'
    );


    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    */

    header(
        'Content-Security-Policy: ' .
        "default-src 'self'; " .
        "script-src 'self' https://cdn.jsdelivr.net; " .
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
        "font-src 'self' https://cdn.jsdelivr.net; " .
        "img-src 'self' data:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none'; " .
        "base-uri 'self'; " .
        "form-action 'self'"
    );
}