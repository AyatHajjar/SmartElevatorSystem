<?php
require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["newfloor"])) {
        $floorTo = (int)$_POST["newfloor"];

        if ($floorTo >= 1 && $floorTo <= 3) {
            $current = $pdo->query("SELECT current_floor FROM elevator_status WHERE id = 1")
                           ->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                UPDATE elevator_status
                SET target_floor = :target_floor,
                    elevator_status = 'Moving',
                    last_command = :last_command
                WHERE id = 1
            ");

            $stmt->execute([
                ":target_floor" => $floorTo,
                ":last_command" => "Go to Floor " . $floorTo
            ]);

            $log = $pdo->prepare("
                INSERT INTO elevator_logs
                (action_type, floor_from, floor_to, door_status, elevator_status, message)
                VALUES
                ('Go To Floor', :floor_from, :floor_to, 'Closed', 'Moving', :message)
            ");

            $log->execute([
                ":floor_from" => $current["current_floor"] ?? null,
                ":floor_to" => $floorTo,
                ":message" => "Requested elevator to Floor " . $floorTo
            ]);
        }
    }

    if (isset($_POST["action"])) {
        $action = $_POST["action"];

        if ($action === "Open") {
            $doorStatus = "Open";
            $elevatorStatus = "Idle";
            $message = "Door opened";
        } elseif ($action === "Closed") {
            $doorStatus = "Closed";
            $elevatorStatus = "Idle";
            $message = "Door closed";
        } elseif ($action === "Alarm") {
            $doorStatus = "Closed";
            $elevatorStatus = "Alarm";
            $message = "Emergency alarm activated";
        } else {
            $doorStatus = "Closed";
            $elevatorStatus = "Idle";
            $message = "Unknown action";
        }

        $stmt = $pdo->prepare("
            UPDATE elevator_status
            SET door_status = :door_status,
                elevator_status = :elevator_status,
                last_command = :last_command
            WHERE id = 1
        ");

        $stmt->execute([
            ":door_status" => $doorStatus,
            ":elevator_status" => $elevatorStatus,
            ":last_command" => $message
        ]);

        $log = $pdo->prepare("
            INSERT INTO elevator_logs
            (action_type, door_status, elevator_status, message)
            VALUES
            (:action_type, :door_status, :elevator_status, :message)
        ");

        $log->execute([
            ":action_type" => $action,
            ":door_status" => $doorStatus,
            ":elevator_status" => $elevatorStatus,
            ":message" => $message
        ]);
    }

    header("Location: ayat_dashboard.php");
    exit;
}

$status = $pdo->query("SELECT * FROM elevator_status WHERE id = 1")
              ->fetch(PDO::FETCH_ASSOC);

$logs = $pdo->query("SELECT * FROM elevator_logs ORDER BY created_at DESC LIMIT 6")
            ->fetchAll(PDO::FETCH_ASSOC);

$doorIcon = ($status["door_status"] === "Open") ? "🔓" : "🔒";
$statusIcon = "🟢";

if ($status["elevator_status"] === "Moving") {
    $statusIcon = "🔵";
} elseif ($status["elevator_status"] === "Alarm") {
    $statusIcon = "🔴";
} elseif ($status["elevator_status"] === "Offline") {
    $statusIcon = "⚫";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ayat Elevator Dashboard</title>
    <link rel="stylesheet" href="css/ayat_dashboard.css">
</head>
<body>

<div class="dashboard">

    <header>
        <p class="system-label">Project VI • Smart Elevator System</p>
        <h1>Elevator Control Dashboard</h1>
    </header>

    <section class="floor-display">
        <span>Current Floor</span>
        <strong><?php echo htmlspecialchars($status["current_floor"]); ?></strong>
    </section>

    <section class="status-grid">
        <div class="status-card">
            <span>Target Floor</span>
            <strong><?php echo htmlspecialchars($status["target_floor"] ?? "None"); ?></strong>
        </div>

        <div class="status-card">
            <span>Door Status</span>
            <strong><?php echo $doorIcon . " " . htmlspecialchars($status["door_status"]); ?></strong>
        </div>

        <div class="status-card">
            <span>Elevator Status</span>
            <strong><?php echo $statusIcon . " " . htmlspecialchars($status["elevator_status"]); ?></strong>
        </div>

        <div class="status-card">
            <span>Last Command</span>
            <strong><?php echo htmlspecialchars($status["last_command"]); ?></strong>
        </div>
    </section>

    <section class="controls">
        <h2>Go to Floor</h2>
        <form method="POST" class="floor-buttons">
            <button type="submit" name="newfloor" value="1">Floor 1</button>
            <button type="submit" name="newfloor" value="2">Floor 2</button>
            <button type="submit" name="newfloor" value="3">Floor 3</button>
        </form>

        <h2>Door Controls</h2>
        <form method="POST">
            <div class="door-buttons">
                <button type="submit" name="action" value="Open">Open Door</button>
                <button type="submit" name="action" value="Closed">Close Door</button>
            </div>

            <button type="submit" name="action" value="Alarm" class="alarm-btn">
                Emergency Alarm
            </button>
        </form>
    </section>

    <section class="activity-box">
        <h2>Recent Activity</h2>

        <?php if (count($logs) === 0): ?>
            <p class="empty-log">No activity recorded yet.</p>
        <?php endif; ?>

        <?php foreach ($logs as $log): ?>
            <div class="log-card">
                <strong><?php echo htmlspecialchars($log["action_type"]); ?></strong>
                <span><?php echo htmlspecialchars($log["message"]); ?></span>
                <small><?php echo htmlspecialchars($log["created_at"]); ?></small>
            </div>
        <?php endforeach; ?>
    </section>

    <footer>
        Last Updated: <?php echo htmlspecialchars($status["updated_at"]); ?>
        <br>
        Conestoga College • Group 5
    </footer>

</div>

</body>
</html>