<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/security.php';

applySecurityHeaders();


$errors = [];

$username = '';

$password = '';

$confirm = '';


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    requireCsrfToken();


    $username =
        trim($_POST['username'] ?? '');

    $password =
        $_POST['password'] ?? '';

    $confirm =
        $_POST['confirm'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Username Validation
    |--------------------------------------------------------------------------
    */

    if ($username === '') {

        $errors[] =
            'Username is required.';
    } elseif (
        strlen($username) < 3
    ) {

        $errors[] =
            'Username must be at least 3 characters.';
    } elseif (
        strlen($username) > 50
    ) {

        $errors[] =
            'Username cannot exceed 50 characters.';
    } elseif (
        !preg_match(
            '/^[A-Za-z0-9_]+$/',
            $username
        )
    ) {

        $errors[] =
            'Username may contain only letters, numbers and underscore.';
    }


    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    */

    if ($password === '') {

        $errors[] =
            'Password is required.';
    } elseif (
        strlen($password) < 6
    ) {

        $errors[] =
            'Password must be at least 6 characters.';
    } elseif (
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {

        $errors[] =
            'Password must contain uppercase, lowercase and a number.';
    }


    if (
        $password !== $confirm
    ) {

        $errors[] =
            'Passwords do not match.';
    }


    /*
    |--------------------------------------------------------------------------
    | Username Duplicate Check
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $checkStmt =
            $pdo->prepare(
                "SELECT COUNT(*)
                 FROM users
                 WHERE username = ?"
            );

        $checkStmt->execute([
            $username
        ]);

        if (
            (int) $checkStmt->fetchColumn() > 0
        ) {

            $errors[] =
                'Username already taken.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Account
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $countStmt =
            $pdo->query(
                "SELECT COUNT(*) FROM users"
            );

        $userCount =
            (int) $countStmt->fetchColumn();

        $role =
            ($userCount === 0)
            ? 'admin'
            : 'user';


        $stmt =
            $pdo->prepare(
                "INSERT INTO users
                (
                    username,
                    password,
                    role
                )
                VALUES (?, ?, ?)"
            );


        $hash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        try {

            $stmt->execute([
                $username,
                $hash,
                $role
            ]);


            header(
                "Location: index.php?registered=1"
            );

            exit();
        } catch (PDOException $e) {

            if (
                $e->getCode() === '23000'
            ) {

                $errors[] =
                    'Username already taken.';
            } else {

                $errors[] =
                    'Registration failed. Please try again.';
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Sign Up</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
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
        class="container d-flex justify-content-center align-items-center min-vh-100">

        <div
            class="card shadow-sm"
            style="width:100%;max-width:420px;">

            <div class="card-body">

                <h3 class="card-title text-center mb-4">
                    Sign Up
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


                <form
                    method="post"
                    action="register.php">

                    <?= csrfInput() ?>


                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="registerUsername">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            id="registerUsername"
                            class="form-control"
                            required
                            minlength="3"
                            maxlength="50"
                            value="<?= htmlspecialchars($username) ?>">

                        <div
                            id="usernameStatus"
                            class="form-text text-muted">
                            Enter at least 3 characters.
                        </div>

                    </div>


                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="password">
                            Password
                        </label>


                        <div class="password-wrapper">

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                required
                                minlength="6"
                                style="padding-right:45px;">


                            <button
                                type="button"
                                class="toggle-password"
                                data-target="password">
                                👁️
                            </button>

                        </div>


                        <div class="progress mt-2" style="height:8px;">

                            <div
                                id="passwordStrengthBar"
                                class="progress-bar"
                                role="progressbar"
                                style="width:0%"
                                aria-valuenow="0"
                                aria-valuemin="0"
                                aria-valuemax="100"></div>

                        </div>


                        <small
                            id="passwordStrengthText"
                            class="text-muted">
                            Password strength
                        </small>

                    </div>


                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="confirm">
                            Confirm Password
                        </label>


                        <div class="password-wrapper">

                            <input
                                type="password"
                                name="confirm"
                                id="confirm"
                                class="form-control"
                                required
                                minlength="6"
                                style="padding-right:45px;">


                            <button
                                type="button"
                                class="toggle-password"
                                data-target="confirm">
                                👁️
                            </button>

                        </div>

                    </div>


                    <button
                        class="btn btn-primary w-100"
                        type="submit">
                        Create Account
                    </button>

                </form>


                <p class="text-center mt-3 mb-0">

                    Already have an account?

                    <a href="index.php">
                        Login
                    </a>

                </p>

            </div>

        </div>

    </div>


    <script src="assets/auth.js"></script>


</body>

</html>