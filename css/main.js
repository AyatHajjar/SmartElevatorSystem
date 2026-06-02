window.addEventListener("load", function () {
    const year = document.getElementById("year");
    if (year) {
        year.textContent = new Date().getFullYear();
    }

    const dateTime = document.getElementById("dateTime");
    if (dateTime) {
        dateTime.textContent = new Date().toLocaleString();
    }
});