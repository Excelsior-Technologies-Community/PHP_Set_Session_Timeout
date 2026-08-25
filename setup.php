<?php

$pdo = new PDO(
    'mysql:host=127.0.0.1',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE =>
        PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_EMULATE_PREPARES =>
        false,
    ]
);


$pdo->exec("
    CREATE DATABASE IF NOT EXISTS
    `PHP_Set_Session_Timeou`

    DEFAULT CHARACTER SET utf8mb4

    COLLATE utf8mb4_unicode_ci
");


$pdo->exec(
    "USE `PHP_Set_Session_Timeou`"
);


/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (

        id INT UNSIGNED
            AUTO_INCREMENT PRIMARY KEY,

        username VARCHAR(50)
            NOT NULL UNIQUE,

        password VARCHAR(255)
            NOT NULL,

        role ENUM('admin','user')
            NOT NULL DEFAULT 'user',

        failed_attempts INT UNSIGNED
            NOT NULL DEFAULT 0,

        locked_until DATETIME NULL,

        created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_locked_until
            (locked_until)

    ) ENGINE=InnoDB
");


/*
|--------------------------------------------------------------------------
| Remember Tokens
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS remember_tokens (

        id INT UNSIGNED
            AUTO_INCREMENT PRIMARY KEY,

        user_id INT UNSIGNED
            NOT NULL,

        selector VARCHAR(128)
            NOT NULL,

        token_hash VARCHAR(128)
            NOT NULL,

        expires_at INT UNSIGNED
            NOT NULL,

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE,

        INDEX idx_selector
            (selector),

        INDEX idx_user_id
            (user_id)

    ) ENGINE=InnoDB
");


/*
|--------------------------------------------------------------------------
| Session Logs
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS session_logs (

        id BIGINT UNSIGNED
            AUTO_INCREMENT PRIMARY KEY,

        user_id INT UNSIGNED NULL,

        event VARCHAR(50)
            NOT NULL,

        ip_address VARCHAR(45)
            NULL,

        user_agent TEXT NULL,

        created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL,

        INDEX idx_user_id
            (user_id),

        INDEX idx_event
            (event),

        INDEX idx_created_at
            (created_at)

    ) ENGINE=InnoDB
");


echo "
Database and tables created successfully.<br><br>
Security features enabled:<br>
✓ CSRF protection<br>
✓ Account lockout<br>
✓ Remember Me<br>
✓ Session logging
";
