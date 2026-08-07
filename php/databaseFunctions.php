<?php
function connect(string $path, string $user, string $password) {
    $db = new PDO($path, $user, $password);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

function insert($path, $user, $password, $doorState, $currentFloor, $requestedFloor, $otherInfo, $macAddress) {
    $db = connect($path, $user, $password);
    $query = 'INSERT INTO elevatorNetwork(doorState, currentFloor, requestedFloor, otherInfo, MAC_address) VALUES
    (:doorState, :currentFloor, :requestedFloor, :otherInfo, :macAddress)';
    $params = [
        'doorState' => $doorState,
        'currentFloor' => $currentFloor,
        'requestedFloor' => $requestedFloor,
        'otherInfo' => $otherInfo,
        'macAddress' => $macAddress
    ];
    $statement = $db->prepare($query);
    $statement->execute($params);
}

function showtable(string $path, string $user, string $password, $tablename) {
    echo "<h3>Content of ElevatorNetwork table</h3>";
    $db = connect($path, $user, $password);
    $query = "SELECT * FROM $tablename ORDER BY nodeID";
    $rows = $db->query($query);
    echo "NODEID|CURRENTFLOOR|REQUESTEDFLOOR|DOORSTATE|OTHERINFO|MAC ADDRESS <br>";
    foreach ($rows as $row) {
        echo $row['nodeID'] . " | " . $row['currentFloor'] . " | " . $row['requestedFloor'] . " | "
             . $row['doorState'] . " | " . $row['otherInfo'] . " | " . $row['MAC_address'] . "<br>";
    }
}

function update(string $path, string $user, string $password, string $tablename, int $node_ID, string $new_doorState, int $new_currentFloor,
                int $new_requestedFloor, string $new_otherInfo) : void {
    $db = connect($path, $user, $password);
    $query = 'UPDATE ' . $tablename . ' SET doorState = :doorState, currentFloor = :curFloor, requestedFloor = :rqFloor, otherInfo = :oInfo
             WHERE nodeID = :id';
    $statement = $db->prepare($query);
    $statement->bindValue('doorState', $new_doorState);
    $statement->bindValue('curFloor', $new_currentFloor);
    $statement->bindValue('rqFloor', $new_requestedFloor);
    $statement->bindValue('oInfo', $new_otherInfo);
    $statement->bindValue('id', $node_ID);
    $statement->execute();
}

function delete(string $path, string $user, string $password, string $tablename, int $node_ID) : void {
    $db = connect($path, $user, $password);
    $query = 'DELETE FROM ' . $tablename . ' WHERE nodeID = :id';
    $statement = $db->prepare($query);
    $statement->bindValue('id', $node_ID);
    $statement->execute();
}
?>

