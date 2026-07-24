//Year
window.addEventListener("load", function () {
    const year = document.getElementById("year");
    if (year) {
        year.textContent = new Date().getFullYear();
    }

    const usernameInput = document.getElementById("username");
    if (usernameInput) {
        usernameInput.focus();
    }
});

//Username and Password length
var elForm = document.querySelector('form');
var username = document.getElementById('username');
var password = document.getElementById('password');
var requestMessage = document.getElementById('requestMessage');

function checkUsername(event) {
    if (username.value.trim().length <= 7) {
        requestMessage.innerHTML = 'Username must be have more then 7 letters';
        event.preventDefault();                
    } else {
        requestMessage.innerHTML = '';      
        requestMessage.className = "";
    }
}


function checkPassword(event) {
    if (password.value.trim().length <= 7) {
        requestMessage.innerHTML = 'Password must be have more then 7 letters';
        event.preventDefault();                
    } 
}


elForm.addEventListener('submit', function(event) {
    checkUsername(event); 
    checkPassword(event); 
}, false);
