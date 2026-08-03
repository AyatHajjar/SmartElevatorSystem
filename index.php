<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();
    
    define('SABBATH_DWELL_MS', 5000); // time spent at each floor before advancing
    define('MIN_FLOOR', 1);
    define('MAX_FLOOR', 3);
    define('REQUIRE_LOGIN_FOR_MAINTENANCE', false); // set back to true before real use

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

    function get_elevator_status(): array {
        $default_data = ['currentFloor' => 1, 'doorState' => 'Closed', 'otherInfo' => 'Normal'];
        try {
            $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $db->query('SELECT currentFloor, doorState, otherInfo FROM elevatorNetwork LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row : $default_data;
        } catch (PDOException $e) {
            return $default_data;
        }
    }

    function get_sabbathMode(): bool {
        try {
            $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $rows = $db->query('SELECT sabbathMode FROM elevatorNetwork LIMIT 1');
            foreach ($rows as $row) {
                return (bool)$row[0];
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    function set_sabbathMode(bool $enabled) {
        try {
            $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $statement = $db->prepare('UPDATE elevatorNetwork SET sabbathMode = :value');
            $statement->bindValue(':value', $enabled ? 1 : 0, PDO::PARAM_INT);
            $statement->execute();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    function get_maintenanceMode(): bool {
        try {
            $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $rows = $db->query('SELECT maintenanceMode FROM elevatorNetwork LIMIT 1');
            foreach ($rows as $row) {
                return (bool)$row[0];
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    function set_maintenanceMode(bool $enabled) {
        try {
            $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $statement = $db->prepare('UPDATE elevatorNetwork SET maintenanceMode = :value');
            $statement->bindValue(':value', $enabled ? 1 : 0, PDO::PARAM_INT);
            $statement->execute();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    $isLoggedIn = !empty($_SESSION['is_logged_in']);
    $maintenanceMode = get_maintenanceMode();
    $sabbathMode = get_sabbathMode();

    // Maintenance lock-out toggle
    if (isset($_POST['toggle_maintenance'])) {
        if (!REQUIRE_LOGIN_FOR_MAINTENANCE || $isLoggedIn) {
            $maintenanceMode = !$maintenanceMode;
            set_maintenanceMode($maintenanceMode);

            if ($maintenanceMode) {
                set_sabbathMode(false);
            }
        }
        header('Location: index.php');
        exit;
    }

    // Sabbath mode toggle
    if (isset($_POST['toggle_sabbath'])) {
        if (!$maintenanceMode) {
            set_sabbathMode(!$sabbathMode);
        }
        header('Location: index.php');
        exit;
    }

    // Floor changes via buttons or numeric input
    if (isset($_POST['newfloor'])) {
        if (!$maintenanceMode) {
            try {
                $targetFloor = (int)$_POST['newfloor'];
                update_elevatorNetwork(1, new_floor: $targetFloor, requested_floor: $targetFloor); 
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }
        header('Location: index.php');
        exit;
    } 

    // Actions for Door Open/Close and Alarm
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if (!$maintenanceMode || $action === 'Alarm') {
            try {
                if ($action === 'Open') {
                    update_elevatorNetwork(1, door_state: 'Open');
                } elseif ($action === 'Closed') {
                    update_elevatorNetwork(1, door_state: 'Closed');
                } elseif ($action === 'Alarm') {
                    update_elevatorNetwork(1, other_info: 'ALARM TRIGGERED');
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }
        header('Location: index.php');
        exit;
    }

    $elevatorData = get_elevator_status();
    $curFlr = $elevatorData['currentFloor'];
    $doorState = $elevatorData['doorState'];
    $otherInfo = $elevatorData['otherInfo'];
    $controlsLocked = $maintenanceMode || $sabbathMode;
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
            
            <h2>Current Status</h2>
            <div class="floor-display" id="floorDisplay">
                Floor: <?php echo $curFlr; ?>
                <?php if ($doorState === 'Open'): ?>
                    <br>Doors: <strong>Open</strong>
                <?php endif; ?>
                <?php if ($otherInfo === 'ALARM TRIGGERED'): ?>
                    <br><span style="color: red; font-weight: bold;">⚠️ ALARM ACTIVE</span>
                <?php endif; ?>
            </div>
            <hr />
            
            <h2>Floor Control</h2>
            <form action="index.php" method="POST" class="floor-buttons">
                <button type="submit" name="newfloor" value="1" class="btn <?php echo ($curFlr == 1) ? 'active' : ''; ?>" <?php echo $controlsLocked ? 'disabled' : ''; ?>>Floor 1</button>
                <button type="submit" name="newfloor" value="2" class="btn <?php echo ($curFlr == 2) ? 'active' : ''; ?>" <?php echo $controlsLocked ? 'disabled' : ''; ?>>Floor 2</button>
                <button type="submit" name="newfloor" value="3" class="btn <?php echo ($curFlr == 3) ? 'active' : ''; ?>" <?php echo $controlsLocked ? 'disabled' : ''; ?>>Floor 3</button>
            </form>
            
            <hr />
            
            <form action="index.php" method="POST">
                <h2>Car Control</h2>
                <input type="number" name="newfloor" max="3" min="1" required value="<?php echo $curFlr; ?>" <?php echo $controlsLocked ? 'disabled' : ''; ?> />
                <input type="submit" value="Go" class="btn" <?php echo $controlsLocked ? 'disabled' : ''; ?>/>
            </form>

            <hr />

            <h2>Door Controls</h2>
            <form action="index.php" method="POST">
                <button type="submit" name="action" value="Open" class="btn <?php echo ($doorState === 'Open') ? 'active' : ''; ?>" <?php echo $maintenanceMode ? 'disabled' : ''; ?>>&lt;|&gt; Open</button>
                <button type="submit" name="action" value="Closed" class="btn <?php echo ($doorState === 'Closed') ? 'active' : ''; ?>" <?php echo $maintenanceMode ? 'disabled' : ''; ?>>&gt;|&lt; Close</button>
                <button type="submit" name="action" value="Alarm" class="btn btn-danger">ALARM</button>
            </form>

            <hr />

            <h2>Special Modes</h2>
            <div class="mode-status">
                Sabbath Mode: <strong><?php echo $sabbathMode ? 'ON' : 'OFF'; ?></strong><br />
                Maintenance Lock-out: <strong><?php echo $maintenanceMode ? 'ON' : 'OFF'; ?></strong>
            </div>

            <form action="index.php" method="POST" class="mode-buttons">
                <button type="submit" name="toggle_sabbath" value="1"
                    class="btn <?php echo $sabbathMode ? 'active' : ''; ?>"
                    <?php echo $maintenanceMode ? 'disabled' : ''; ?>>
                    <?php echo $sabbathMode ? 'Disable' : 'Enable'; ?> Sabbath Mode
                </button>
            </form>

            <form action="index.php" method="POST" class="mode-buttons">
                <button type="submit" name="toggle_maintenance" value="1"
                    class="btn btn-warning <?php echo $maintenanceMode ? 'active' : ''; ?>"
                    <?php echo (REQUIRE_LOGIN_FOR_MAINTENANCE && !$isLoggedIn) ? 'disabled' : ''; ?>>
                    <?php echo $maintenanceMode ? 'Disable' : 'Enable'; ?> Maintenance Lock-out
                </button>
            </form>
            <?php if (REQUIRE_LOGIN_FOR_MAINTENANCE && !$isLoggedIn): ?>
                <p class="login-note">Login required to control maintenance lock-out.</p>
            <?php endif; ?>
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

        <?php if ($sabbathMode && !$maintenanceMode): ?>
        <script>
            (function () {
                var MIN_FLOOR = <?php echo MIN_FLOOR; ?>;
                var MAX_FLOOR = <?php echo MAX_FLOOR; ?>;
                var current = <?php echo (int)$curFlr; ?>;

                setTimeout(function () {
                    var next = current + 1;
                    if (next > MAX_FLOOR) {
                        next = MIN_FLOOR;
                    }

                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'index.php';

                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'newfloor';
                    input.value = next;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }, <?php echo SABBATH_DWELL_MS; ?>);
            })();
        </script>
        <?php endif; ?>
    </body>
</html>
