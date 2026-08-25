<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/auth.php';


/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

$timeout = defined('SESSION_TIMEOUT')
    ? SESSION_TIMEOUT
    : 120;

$warningTime = defined('SESSION_WARNING_TIME')
    ? SESSION_WARNING_TIME
    : 20;


/*
|--------------------------------------------------------------------------
| Format Session Time
|--------------------------------------------------------------------------
*/

$timeoutMinutes = floor($timeout / 60);
$timeoutSeconds = $timeout % 60;

$formattedTimeout = sprintf(
    '%02d:%02d',
    $timeoutMinutes,
    $timeoutSeconds
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

    </style>

</head>


<body class="bg-light">


<div
    class="container d-flex justify-content-center align-items-center min-vh-100"
>


    <div
        class="card shadow login-card"
    >

        <div class="card-body p-4">


            <!-- Login Header -->

            <div class="text-center mb-4">

                <h3 class="card-title mb-2">
                    Login
                </h3>

                <p class="text-muted mb-0">
                    Secure Session Authentication
                </p>

            </div>


            <!-- Registration Message -->

            <?php if (isset($_GET['registered'])): ?>

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >

                    <strong>
                        Account created successfully.
                    </strong>

                    <br>

                    Please login to continue.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- Login Error -->

            <?php if (isset($_GET['error'])): ?>

                <div
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert"
                >

                    <strong>
                        Login Failed
                    </strong>

                    <br>

                    Invalid username or password.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- Session Expired -->

            <?php if (isset($_GET['expired'])): ?>

                <div
                    class="alert alert-warning alert-dismissible fade show"
                    role="alert"
                >

                    <strong>
                        Session Expired
                    </strong>

                    <br>

                    Your session expired due to inactivity.
                    Please login again.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- Logout Message -->

            <?php if (isset($_GET['logout'])): ?>

                <div
                    class="alert alert-info alert-dismissible fade show"
                    role="alert"
                >

                    <strong>
                        Logged Out
                    </strong>

                    <br>

                    You have been logged out successfully.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- Login Form -->

            <form
                method="post"
                action="login.php"
            >


                <!-- Username -->

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


                <!-- Password -->

                <div class="mb-3">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        required
                        autocomplete="current-password"
                    >

                </div>


                <!-- Remember Me -->

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


                <!-- Login Button -->

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                >
                    Login
                </button>


            </form>


            <!-- Security Information -->

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
                            <?= htmlspecialchars(
                                $formattedTimeout
                            ) ?>
                        </span>

                    </div>


                    <div class="d-flex justify-content-between">

                        <span>
                            Expiry warning:
                        </span>

                        <span class="badge bg-warning text-dark session-badge">
                            Last <?= htmlspecialchars(
                                $warningTime
                            ) ?> seconds
                        </span>

                    </div>

                </div>

            </div>


            <!-- Register -->

            <p class="text-center mt-4 mb-0">

                No account?

                <a href="register.php">
                    Sign Up
                </a>

            </p>


        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>