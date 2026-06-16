<?php
echo "<h1>Request Access Information</h1>";

echo "Full Name: " . $_POST["fullName"] . "<br>";
echo "Email: " . $_POST["email"] . "<br>";
echo "Role: " . $_POST["role"] . "<br>";
echo "Reason for Access: " . $_POST["reason"] . "<br>";

echo '<p><a href="Request Access.html">Back to Request Access</a></p>';
?>