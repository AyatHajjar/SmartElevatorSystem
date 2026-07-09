<?php
session_start();

$jsonFile = "../json/authorizedUsers.json";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $formData = [
        "username" => trim($_POST["username"] ?? ""),
        "password" => trim($_POST["password"] ?? "")
    ];

    $existingData = [];

    if (file_exists($jsonFile)) {
        $fileContents = file_get_contents($jsonFile);
        $existingData = json_decode($fileContents, true) ?? [];
    }

    $accessGranted = false;

    foreach ($existingData as $userRecord) {

        if (
            $userRecord["username"] === $formData["username"] &&
            $userRecord["password"] === $formData["password"]
        ) {

            $accessGranted = true;

            $_SESSION["is_logged_in"] = true;
            $_SESSION["username"] = $formData["username"];

            break;
        }
    }

    if ($accessGranted) {

        header("Location: ayat_dashboard.php");
        exit();

    } else {

        echo "<h2>Access Denied</h2>";
        echo "<p>Invalid username or password.</p>";

    }

} else {

    echo "No Access.";

}
?>
<////Added trim() to clean user input.
Replaced manual member link with automatic redirection using header().
Removed displaying usernames and passwords because it is a security risk.
Kept session handling and strict comparison because they follow good programming practices/>
