<?php 
    // authentication.php
    session_start();
    $username= $_POST['username'];
    $password = $_POST['password'];
    $authenticated = FALSE;

    $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $query = "SELECT * FROM authorizedUsers WHERE username = '$username'";
    $rows = $db->query($query);
    foreach ($rows as $row) {
        echo $row['username'];
        if($username === $row['username'] && $password === $row['password']) {
            $authenticated = TRUE;
        }
    }

    if($authenticated) {
        $_SESSION['username'] = $username;  
        echo "<p>Congrats, you a logged in</p>"; 
        echo "<p>Click <a href='member.php'> here </a> to goto the members only page</p>";
    } else {
        echo "<p>You are not authenticated!!!!</p>"; 
    }
?>
