<?php
session_start();

session_unset();
session_destroy();

echo "You have logged out.";
echo "<br>";
echo "<a href='index.html'>Back to login page</a>";
?>
