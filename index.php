<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/security.php';

applySecurityHeaders();


$timeout =
    defined('SESSION_TIMEOUT')
        ? SESSION_TIMEOUT
        : 120;

$warningTime =
    defined('SESSION_WARNING_TIME')
        ? SESSION_WARNING_TIME
        : 20;


$timeoutMinutes =
    floor($timeout / 60);

$timeoutSeconds =
    $timeout % 60;


$formattedTimeout =
    sprintf(
        '%02d:%02d',
        $timeoutMinutes,
        $timeoutSeconds
    );


$lockSeconds =
    max(
        0,
        (int) ($_GET['seconds'] ?? 60)
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - Session Security</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f8f9fa;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 12px;
        }

        .security-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
        }

        .security-icon {
            font-size: 22px;
        }

        .session-badge {
            font-size: 13px;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
        }

    </style>

</head>


<body class="bg-light">


<div
    class="container d-flex justify-content-center align-items-center min-vh-100"
>

    <div class="card shadow login-card">

        <div class="card-body p-4">


            <div class="text-center mb-4">

                <h3 class="card-title mb-2">
                    Login
                </h3>

                <p class="text-muted mb-0">
                    Secure Session Authentication
                </p>

            </div>


            <?php if (isset($_GET['registered'])): ?>

                <div class="alert alert-success">

                    <strong>
                        Account created successfully.
                    </strong>

                    <br>

                    Please login to continue.

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['error'])): ?>

                <div class="alert alert-danger">

                    <strong>
                        Login Failed
                    </strong>

                    <br>

                    Invalid username or password.

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['expired'])): ?>

                <div class="alert alert-warning">

                    <strong>
                        Session Expired
                    </strong>

                    <br>

                    Your session expired due to inactivity.

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['logout'])): ?>

                <div class="alert alert-info">

                    <strong>
                        Logged Out
                    </strong>

                    <br>

                    You have been logged out successfully.

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['locked'])): ?>

                <div class="alert alert-danger">

                    <strong>
                        🔒 Account Temporarily Locked
                    </strong>

                    <br>

                    Too many failed login attempts.

                    <br>

                    Please try again in

                    <strong>
                        <span id="lockCountdown">
                            <?= $lockSeconds ?>
                        </span>
                    </strong>

                    seconds.

                </div>

            <?php endif; ?>


            <form
                method="post"
                action="login.php"
            >

                <?= csrfInput() ?>


                <div class="mb-3">

                    <label
                        for="username"
                        class="form-label"
                    >
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        id="username"
                        class="form-control"
                        required
                        autofocus
                        autocomplete="username"
                    >

                </div>


                <div class="mb-3">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>


                    <div class="password-wrapper">

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            required
                            autocomplete="current-password"
                            style="padding-right:45px;"
                        >


                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password"
                            aria-label="Show password"
                        >
                            👁️
                        </button>

                    </div>

                </div>


                <div class="mb-3 form-check">

                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        class="form-check-input"
                    >

                    <label
                        for="remember"
                        class="form-check-label"
                    >
                        Remember me
                    </label>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary w-100"
                    <?= isset($_GET['locked']) ? 'disabled' : '' ?>
                >
                    Login
                </button>

            </form>


            <div class="security-info mt-4">

                <div class="d-flex align-items-center mb-2">

                    <span class="security-icon me-2">
                        🔐
                    </span>

                    <strong>
                        Session Security
                    </strong>

                </div>


                <div class="small text-muted">

                    <div class="d-flex justify-content-between mb-1">

                        <span>
                            Session timeout:
                        </span>

                        <span class="badge bg-primary session-badge">
                            <?= htmlspecialchars($formattedTimeout) ?>
                        </span>

                    </div>


                    <div class="d-flex justify-content-between">

                        <span>
                            Expiry warning:
                        </span>

                        <span class="badge bg-warning text-dark session-badge">
                            Last <?= htmlspecialchars($warningTime) ?> seconds
                        </span>

                    </div>

                </div>

            </div>


            <p class="text-center mt-4 mb-0">

                No account?

                <a href="register.php">
                    Sign Up
                </a>

            </p>


        </div>

    </div>

</div>


<script src="assets/auth.js"></script>


</body>

</html>