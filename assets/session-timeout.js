(function () {

    'use strict';

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    | Default:
    | Session timeout  = 120 seconds (2 minutes)
    | Warning time     = 20 seconds
    |--------------------------------------------------------------------------
    */

    const timeout = Number(
        document.body.dataset.sessionTimeout || 120
    );

    const warningTime = Number(
        document.body.dataset.sessionWarning || 20
    );

    let remaining = timeout;

    let countdownInterval = null;

    let warningShown = false;

    let refreshInProgress = false;

    let lastRefresh = 0;


    /*
    |--------------------------------------------------------------------------
    | DOM Elements
    |--------------------------------------------------------------------------
    */

    const countdownElement =
        document.getElementById('sessionCountdown');

    const statusElement =
        document.getElementById('sessionStatus');

    const warningElement =
        document.getElementById('sessionWarning');

    const stayLoggedInButton =
        document.getElementById('stayLoggedIn');

    const logoutButton =
        document.getElementById('sessionLogout');


    /*
    |--------------------------------------------------------------------------
    | Format Time
    |--------------------------------------------------------------------------
    */

    function formatTime(seconds) {

        seconds = Math.max(0, seconds);

        const minutes = Math.floor(seconds / 60);

        const secs = seconds % 60;

        return String(minutes).padStart(2, '0') +
            ':' +
            String(secs).padStart(2, '0');
    }


    /*
    |--------------------------------------------------------------------------
    | Update Countdown
    |--------------------------------------------------------------------------
    */

    function updateCountdown() {

        if (!countdownElement) {
            return;
        }

        countdownElement.textContent =
            formatTime(remaining);


        /*
        |--------------------------------------------------------------------------
        | Warning State
        |--------------------------------------------------------------------------
        */

        if (remaining <= warningTime) {

            countdownElement.classList.remove(
                'text-success'
            );

            countdownElement.classList.add(
                'text-danger'
            );


            if (statusElement) {

                statusElement.textContent =
                    'Expiring Soon';

                statusElement.classList.remove(
                    'bg-success'
                );

                statusElement.classList.add(
                    'bg-danger'
                );
            }


            if (!warningShown) {

                warningShown = true;

                if (warningElement) {

                    warningElement.classList.remove(
                        'd-none'
                    );

                }

            }

        } else {

            countdownElement.classList.remove(
                'text-danger'
            );

            countdownElement.classList.add(
                'text-success'
            );


            if (statusElement) {

                statusElement.textContent =
                    'Active';

                statusElement.classList.remove(
                    'bg-danger'
                );

                statusElement.classList.add(
                    'bg-success'
                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Countdown Timer
    |--------------------------------------------------------------------------
    */

    function startCountdown() {

        updateCountdown();


        countdownInterval = setInterval(
            function () {

                remaining--;

                updateCountdown();


                /*
                |--------------------------------------------------------------------------
                | Session Expired
                |--------------------------------------------------------------------------
                */

                if (remaining <= 0) {

                    clearInterval(
                        countdownInterval
                    );

                    if (statusElement) {

                        statusElement.textContent =
                            'Expired';

                        statusElement.classList.remove(
                            'bg-success',
                            'bg-danger'
                        );

                        statusElement.classList.add(
                            'bg-secondary'
                        );

                    }


                    window.location.href =
                        'logout.php?expired=1';

                }

            },
            1000
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Session
    |--------------------------------------------------------------------------
    */

    function refreshSession(
        showMessage = false
    ) {

        if (refreshInProgress) {
            return;
        }


        refreshInProgress = true;


        fetch(
            'session_status.php',
            {
                method: 'POST',

                headers: {
                    'X-Requested-With':
                        'XMLHttpRequest'
                },

                credentials: 'same-origin'
            }
        )

        .then(function (response) {

            if (response.status === 401) {

                throw new Error(
                    'expired'
                );

            }

            return response.json();

        })

        .then(function (data) {

            if (!data.success) {

                throw new Error(
                    'expired'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Reset Countdown
            |--------------------------------------------------------------------------
            */

            remaining =
                Number(data.remaining);


            warningShown =
                false;


            /*
            |--------------------------------------------------------------------------
            | Hide Warning
            |--------------------------------------------------------------------------
            */

            if (warningElement) {

                warningElement.classList.add(
                    'd-none'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Update Status
            |--------------------------------------------------------------------------
            */

            if (statusElement) {

                statusElement.textContent =
                    'Active';

                statusElement.classList.remove(
                    'bg-danger',
                    'bg-secondary'
                );

                statusElement.classList.add(
                    'bg-success'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Success Message
            |--------------------------------------------------------------------------
            */

            if (showMessage) {

                showRefreshMessage(
                    'Session extended successfully.'
                );

            }


            updateCountdown();

        })

        .catch(function (error) {

            console.error(error);

            window.location.href =
                'index.php?expired=1';

        })

        .finally(function () {

            refreshInProgress =
                false;

            lastRefresh =
                Date.now();

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Session Refresh Message
    |--------------------------------------------------------------------------
    */

    function showRefreshMessage(message) {

        const messageElement =
            document.createElement('div');


        messageElement.className =
            'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 shadow';

        messageElement.style.zIndex =
            '9999';

        messageElement.textContent =
            message;


        document.body.appendChild(
            messageElement
        );


        setTimeout(function () {

            messageElement.remove();

        }, 3000);

    }


    /*
    |--------------------------------------------------------------------------
    | Stay Logged In Button
    |--------------------------------------------------------------------------
    */

    if (stayLoggedInButton) {

        stayLoggedInButton.addEventListener(
            'click',
            function () {

                refreshSession(true);

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Manual Logout
    |--------------------------------------------------------------------------
    */

    if (logoutButton) {

        logoutButton.addEventListener(
            'click',
            function () {

                window.location.href =
                    'logout.php';

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Detect User Activity
    |--------------------------------------------------------------------------
    */

    const activityEvents = [
        'click',
        'keydown',
        'scroll',
        'touchstart'
    ];


    activityEvents.forEach(
        function (eventName) {

            document.addEventListener(
                eventName,
                function () {

                    const now =
                        Date.now();


                    /*
                    |--------------------------------------------------------------------------
                    | Don't continuously hit server
                    |--------------------------------------------------------------------------
                    */

                    if (
                        now - lastRefresh <
                        30000
                    ) {

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Only automatically refresh near expiration
                    |--------------------------------------------------------------------------
                    */

                    if (
                        remaining >
                        warningTime
                    ) {

                        return;

                    }


                    refreshSession(
                        false
                    );

                },
                {
                    passive: true
                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Start Countdown
    |--------------------------------------------------------------------------
    */

    startCountdown();


})();