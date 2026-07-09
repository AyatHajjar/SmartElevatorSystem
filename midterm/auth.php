<?php
session_start();

$file = "users.json";

$username = $_POST["username"];
$password = $_POST["password"];
$action = $_POST["action"];

if (file_exists($file)) {
    $jsonData = file_get_contents($file);
    $users = json_decode($jsonData, true);
} else {
    $users = [];
}

if ($users == null) {
    $users = [];
}


if ($action == "signup") {

    $newUser = [
        "username" => $username,
        "password" => $password
    ];

    $users[] = $newUser;

    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));

    echo "Signup successful. ";
    echo "<a href='index.html'>Go to login page</a>";
    exit();
}


if ($action == "login") {

    foreach ($users as $user) {
        if ($user["username"] == $username && $user["password"] == $password) {

            $_SESSION["loggedIn"] = true;
            $_SESSION["username"] = $username;

            header("Location: members.php");
            exit();
        }
    }

    echo "You are not authorized.";
    echo "<br>";
    echo "<a href='index.html'>Try again</a>";
    exit();
}
?>