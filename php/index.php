<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    function update_elevatorNetwork(int $node_ID, ?int $new_floor = null, ?int $requested_floor = null, ?string $door_state = null, ?string $other_info = null): void {
        $db1 = null;
        try {
            $db1 = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($node_ID <= 0) {
                throw new InvalidArgumentException("Error: Invalid node ID provided.");
            }

            if ($new_floor !== null && ($new_floor < 1 || $new_floor > 3)) {
                throw new InvalidArgumentException("Error: Floor number out of bounds (must be between 1 and 3).");
            }

            if ($door_state !== null && $door_state !== 'Open' && $door_state !== 'Closed') {
                throw new InvalidArgumentException("Error: Invalid door state value.");
            }

            $db1->beginTransaction();

            $stmtCurrent = $db1->prepare("SELECT currentFloor, requestedFloor, doorState, otherInfo, MAC_address FROM elevatorNetwork WHERE nodeID = :id FOR UPDATE");
            $stmtCurrent->execute([':id' => $node_ID]);
            $currentData = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

            if (!$currentData) {
                throw new Exception("Error: Node ID {$node_ID} not found in the database.");
            }

            $currFlr = $currentData['currentFloor'] ?? 1;
            $reqFlr = $currentData['requestedFloor'] ?? 1;
            $doorSt = $currentData['doorState'] ?? 'Closed';
            $otherIn = $currentData['otherInfo'] ?? 'System Initialization';
            $macAddr = $currentData['MAC_address'] ?? '00:00:00:00:00:00';

            $fields = [];
            $params = [':id' => $node_ID];

            if ($new_floor !== null) {
                $fields[] = "currentFloor = :floor";
                $params[':floor'] = $new_floor;
                $currFlr = $new_floor;
                $fields[] = "otherInfo = :clearedInfo";
                $params[':clearedInfo'] = 'Normal';
                $otherIn = 'Normal';
            }
            if ($requested_floor !== null) {
                $fields[] = "requestedFloor = :reqFloor";
                $params[':reqFloor'] = $requested_floor;
                $reqFlr = $requested_floor;
            }
            if ($door_state !== null) {
                $fields[] = "doorState = :doorState";
                $params[':doorState'] = $door_state;
                $doorSt = $door_state;
            }
            if ($other_info !== null) {
                $fields[] = "otherInfo = :otherInfo";
                $params[':otherInfo'] = $other_info;
                $otherIn = $other_info;
            }

            if (!empty($fields)) {
                $query = 'UPDATE elevatorNetwork SET ' . implode(', ', $fields) . ' WHERE nodeID = :id';
                $statement = $db1->prepare($query);
                foreach ($params as $key => $val) {
                    $statement->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $statement->execute();
            }

            $logQuery = 'INSERT INTO elevatorLogs (nodeID, date, time, currentFloor, requestedFloor, doorState, otherInfo, MAC_address)  
                         VALUES (:nodeID, CURDATE(), CURTIME(), :currFlr, :reqFlr, :doorSt, :otherIn, :macAddr)';
            $logStmt = $db1->prepare($logQuery);
            $logStmt->execute([
                ':nodeID' => $node_ID,
                ':currFlr' => $currFlr,
                ':reqFlr' => $reqFlr,
                ':doorSt' => $doorSt,
                ':otherIn' => $otherIn,
                ':macAddr' => $macAddr
            ]);

            $db1->commit();

        } catch (Exception $e) {
            if ($db1 && $db1->inTransaction()) {
                $db1->rollBack();
            }
            throw $e;
        }
    }

    function get_currentFloor(): int {
        $default_floor = 1; 
        try { 
            $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $rows = $db->query('SELECT currentFloor FROM elevatorNetwork LIMIT 1');
            foreach ($rows as $row) {
                return $row[0]; 
            }
            return $default_floor;
        } catch (PDOException $e) {
            return $default_floor;
        }
    }

    $curFlr = get_currentFloor();

    if (isset($_POST['newfloor'])) {
        try {
            $targetFloor = (int)$_POST['newfloor'];
            update_elevatorNetwork(1, new_floor: $targetFloor, requested_floor: $targetFloor); 
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
        header('Location: index.php');
        exit;
    } 

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        try {
            if ($action === 'Open' || $action === 'Closed') {
                update_elevatorNetwork(1, door_state: $action);
            } elseif ($action === 'Alarm') {
                update_elevatorNetwork(1, other_info: 'ALARM TRIGGERED');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
        header('Location: index.php');
        exit;
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>ESE Project VI Elevator</title>
        <link rel="stylesheet" href="../css/elevator.css">
    </head>
    <body>
        <div class="elevator-panel">
            <h1>Elevator Control</h1> 
            
            <div style="margin-bottom: 15px;">
                <a href="diagnostic.php" class="btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center; box-sizing: border-box; width: 100%; background-color: #6f42c1; border-color: #6f42c1; color: white;">View Diagnostic Report</a>
            </div>

            <hr />
            
            <h2>Current Floor</h2>
            <div class="floor-display" id="floorDisplay">
                Floor: <?php echo $curFlr; ?>
            </div>        
            
            <hr />
            
            <h2>Floor Control</h2>
            <form action="index.php" method="POST" class="floor-buttons">
                <button type="submit" name="newfloor" value="1" class="btn">Floor 1</button>
                <button type="submit" name="newfloor" value="2" class="btn">Floor 2</button>
                <button type="submit" name="newfloor" value="3" class="btn">Floor 3</button>
            </form>
            
            <hr />
            
            <form action="index.php" method="POST">
                <h2>Car Control</h2>
                <input type="number" name="newfloor" max="3" min="1" required value="<?php echo $curFlr; ?>" />
                <input type="submit" value="Go" class="btn"/>
            </form>

            <hr />

            <h2>Door Controls</h2>
            <form action="index.php" method="POST">
                <button type="submit" name="action" value="Open" class="btn">&lt;|&gt; Open</button>
                <button type="submit" name="action" value="Closed" class="btn">&gt;|&lt; Close</button>
                <button type="submit" name="action" value="Alarm" class="btn btn-danger">ALARM</button>
            </form>
        </div>

        <audio id="floorAudio" src="../audio/floor<?php echo $curFlr; ?>.mp3" preload="auto"></audio>

        <script>
            window.addEventListener('DOMContentLoaded', (event) => {
                const audio = document.getElementById('floorAudio');
                audio.play().catch(error => {
                    console.log("Audio autoplay restricted by browser policy until user interacts with the page.");
                });
            });
        </script>
    </body>
</html>
