<?php
    session_start();

    if(isset($_SESSION['username'])) {
        require 'databaseFunctions.php';

        $host = '127.0.0.1';
        $database = 'elevator';
        $tablename = 'elevatorNetwork';
        $path = 'mysql:host=' . $host . ';dbname=' . $database;
        $user = 'ese';
        $password = 'ese';

        $db = connect($path, $user, $password);

        if(isset($_POST['nodeID'])) { $nodeID = (int)$_POST['nodeID']; }
        if(isset($_POST['doorState'])) { $doorState = $_POST['doorState']; }
        if(isset($_POST['currentFloor'])) { $currentFloor = (int)$_POST['currentFloor']; }
        if(isset($_POST['requestedFloor'])) { $requestedFloor = (int)$_POST['requestedFloor']; }
        if(isset($_POST['otherInfo'])) { $otherInfo = $_POST['otherInfo']; }
        if(isset($_POST['MAC_address'])) { $macAddress = $_POST['MAC_address']; }

        echo "<h1>Welcome, " . $_SESSION['username'] . "</h1>";
        require '../elevatorNetworkForm.html';

        try {
            if(isset($_POST['insert'])) {
                echo "You pressed INSERT <br>";
                insert($path, $user, $password, $doorState, $currentFloor, $requestedFloor, $otherInfo, $macAddress);

            } elseif(isset($_POST['update'])) {
                echo "You pressed UPDATE <br>";
                update($path, $user, $password, $tablename, $nodeID, $doorState, $currentFloor, $requestedFloor, $otherInfo);

            } elseif(isset($_POST['delete'])) {
                echo 'You pressed DELETE <br>';
                delete($path, $user, $password, $tablename, $nodeID);
            }
        } catch (Throwable $e) {
            echo "<p>Database operation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        showtable($path, $user, $password, $tablename);
        echo "<p>Click <a href='logout.php'>here</a> to sign out</p>";
    } else {
        echo "<p>You are not authorized!</p>";
    }
?>
