// Event listeners WITH PARAMETERS using ANONYMOUS FUNCTION
var elUsername = document.getElementById('username');						
var elMsg = document.getElementById('feedback'); 

function checkUsername(minLength) {
	if (elUsername.value.length < minLength) {
		elMsg.innerHTML = '<p>Username must be ' + minLength + ' characters or more</p>';
	} else {
		elMsg.innerHTML = '';  // Clear any error message
	}
}

elUsername.addEventListener('blur', function() {checkUsername(7)}, false);  // Anonymous function



// Event listeners WITH PARAMETERS using ANONYMOUS FUNCTION
var elPassword = document.getElementById('password');						
var elMsg = document.getElementById('feedbackP'); 

function checkPassword(minLength) {
	if (elPassword.value.length < minLength) {
		elMsg.innerHTML = '<p>Password must be ' + minLength + ' characters or more</p>';
	} else {
		elMsg.innerHTML = '';  // Clear any error message
	}
}

elPassword.addEventListener('blur', function() {checkPassword(7)}, false);  // Anonymous function


