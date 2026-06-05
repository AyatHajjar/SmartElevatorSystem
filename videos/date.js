// Get the current date and time
var today = new Date();

// Format the date and time to look clean
var currentDate = today.toLocaleDateString(); // e.g., "6/5/2026"
var currentTime = today.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); // e.g., "9:38 AM"

// Create the message and inject it into the 'info' element
var msg = '<p>Current Date: ' + currentDate + '  | Current Time: ' + currentTime + '</p>';

var elemt = document.getElementById('info');
elemt.innerHTML = msg;
