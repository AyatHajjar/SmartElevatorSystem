<?php
    session_start();
    
    $jsonFile = "../json/authorizedUsers.json";
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        echo "<h1>Request Access Information</h1>";

        $formData = [
            "username" => $_POST["username"] ?? "",
            "password" => $_POST["password"] ?? ""
        ];

        $existingData = [];
        if (file_exists($jsonFile)) {
            $fileContents = file_get_contents($jsonFile);
            $existingData = json_decode($fileContents, true) ?? [];
        }
       
        $accessGranted = false;
        foreach ($existingData as $userRecord) {
            if ($userRecord["username"] === $formData["username"] && $userRecord["password"] === $formData["password"]) {
                $accessGranted = true;
                break;
            }
        }
        
        if ($accessGranted) {
            echo '<p>Login Matches! You are a member</p>';
            echo '<p><a href="member.php">member</a></p>';
            
            $_SESSION["is_logged_in"] = true;
            $_SESSION["username"] = $formData["username"];
        } else {
            echo '<p>Access Denied: your not a member</p>';
        }
        
        if (file_exists($jsonFile)) {
            $loginJson = file_get_contents($jsonFile);
            $loginRequests = json_decode($loginJson, true) ?? [];

            echo "<h2>All Stored Requests:</h2>";
            foreach ($loginRequests as $request) {
                $user = htmlspecialchars($request["username"] ?? "N/A");
                $pass = htmlspecialchars($request["password"] ?? "N/A");
               
                echo "<strong>User:</strong> " . $user . "<br>";
                echo "<strong>Pass:</strong> " . $pass . "<br>";
                echo "<hr>";
            }
        }

    } else {
        echo "No Access.";
    }
?>
