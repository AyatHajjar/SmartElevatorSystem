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

        message.textContent = "Username and password must be at least 7 characters.";
        message.style.color = "blue";
    }
    else {

        message.textContent = "Login information is valid.";
        message.style.color = "green";
    }
    window.addEventListener('load', setup, false);
});

/*
var elUsername = document.getElementById('username');						
var elMsg = document.getElementById('feedback'); 

function checkUsername(minLength) {
	if (elUsername.value.length < minLength) {
		elMsg.innerHTML = '<p>Username must be ' + minLength + ' characters or more</p>';
		message.style.color = "green";
		return false;
	} else {
		elMsg.innerHTML = '';  // Clear any error message
		return true;
	}
}

elUsername.addEventListener('blur', function() {checkUsername(7)}, false);  // Anonymous function



// Event listeners WITH PARAMETERS using ANONYMOUS FUNCTION
var elPassword = document.getElementById('password');						

function checkPassword(minLength) {
	if (elPassword.value.length < minLength) {
		elMsg.innerHTML = '<p>Password must be ' + minLength + ' characters or more</p>';
		message.style.color = "red";
		return false;
	} else {
		elMsg.innerHTML = '';  // Clear any error message
		return true;
	}
}

elPassword.addEventListener('blur', function() {checkPassword(7)}, false);  // Anonymous function
*/
