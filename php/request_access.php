<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h1>Request Access Information</h1>";

    echo "Full Name: " . htmlspecialchars($_POST["fullName"]) . "<br>";
    echo "Email: " . htmlspecialchars($_POST["email"]) . "<br>";
    echo "Role: " . htmlspecialchars($_POST["role"]) . "<br>";
    echo "Reason for Access: " . htmlspecialchars($_POST["reason"]) . "<br>";

    echo '<p><a href="request_access.html">Back to Request Access</a></p>';
} else {
    echo "Direct access to this script is not allowed.";
}
?>
