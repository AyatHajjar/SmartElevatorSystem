window.addEventListener("load", function () {

    const year = document.getElementById("year");
    if (year) {
        year.textContent = new Date().getFullYear();
    }

    const reason = document.getElementById("reason");
    const counter = document.getElementById("charCount");

    counter.textContent = "180 characters remaining";

    reason.addEventListener("input", function () {

        const max = 180;
        const remaining = max - reason.value.length;

        if (remaining >= 0) {
            counter.textContent = remaining + " characters remaining";
            counter.style.color = "black";
        }
        else {
            counter.textContent =
                "You have exceeded the maximum number of characters allowed.";
            counter.style.color = "red";
        }
    });

});


document.getElementById("requestForm").addEventListener("submit",
function(event){

    event.preventDefault();

    const fullName =
        document.getElementById("fullName").value.trim();

    const email =
        document.getElementById("email").value.trim();

    const role =
        document.getElementById("role").value.trim();

    const reason =
        document.getElementById("reason").value.trim();

    const message =
        document.getElementById("requestMessage");

    if(fullName === "" ||
       email === "" ||
       role === "" ||
       reason === "")
    {
        message.textContent =
        "Please complete all required fields.";

        message.style.color = "red";
        return;
    }

    if(reason.length > 180)
    {
        message.textContent =
        "Reason must be 180 characters or less.";

        message.style.color = "red";
        return;
    }

    message.textContent =
    "Request submitted successfully.";

    message.style.color = "green";

});