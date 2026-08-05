<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();

    // Include OOP structural classes from the 'oop' folder
    require_once 'oop/DatabaseConnector.php';
    require_once 'oop/ElevatorCar.php';
    require_once 'oop/FloorNode.php';

    define('REQUIRE_LOGIN_FOR_MAINTENANCE', false); // set back to true before real use

    // Initialize dependencies
    $dbConnector = new DatabaseConnector();
    $pdo = $dbConnector->connect();
    
    $elevator = new ElevatorCar($pdo, 1);
    $floorNodeConfig = new FloorNode(1, 3, 5000);

    $isLoggedIn = !empty($_SESSION['is_logged_in']);
    $maintenanceMode = $elevator->getMaintenanceMode();
    $sabbathMode = $elevator->getSabbathMode();

    // Maintenance lock-out toggle
    if (isset($_POST['toggle_maintenance'])) {
        if (!REQUIRE_LOGIN_FOR_MAINTENANCE || $isLoggedIn) {
            $maintenanceMode = !$maintenanceMode;
            $elevator->setMaintenanceMode($maintenanceMode);

            if ($maintenanceMode) {
                $elevator->setSabbathMode(false);
            }
        }
        header('Location: index.php');
        exit;
    }

    // Sabbath mode toggle
    if (isset($_POST['toggle_sabbath'])) {
        if (!$maintenanceMode) {
            $elevator->setSabbathMode(!$sabbathMode);
        }
        header('Location: index.php');
        exit;
    }

    // Floor changes via buttons or numeric input
    if (isset($_POST['newfloor'])) {
        if (!$maintenanceMode) {
            try {
                $targetFloor = (int)$_POST['newfloor'];
                $elevator->updateNetwork(new_floor: $targetFloor, requested_floor: $targetFloor); 
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
                    $elevator->updateNetwork(door_state: 'Open');
                } elseif ($action === 'Closed') {
                    $elevator->updateNetwork(door_state: 'Closed');
                } elseif ($action === 'Alarm') {
                    $elevator->updateNetwork(other_info: 'ALARM TRIGGERED');
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }
        header('Location: index.php');
        exit;
    }

    // Default fallback values in case of communication failure
    $curFlr = 1;
    $doorState = 'Closed';
    $otherInfo = '';
    
    try {
        $elevatorData = $elevator->getStatus();
        $curFlr = $elevatorData['currentFloor'] ?? 1;
        $doorState = $elevatorData['doorState'] ?? 'Closed';
        $otherInfo = $elevatorData['otherInfo'] ?? '';
    } catch (Exception $e) {
        error_log($e->getMessage());
        $otherInfo = 'COMMUNICATION ERROR';
    }

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
                <?php elseif ($otherInfo === 'COMMUNICATION ERROR'): ?>
                    <br><span style="color: red; font-weight: bold;">⚠️ COMMUNICATION ERROR</span>
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
                <input type="number" name="newfloor" max="<?php echo $floorNodeConfig->getMaxFloor(); ?>" min="<?php echo $floorNodeConfig->getMinFloor(); ?>" required value="<?php echo $curFlr; ?>" <?php echo $controlsLocked ? 'disabled' : ''; ?> />
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
                var MIN_FLOOR = <?php echo $floorNodeConfig->getMinFloor(); ?>;
                var MAX_FLOOR = <?php echo $floorNodeConfig->getMaxFloor(); ?>;
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
                }, <?php echo $floorNodeConfig->getSabbathDwellMs(); ?>);
            })();
        </script>
        <?php endif; ?>
    </body>
</html>
