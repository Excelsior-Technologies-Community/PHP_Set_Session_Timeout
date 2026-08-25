<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/security.php';

applySecurityHeaders();


$user =
    requireAuth($pdo);


$errors = [];

$success = '';


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    requireCsrfToken();


    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Current Password
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare(
            "SELECT password
             FROM users
             WHERE id = ?"
        );

    $stmt->execute([
        $user['id']
    ]);

    $passwordHash =
        $stmt->fetchColumn();


    if (
        !$passwordHash ||
        !password_verify(
            $currentPassword,
            $passwordHash
        )
    ) {

        $errors[] =
            'Current password is incorrect.';
    }


    /*
    |--------------------------------------------------------------------------
    | New Password
    |--------------------------------------------------------------------------
    */

    if (
        strlen($newPassword) < 6
    ) {

        $errors[] =
            'New password must be at least 6 characters.';
    }


    if (
        !preg_match(
            '/[A-Z]/',
            $newPassword
        ) ||
        !preg_match(
            '/[a-z]/',
            $newPassword
        ) ||
        !preg_match(
            '/[0-9]/',
            $newPassword
        )
    ) {

        $errors[] =
            'New password must contain uppercase, lowercase and a number.';
    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Password
    |--------------------------------------------------------------------------
    */

    if (
        $newPassword !== $confirmPassword
    ) {

        $errors[] =
            'New passwords do not match.';
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Same Password
    |--------------------------------------------------------------------------
    */

    if (
        $passwordHash &&
        password_verify(
            $newPassword,
            $passwordHash
        )
    ) {

        $errors[] =
            'New password must be different from the current password.';
    }


    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $newHash =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        $stmt =
            $pdo->prepare(
                "UPDATE users
                 SET password = ?
                 WHERE id = ?"
            );


        $stmt->execute([
            $newHash,
            $user['id']
        ]);


        /*
        |--------------------------------------------------------------------------
        | Remove Remember Tokens
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare(
                "DELETE FROM remember_tokens
                 WHERE user_id = ?"
            );

        $stmt->execute([
            $user['id']
        ]);


        /*
        |--------------------------------------------------------------------------
        | Log Password Change
        |--------------------------------------------------------------------------
        */

        logSessionEvent(
            $pdo,
            (int) $user['id'],
            'password_changed'
        );


        /*
        |--------------------------------------------------------------------------
        | Refresh Session
        |--------------------------------------------------------------------------
        */

        session_regenerate_id(true);

        refreshSessionActivity();


        $success =
            'Password changed successfully. Please use your new password next time you login.';
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Change Password</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
        }

    </style>

</head>


<body class="bg-light">


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h3 class="mb-4">
                        🔄 Change Password
                    </h3>


                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger">

                            <?= implode(
                                '<br>',
                                array_map(
                                    'htmlspecialchars',
                                    $errors
                                )
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($success !== ''): ?>

                        <div class="alert alert-success">

                            <?= htmlspecialchars($success) ?>

                        </div>

                    <?php endif; ?>


                    <form
                        method="post"
                        action="change_password.php"
                    >

                        <?= csrfInput() ?>


                        <div class="mb-3">

                            <label class="form-label">
                                Current Password
                            </label>


                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="current_password"
                                    class="form-control"
                                    required
                                    style="padding-right:45px;"
                                >


                                <button
                                    type="button"
                                    class="toggle-password"
                                    data-target="current_password"
                                >
                                    👁️
                                </button>

                            </div>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                New Password
                            </label>


                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="new_password"
                                    id="password"
                                    class="form-control"
                                    required
                                    minlength="6"
                                    style="padding-right:45px;"
                                >


                                <button
                                    type="button"
                                    class="toggle-password"
                                    data-target="password"
                                >
                                    👁️
                                </button>

                            </div>


                            <div
                                class="progress mt-2"
                                style="height:8px;"
                            >

                                <div
                                    id="passwordStrengthBar"
                                    class="progress-bar"
                                    style="width:0%"
                                ></div>

                            </div>


                            <small
                                id="passwordStrengthText"
                                class="text-muted"
                            >
                                Password strength
                            </small>

                        </div>


                        <div class="mb-4">

                            <label class="form-label">
                                Confirm New Password
                            </label>


                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control"
                                    required
                                    minlength="6"
                                    style="padding-right:45px;"
                                >


                                <button
                                    type="button"
                                    class="toggle-password"
                                    data-target="confirm_password"
                                >
                                    👁️
                                </button>

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Change Password
                        </button>

                    </form>


                    <div class="text-center mt-3">

                        <?php if ($user['role'] === 'admin'): ?>

                            <a href="admin.php">
                                ← Back to Dashboard
                            </a>

                        <?php else: ?>

                            <a href="dashboard.php">
                                ← Back to Dashboard
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script src="assets/auth.js"></script>


</body>

</html>