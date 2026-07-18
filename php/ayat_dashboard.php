<?php


require_once "db_connect.php";


/*
|--------------------------------------------------------------------------
| AJAX: Simulate arrival during Sabbath Mode
|--------------------------------------------------------------------------
| This part is called automatically by JavaScript.
| It updates the current floor and stores the arrival in the database.
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["ajax_sabbath_floor"])
) {
    header("Content-Type: application/json");

    $floorTo = (int) $_POST["ajax_sabbath_floor"];

    if ($floorTo < 1 || $floorTo > 3) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid floor"
        ]);
        exit;
    }

    $statusCheck = $pdo->query("
        SELECT current_floor, sabbath_mode, maintenance_lockout
        FROM elevator_status
        WHERE id = 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (
        !$statusCheck ||
        (int) $statusCheck["sabbath_mode"] !== 1 ||
        (int) $statusCheck["maintenance_lockout"] === 1
    ) {
        echo json_encode([
            "success" => false,
            "message" => "Sabbath Mode is not active"
        ]);
        exit;
    }

    $floorFrom = $statusCheck["current_floor"] ?? null;


    /*
    Update elevator state to show arrival.
    */

    $stmt = $pdo->prepare("
        UPDATE elevator_status
        SET current_floor = :current_floor,
            target_floor = :target_floor,
            door_status = 'Open',
            elevator_status = 'Idle',
            last_command = :last_command
        WHERE id = 1
    ");

    $stmt->execute([
        ":current_floor" => $floorTo,
        ":target_floor" => $floorTo,
        ":last_command" => "Sabbath Mode arrived at Floor " . $floorTo
    ]);


    /*
    Store the automatic arrival in elevator_logs.
    */

    $log = $pdo->prepare("
        INSERT INTO elevator_logs
        (
            action_type,
            floor_from,
            floor_to,
            door_status,
            elevator_status,
            message
        )
        VALUES
        (
            'Sabbath Floor',
            :floor_from,
            :floor_to,
            'Open',
            'Idle',
            :message
        )
    ");

    $log->execute([
        ":floor_from" => $floorFrom,
        ":floor_to" => $floorTo,
        ":message" => "Automatic stop at Floor " . $floorTo
    ]);


    echo json_encode([
        "success" => true,
        "floor" => $floorTo
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Process normal form submissions
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /*
    |--------------------------------------------------------------------------
    | Enable Sabbath Mode
    |--------------------------------------------------------------------------
    */

    if (isset($_POST["sabbath_on"])) {

        $stmt = $pdo->prepare("
            UPDATE elevator_status
            SET sabbath_mode = 1,
                door_status = 'Closed',
                elevator_status = 'Idle',
                last_command = 'Sabbath Mode Enabled'
            WHERE id = 1
        ");

        $stmt->execute();


        $log = $pdo->prepare("
            INSERT INTO elevator_logs
            (
                action_type,
                door_status,
                elevator_status,
                message
            )
            VALUES
            (
                'Sabbath Mode',
                'Closed',
                'Idle',
                'Sabbath Mode Enabled'
            )
        ");

        $log->execute();
    }


    /*
    |--------------------------------------------------------------------------
    | Disable Sabbath Mode
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST["sabbath_off"])) {

        $stmt = $pdo->prepare("
            UPDATE elevator_status
            SET sabbath_mode = 0,
                door_status = 'Closed',
                elevator_status = 'Idle',
                last_command = 'Sabbath Mode Disabled'
            WHERE id = 1
        ");

        $stmt->execute();


        $log = $pdo->prepare("
            INSERT INTO elevator_logs
            (
                action_type,
                door_status,
                elevator_status,
                message
            )
            VALUES
            (
                'Sabbath Mode',
                'Closed',
                'Idle',
                'Sabbath Mode Disabled'
            )
        ");

        $log->execute();
    }


    /*
    |--------------------------------------------------------------------------
    | Enable Maintenance Lock-out
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST["maintenance_on"])) {

        $stmt = $pdo->prepare("
            UPDATE elevator_status
            SET maintenance_lockout = 1,
                sabbath_mode = 0,
                door_status = 'Closed',
                elevator_status = 'Offline',
                last_command = 'Maintenance Lock-out Enabled'
            WHERE id = 1
        ");

        $stmt->execute();

        $log = $pdo->prepare("
            INSERT INTO elevator_logs
            (
                action_type,
                door_status,
                elevator_status,
                message
            )
            VALUES
            (
                'Maintenance',
                'Closed',
                'Offline',
                'Maintenance Lock-out Enabled'
            )
        ");

        $log->execute();
    }


    /*
    |--------------------------------------------------------------------------
    | Disable Maintenance Lock-out
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST["maintenance_off"])) {

        $stmt = $pdo->prepare("
            UPDATE elevator_status
            SET maintenance_lockout = 0,
                door_status = 'Closed',
                elevator_status = 'Idle',
                last_command = 'Maintenance Lock-out Disabled'
            WHERE id = 1
        ");

        $stmt->execute();

        $log = $pdo->prepare("
            INSERT INTO elevator_logs
            (
                action_type,
                door_status,
                elevator_status,
                message
            )
            VALUES
            (
                'Maintenance',
                'Closed',
                'Idle',
                'Maintenance Lock-out Disabled'
            )
        ");

        $log->execute();
    }


    /*
    |--------------------------------------------------------------------------
    | Manual Floor Request
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST["newfloor"])) {

        $floorTo = (int) $_POST["newfloor"];


        /*
        Check Sabbath Mode before accepting a manual request.
        */

        $modeStatus = $pdo->query("
            SELECT current_floor, sabbath_mode, maintenance_lockout
            FROM elevator_status
            WHERE id = 1
        ")->fetch(PDO::FETCH_ASSOC);


        $sabbathModeActive =
            isset($modeStatus["sabbath_mode"]) &&
            (int) $modeStatus["sabbath_mode"] === 1;

        $maintenanceActive =
            isset($modeStatus["maintenance_lockout"]) &&
            (int) $modeStatus["maintenance_lockout"] === 1;


        if (
            !$sabbathModeActive &&
            !$maintenanceActive &&
            $floorTo >= 1 &&
            $floorTo <= 3
        ) {

            $floorFrom =
                $modeStatus["current_floor"] ?? null;


            /*
            Update target and elevator state.
            */

            $stmt = $pdo->prepare("
                UPDATE elevator_status
                SET target_floor = :target_floor,
                    door_status = 'Closed',
                    elevator_status = 'Moving',
                    last_command = :last_command
                WHERE id = 1
            ");

            $stmt->execute([
                ":target_floor" => $floorTo,
                ":last_command" => "Go to Floor " . $floorTo
            ]);


            /*
            Store the floor request.
            */

            $log = $pdo->prepare("
                INSERT INTO elevator_logs
                (
                    action_type,
                    floor_from,
                    floor_to,
                    door_status,
                    elevator_status,
                    message
                )
                VALUES
                (
                    'Go To Floor',
                    :floor_from,
                    :floor_to,
                    'Closed',
                    'Moving',
                    :message
                )
            ");

            $log->execute([
                ":floor_from" => $floorFrom,
                ":floor_to" => $floorTo,
                ":message" => "Requested elevator to Floor " . $floorTo
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Door and Alarm Controls
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST["action"])) {

        $action = $_POST["action"];

        $modeStatus = $pdo->query("
            SELECT maintenance_lockout
            FROM elevator_status
            WHERE id = 1
        ")->fetch(PDO::FETCH_ASSOC);

        $maintenanceActive =
            isset($modeStatus["maintenance_lockout"]) &&
            (int) $modeStatus["maintenance_lockout"] === 1;

        if ($maintenanceActive && $action !== "Alarm") {
            header("Location: ayat_dashboard.php");
            exit;
        }


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
            (
                action_type,
                door_status,
                elevator_status,
                message
            )
            VALUES
            (
                :action_type,
                :door_status,
                :elevator_status,
                :message
            )
        ");

        $log->execute([
            ":action_type" => $action,
            ":door_status" => $doorStatus,
            ":elevator_status" => $elevatorStatus,
            ":message" => $message
        ]);
    }


    /*
    Prevent duplicate form submission after refresh.
    */

    header("Location: ayat_dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Read Current Elevator Status
|--------------------------------------------------------------------------
*/

$status = $pdo->query("
    SELECT *
    FROM elevator_status
    WHERE id = 1
")->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Read Recent Activity
|--------------------------------------------------------------------------
*/

$logs = $pdo->query("
    SELECT *
    FROM elevator_logs
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Prepare GUI Values
|--------------------------------------------------------------------------
*/

$doorIcon =
    ($status["door_status"] === "Open")
    ? "🔓"
    : "🔒";


$statusIcon = "🟢";

if ($status["elevator_status"] === "Moving") {

    $statusIcon = "🔵";

} elseif ($status["elevator_status"] === "Alarm") {

    $statusIcon = "🔴";

} elseif ($status["elevator_status"] === "Offline") {

    $statusIcon = "⚫";
}


$sabbathModeActive =
    isset($status["sabbath_mode"]) &&
    (int) $status["sabbath_mode"] === 1;


$sabbathModeText =
    $sabbathModeActive
    ? "ON"
    : "OFF";


$sabbathModeIcon =
    $sabbathModeActive
    ? "🟣"
    : "⚪";


$maintenanceActive =
    isset($status["maintenance_lockout"]) &&
    (int) $status["maintenance_lockout"] === 1;

$maintenanceText =
    $maintenanceActive
    ? "ON"
    : "OFF";

$maintenanceIcon =
    $maintenanceActive
    ? "🔴"
    : "⚪";

$manualControlsDisabled =
    $sabbathModeActive || $maintenanceActive;


$currentFloor =
    isset($status["current_floor"])
    ? (int) $status["current_floor"]
    : 1;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Ayat Elevator Dashboard
    </title>

    <link
        rel="stylesheet"
        href="../css/ayat_dashboard.css"
    >


    <script>

        /*
        Play the recorded announcement.
        */

        function announceFloor(floor) {

            const audio = new Audio(
                "../audio/floor" + floor + ".mp3"
            );

            audio.volume = 1;

            audio.play().catch(error => {

                console.log(
                    "Audio error:",
                    error
                );

            });
        }


        /*
        Manual floor button:
        1. Stop the immediate form submission.
        2. Play the sound.
        3. Submit using requestSubmit(button).

        requestSubmit(button) is important because it sends
        the button name and value to PHP.
        */

        function submitFloorRequest(event, button, floor) {

            event.preventDefault();

            announceFloor(floor);

            setTimeout(function () {

                button.form.requestSubmit(button);

            }, 1500);
        }


        /*
        Simulated Sabbath Mode cycle.

        While the mode is ON:
        Floor 1 -> Floor 2 -> Floor 3 -> Floor 1
        */

        const sabbathModeActive =
            <?php echo $sabbathModeActive ? "true" : "false"; ?>;

        const maintenanceActive =
            <?php echo $maintenanceActive ? "true" : "false"; ?>;

        const currentFloor =
            <?php echo $currentFloor; ?>;


        if (sabbathModeActive && !maintenanceActive) {

            const nextFloor =
                currentFloor >= 3
                ? 1
                : currentFloor + 1;


            /*
            Wait three seconds to simulate movement.
            */

            setTimeout(function () {

                const requestData =
                    new URLSearchParams();

                requestData.append(
                    "ajax_sabbath_floor",
                    nextFloor
                );


                fetch(
                    "ayat_dashboard.php",
                    {
                        method: "POST",
                        headers: {
                            "Content-Type":
                                "application/x-www-form-urlencoded"
                        },
                        body: requestData.toString()
                    }
                )
                .then(response => response.json())
                .then(result => {

                    if (result.success) {

                        announceFloor(result.floor);

                        /*
                        Reload after the announcement.
                        The next automatic cycle then begins.
                        */

                        setTimeout(function () {

                            window.location.reload();

                        }, 1600);
                    }
                })
                .catch(error => {

                    console.log(
                        "Sabbath Mode error:",
                        error
                    );

                });

            }, 3000);
        }

    </script>

</head>


<body>


<div class="dashboard">


    <header>

        <p class="system-label">

            Project VI • Smart Elevator System

        </p>

        <h1>

            Elevator Control Dashboard

        </h1>

    </header>



    <!-- Elevator Shaft -->

    <section class="shaft">


        <div class="shaft-row <?php
            echo ($status["current_floor"] == 3)
                ? "active-floor"
                : "";
        ?>">

            <span>
                Floor 3
            </span>

            <div class="elevator-car">

                <?php
                echo ($status["current_floor"] == 3)
                    ? "🛗"
                    : "";
                ?>

            </div>

        </div>



        <div class="shaft-row <?php
            echo ($status["current_floor"] == 2)
                ? "active-floor"
                : "";
        ?>">

            <span>
                Floor 2
            </span>

            <div class="elevator-car">

                <?php
                echo ($status["current_floor"] == 2)
                    ? "🛗"
                    : "";
                ?>

            </div>

        </div>



        <div class="shaft-row <?php
            echo ($status["current_floor"] == 1)
                ? "active-floor"
                : "";
        ?>">

            <span>
                Floor 1
            </span>

            <div class="elevator-car">

                <?php
                echo ($status["current_floor"] == 1)
                    ? "🛗"
                    : "";
                ?>

            </div>

        </div>


    </section>



    <!-- Current Floor -->

    <section class="floor-display">

        <span>
            Current Floor
        </span>

        <strong>

            <?php
            echo htmlspecialchars(
                $status["current_floor"]
            );
            ?>

        </strong>

    </section>



    <!-- Status Cards -->

    <section class="status-grid">


        <div class="status-card">

            <span>
                Target Floor
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $status["target_floor"] ?? "None"
                );
                ?>

            </strong>

        </div>



        <div class="status-card">

            <span>
                Door Status
            </span>

            <strong>

                <?php
                echo $doorIcon . " " .
                    htmlspecialchars(
                        $status["door_status"]
                    );
                ?>

            </strong>

        </div>



        <div class="status-card">

            <span>
                Elevator Status
            </span>

            <strong>

                <?php
                echo $statusIcon . " " .
                    htmlspecialchars(
                        $status["elevator_status"]
                    );
                ?>

            </strong>

        </div>



        <div class="status-card">

            <span>
                Last Command
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $status["last_command"]
                );
                ?>

            </strong>

        </div>



        <div class="status-card">

            <span>
                Sabbath Mode
            </span>

            <strong>

                <?php
                echo $sabbathModeIcon . " " .
                    htmlspecialchars(
                        $sabbathModeText
                    );
                ?>

            </strong>

        </div>


        <div class="status-card">

            <span>
                Maintenance Lock-out
            </span>

            <strong>

                <?php
                echo $maintenanceIcon . " " .
                    htmlspecialchars(
                        $maintenanceText
                    );
                ?>

            </strong>

        </div>


    </section>



    <!-- Elevator Controls -->

    <section class="controls">


        <h2>
            Go to Floor
        </h2>


        <?php if ($sabbathModeActive): ?>

            <p>

                Manual floor controls are disabled while
                Sabbath Mode is active.

            </p>

        <?php endif; ?>


        <?php if ($maintenanceActive): ?>

            <p>

                Floor and door controls are disabled during
                maintenance lock-out.

            </p>

        <?php endif; ?>


        <form
            method="POST"
            class="floor-buttons"
        >


            <button
                type="submit"
                name="newfloor"
                value="1"

                <?php
                echo $manualControlsDisabled
                    ? "disabled"
                    : "";
                ?>

                onclick="
                    submitFloorRequest(
                        event,
                        this,
                        1
                    );
                "
            >

                Floor 1

            </button>



            <button
                type="submit"
                name="newfloor"
                value="2"

                <?php
                echo $manualControlsDisabled
                    ? "disabled"
                    : "";
                ?>

                onclick="
                    submitFloorRequest(
                        event,
                        this,
                        2
                    );
                "
            >

                Floor 2

            </button>



            <button
                type="submit"
                name="newfloor"
                value="3"

                <?php
                echo $manualControlsDisabled
                    ? "disabled"
                    : "";
                ?>

                onclick="
                    submitFloorRequest(
                        event,
                        this,
                        3
                    );
                "
            >

                Floor 3

            </button>


        </form>



        <!-- Sabbath Mode Controls -->

        <h2>
            Sabbath Mode
        </h2>


        <form method="POST">


            <?php if (!$sabbathModeActive): ?>

                <button
                    type="submit"
                    name="sabbath_on"
                    value="1"
                    <?php
                    echo $maintenanceActive
                        ? "disabled"
                        : "";
                    ?>
                >

                    Enable Sabbath Mode

                </button>

            <?php else: ?>

                <button
                    type="submit"
                    name="sabbath_off"
                    value="0"
                >

                    Disable Sabbath Mode

                </button>

            <?php endif; ?>


        </form>



        <!-- Maintenance Lock-out Controls -->

        <h2>
            Maintenance Lock-out
        </h2>


        <form method="POST">


            <?php if (!$maintenanceActive): ?>

                <button
                    type="submit"
                    name="maintenance_on"
                    value="1"
                >

                    Enable Maintenance Lock-out

                </button>

            <?php else: ?>

                <button
                    type="submit"
                    name="maintenance_off"
                    value="0"
                >

                    Disable Maintenance Lock-out

                </button>

            <?php endif; ?>


        </form>



        <!-- Door Controls -->

        <h2>
            Door Controls
        </h2>


        <form method="POST">


            <div class="door-buttons">


                <button
                    type="submit"
                    name="action"
                    value="Open"
                    <?php
                    echo $maintenanceActive
                        ? "disabled"
                        : "";
                    ?>
                >

                    Open Door

                </button>


                <button
                    type="submit"
                    name="action"
                    value="Closed"
                    <?php
                    echo $maintenanceActive
                        ? "disabled"
                        : "";
                    ?>
                >

                    Close Door

                </button>


            </div>



            <button
                type="submit"
                name="action"
                value="Alarm"
                class="alarm-btn"
            >

                Emergency Alarm

            </button>


        </form>


    </section>



    <!-- Recent Activity -->

    <section class="activity-box">


        <h2>
            Recent Activity
        </h2>


        <?php if (count($logs) === 0): ?>

            <p class="empty-log">

                No activity recorded yet.

            </p>

        <?php endif; ?>


        <?php foreach ($logs as $log): ?>

            <div class="log-card">


                <strong>

                    <?php
                    echo htmlspecialchars(
                        $log["action_type"]
                    );
                    ?>

                </strong>


                <span>

                    <?php
                    echo htmlspecialchars(
                        $log["message"]
                    );
                    ?>

                </span>


                <small>

                    <?php
                    echo htmlspecialchars(
                        $log["created_at"]
                    );
                    ?>

                </small>


            </div>

        <?php endforeach; ?>


    </section>



    <footer>

        Last Updated:

        <?php
        echo htmlspecialchars(
            $status["updated_at"]
        );
        ?>

        <br>

        Conestoga College • Group 5

    </footer>


</div>


</body>

</html>