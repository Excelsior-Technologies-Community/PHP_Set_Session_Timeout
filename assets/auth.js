(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | Show / Hide Password
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.toggle-password')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    const targetId =
                        button.dataset.target;

                    const input =
                        document.getElementById(
                            targetId
                        );

                    if (!input) {
                        return;
                    }


                    if (
                        input.type === 'password'
                    ) {

                        input.type = 'text';

                        button.textContent = '🙈';

                        button.setAttribute(
                            'aria-label',
                            'Hide password'
                        );

                    } else {

                        input.type = 'password';

                        button.textContent = '👁️';

                        button.setAttribute(
                            'aria-label',
                            'Show password'
                        );
                    }

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Password Strength
    |--------------------------------------------------------------------------
    */

    const passwordInput =
        document.getElementById('password');

    const strengthBar =
        document.getElementById(
            'passwordStrengthBar'
        );

    const strengthText =
        document.getElementById(
            'passwordStrengthText'
        );


    function checkPasswordStrength(
        password
    ) {

        let score = 0;


        if (password.length >= 6) {
            score++;
        }

        if (password.length >= 10) {
            score++;
        }

        if (/[a-z]/.test(password)) {
            score++;
        }

        if (/[A-Z]/.test(password)) {
            score++;
        }

        if (/[0-9]/.test(password)) {
            score++;
        }

        if (/[^A-Za-z0-9]/.test(password)) {
            score++;
        }


        if (!strengthBar || !strengthText) {
            return score;
        }


        let width = 0;
        let text = 'Very Weak';


        if (score <= 1) {

            width = 20;
            text = 'Very Weak';

        } else if (score === 2) {

            width = 40;
            text = 'Weak';

        } else if (score === 3) {

            width = 60;
            text = 'Medium';

        } else if (score === 4) {

            width = 75;
            text = 'Good';

        } else {

            width = 100;
            text = 'Strong';
        }


        strengthBar.style.width =
            width + '%';

        strengthText.textContent =
            text;

        strengthBar.setAttribute(
            'aria-valuenow',
            width
        );


        return score;
    }


    if (passwordInput) {

        passwordInput.addEventListener(
            'input',
            function () {

                checkPasswordStrength(
                    passwordInput.value
                );

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Username Availability
    |--------------------------------------------------------------------------
    */

    const usernameInput =
        document.getElementById(
            'registerUsername'
        );

    const usernameStatus =
        document.getElementById(
            'usernameStatus'
        );


    let usernameTimer = null;


    if (
        usernameInput &&
        usernameStatus
    ) {

        usernameInput.addEventListener(
            'input',
            function () {

                clearTimeout(
                    usernameTimer
                );


                const username =
                    usernameInput.value.trim();


                if (username.length < 3) {

                    usernameStatus.textContent =
                        'Enter at least 3 characters.';

                    usernameStatus.className =
                        'form-text text-muted';

                    return;
                }


                usernameStatus.textContent =
                    'Checking...';

                usernameStatus.className =
                    'form-text text-secondary';


                usernameTimer =
                    setTimeout(
                        function () {

                            const csrf =
                                document.querySelector(
                                    'input[name="_csrf_token"]'
                                );


                            const body =
                                new URLSearchParams();


                            body.append(
                                'username',
                                username
                            );


                            if (csrf) {

                                body.append(
                                    '_csrf_token',
                                    csrf.value
                                );
                            }


                            fetch(
                                'check_username.php',
                                {
                                    method: 'POST',

                                    headers: {
                                        'Content-Type':
                                            'application/x-www-form-urlencoded',

                                        'X-Requested-With':
                                            'XMLHttpRequest'
                                    },

                                    credentials:
                                        'same-origin',

                                    body: body.toString()
                                }
                            )
                            .then(function (response) {
                                return response.json();
                            })
                            .then(function (data) {

                                if (data.available) {

                                    usernameStatus.textContent =
                                        '✓ Username available';

                                    usernameStatus.className =
                                        'form-text text-success';

                                } else {

                                    usernameStatus.textContent =
                                        '✗ Username already taken';

                                    usernameStatus.className =
                                        'form-text text-danger';
                                }

                            })
                            .catch(function () {

                                usernameStatus.textContent =
                                    'Unable to check username.';

                                usernameStatus.className =
                                    'form-text text-danger';

                            });

                        },
                        400
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Lock Countdown
    |--------------------------------------------------------------------------
    */

    const lockCountdown =
        document.getElementById(
            'lockCountdown'
        );


    if (lockCountdown) {

        let remaining =
            parseInt(
                lockCountdown.textContent,
                10
            );


        const interval =
            setInterval(
                function () {

                    remaining--;

                    lockCountdown.textContent =
                        Math.max(
                            0,
                            remaining
                        );


                    if (remaining <= 0) {

                        clearInterval(
                            interval
                        );

                        window.location.href =
                            'index.php';
                    }

                },
                1000
            );
    }


})();