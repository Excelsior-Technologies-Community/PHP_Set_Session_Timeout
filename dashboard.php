<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$user = requireAuth(
    $pdo,
    'user'
);


/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

$timeout = SESSION_TIMEOUT;
$warningTime = SESSION_WARNING_TIME;


/*
|--------------------------------------------------------------------------
| Recent Session Activity
|--------------------------------------------------------------------------
*/

$logStmt = $pdo->prepare(
    "SELECT event, ip_address, created_at
     FROM session_logs
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 10"
);

$logStmt->execute([
    $user['id']
]);

$sessionLogs = $logStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Initial Countdown
|--------------------------------------------------------------------------
*/

$initialMinutes = floor($timeout / 60);
$initialSeconds = $timeout % 60;

$initialCountdown = sprintf(
    '%02d:%02d',
    $initialMinutes,
    $initialSeconds
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

    <title>User Dashboard</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        .session-card {
            border-left: 5px solid #198754;
        }

        #sessionCountdown {
            font-size: 28px;
            font-weight: 700;
        }

        .session-warning {
            border-left: 5px solid #dc3545;
        }

    </style>

</head>


<body
    class="bg-light"
    data-session-timeout="<?= htmlspecialchars($timeout) ?>"
    data-session-warning="<?= htmlspecialchars($warningTime) ?>"
>


<div class="container py-5">


    <!-- Header -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <h3>

            Welcome,

            <?= htmlspecialchars(
                $user['username']
            ); ?>

            <span class="text-secondary">
                (User)
            </span>

        </h3>


        <a
            href="logout.php"
            id="sessionLogout"
            class="btn btn-outline-danger"
        >
            Logout
        </a>

    </div>


    <!-- Session Status -->

    <div
        class="card shadow-sm session-card mb-4"
    >

        <div class="card-body">

            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-3"
            >

                <div>

                    <h5 class="mb-1">
                        Session Status
                    </h5>

                    <span
                        id="sessionStatus"
                        class="badge bg-success"
                    >
                        Active
                    </span>

                </div>


                <div class="text-center">

                    <small class="text-muted d-block">
                        Session expires in
                    </small>

                    <span
                        id="sessionCountdown"
                        class="text-success"
                    >
                        <?= $initialCountdown ?>
                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- Session Warning -->

    <div
        id="sessionWarning"
        class="alert alert-warning session-warning d-none"
    >

        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-3"
        >

            <div>

                <strong>
                    Your session is about to expire.
                </strong>

                <br>

                <small>
                    Click "Stay Logged In" to continue your session.
                </small>

            </div>


            <button
                type="button"
                id="stayLoggedIn"
                class="btn btn-warning"
            >
                Stay Logged In
            </button>

        </div>

    </div>


    <!-- User Information -->

    <div class="card shadow-sm">

        <div class="card-body">

            <p class="mb-1">

                <strong>
                    Role:
                </strong>

                <?= htmlspecialchars(
                    $user['role']
                ); ?>

            </p>


            <p class="mb-1">

                <strong>
                    Session Timeout:
                </strong>

                <?= $timeout; ?> seconds

                <span class="text-muted">
                    (<?= round($timeout / 60, 2); ?> minutes)
                </span>

            </p>


            <p class="mb-1">

                <strong>
                    Warning Time:
                </strong>

                <?= $warningTime; ?> seconds

            </p>


            <p class="mb-0">

                <strong>
                    Session ID:
                </strong>

                <?= htmlspecialchars(
                    session_id()
                ); ?>

            </p>

        </div>

    </div>


    <!-- Recent Session Activity -->

    <div class="card shadow-sm mt-4">

        <div class="card-header">

            <h5 class="mb-0">
                Recent Session Activity
            </h5>

        </div>


        <div class="card-body p-0">

            <?php if (empty($sessionLogs)): ?>

                <div class="p-4 text-muted">

                    No session activity found.

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table
                        class="table table-hover mb-0"
                    >

                        <thead class="table-light">

                            <tr>

                                <th>
                                    Event
                                </th>

                                <th>
                                    IP Address
                                </th>

                                <th>
                                    Date & Time
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $sessionLogs
                                as $log
                            ): ?>

                                <?php

                                $eventLabels = [

                                    'login' => [
                                        'Login Successful',
                                        'success'
                                    ],

                                    'logout' => [
                                        'Logout',
                                        'secondary'
                                    ],

                                    'session_renewed' => [
                                        'Session Renewed',
                                        'primary'
                                    ],

                                    'session_expired' => [
                                        'Session Expired',
                                        'danger'
                                    ],

                                    'remember_me_login' => [
                                        'Remember Me Login',
                                        'info'
                                    ],

                                    'failed_login' => [
                                        'Failed Login',
                                        'warning'
                                    ],

                                ];


                                $event =
                                    $eventLabels[
                                        $log['event']
                                    ]
                                    ??
                                    [
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $log['event']
                                            )
                                        ),
                                        'secondary'
                                    ];

                                ?>

                                <tr>

                                    <td>

                                        <span
                                            class="badge bg-<?= htmlspecialchars(
                                                $event[1]
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $event[0]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $log['ip_address']
                                            ?? 'Unknown'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $log['created_at']
                                                )
                                            )
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>


</div>


<script src="assets/session-timeout.js"></script>


</body>

</html>