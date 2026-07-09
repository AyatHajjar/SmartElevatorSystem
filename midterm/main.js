window.addEventListener("load", function () {

    const signupForm = document.getElementById("signupForm");
    const loginForm = document.getElementById("loginForm");
    const error = document.getElementById("error");

    window.showSignup = function () {
        signupForm.style.display = "block";
        loginForm.style.display = "none";
        error.innerHTML = "";
        document.getElementById("signupUsername").focus();
    };

    window.showLogin = function () {
        signupForm.style.display = "none";
        loginForm.style.display = "block";
        error.innerHTML = "";
        document.getElementById("loginUsername").focus();
    };

    function checkUsername(username) {
        if (username.value.length < 7) {
            error.innerHTML = "Username must be at least 7 characters.";
        } else {
            error.innerHTML = "";
        }
    }

    function checkPassword(password) {
        let errors = [];

        if (password.value.length < 7) {
            errors.push("Password must be at least 7 characters.");
        }

        if (!/[A-Z]/.test(password.value)) {
            errors.push("Password must contain at least one uppercase letter.");
        }

        if (!/[0-9]/.test(password.value)) {
            errors.push("Password must contain at least one number.");
        }

        error.innerHTML = errors.join("<br>");
    }

    document.getElementById("signupUsername").addEventListener("blur", function () {
        checkUsername(this);
    });

    document.getElementById("loginUsername").addEventListener("blur", function () {
        checkUsername(this);
    });

    document.getElementById("signupPassword").addEventListener("blur", function () {
        checkPassword(this);
    });

    document.getElementById("loginPassword").addEventListener("blur", function () {
        checkPassword(this);
    });

    function validateForm(event) {
        const form = event.target;
        const username = form.querySelector('input[name="username"]');
        const password = form.querySelector('input[name="password"]');

        let errors = [];

        if (username.value.length < 7) {
            errors.push("Username must be at least 7 characters.");
        }

        if (password.value.length < 7) {
            errors.push("Password must be at least 7 characters.");
        }

        if (!/[A-Z]/.test(password.value)) {
            errors.push("Password must contain at least one uppercase letter.");
        }

        if (!/[0-9]/.test(password.value)) {
            errors.push("Password must contain at least one number.");
        }

        if (errors.length > 0) {
            event.preventDefault();
            error.innerHTML = errors.join("<br>");
        }
    }

    signupForm.addEventListener("submit", validateForm);
    loginForm.addEventListener("submit", validateForm);

    showSignup();
});