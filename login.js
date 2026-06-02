window.addEventListener("load", function () {

    document.getElementById("username").focus();

    const year = document.getElementById("year");

    if (year) {
        year.textContent = new Date().getFullYear();
    }
});

document.getElementById("loginForm").addEventListener("submit", function (event) {

    event.preventDefault();

    const username =
        document.getElementById("username").value.trim();

    const password =
        document.getElementById("password").value.trim();

    const message =
        document.getElementById("loginMessage");

    if (username.length < 7 || password.length < 7) {

        message.textContent =
        "Username and password must be at least 7 characters.";

        message.style.color = "red";
    }
    else {

        message.textContent =
        "Login information is valid.";

        message.style.color = "green";
    }
});