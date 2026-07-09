<?php
session_start();

if (!isset($_SESSION["loggedIn"])) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Members Page</title>
</head>
<body>

<h1>Members Only Page</h1>

<p>Welcome, <?php echo $_SESSION["username"]; ?>!</p>

<p>This page is only for authorized members.</p>

<p>You are logged in using PHP session.</p>

<a href="logout.php">Logout</a>

</body>
</html>