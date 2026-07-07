<?php
    function update_elevatorNetwork(int $node_ID, int $new_floor = 1, string $status = 'Normal'): int {
        try {
            $db1 = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = 'UPDATE elevatorNetwork 
                    SET currentFloor = :floor, status = :status
                    WHERE nodeID = :id';
            $statement = $db1->prepare($query);
            $statement->bindValue('floor', $new_floor, PDO::PARAM_INT);
            $statement->bindValue('status', $status, PDO::PARAM_STR);
            $statement->bindValue('id', $node_ID, PDO::PARAM_INT);
            $statement->execute();    
            
            return $new_floor;
        } catch (PDOException $e) {
            return $new_floor; 
        }
    }

    function get_currentFloor(): int {
        $default_floor = 1; 
        
        try { 
            $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $rows = $db->query('SELECT currentFloor FROM elevatorNetwork LIMIT 1');
            $current_floor = $default_floor;
            
            foreach ($rows as $row) {
                $current_floor = $row[0];
            }
            return $current_floor;

        } catch (PDOException $e) {
            return $default_floor;
        }
    }
?>

<?php 
    $curFlr = get_currentFloor();

    if (isset($_POST['newfloor'])) {
        update_elevatorNetwork(1, (int)$_POST['newfloor'], 'Normal'); 
        header('Location: index.php');
        exit;
    } 

    if (isset($_POST['action'])) {
        $action = $_POST['action']; 
        update_elevatorNetwork(1, $curFlr, $action); 
        header('Location: index.php');
        exit;
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>ESE Project VI Elevator</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f0f2f5;
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .elevator-panel {
                background-color: #ffffff;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                width: 100%;
                max-width: 340px;
                text-align: center;
                box-sizing: border-box;
            }

            h1 {
                font-size: 1.4rem;
                color: #333;
                margin-top: 0;
                margin-bottom: 20px;
                border-bottom: 2px solid #007bff;
                padding-bottom: 10px;
            }
            h2 {
                font-size: 1.1rem;
                color: #555;
                margin: 15px 0;
                font-weight: 600;
            }

            .floor-display {
                background-color: lightgreen;
                color: black;
                font-family: 'Courier New', Courier, monospace;
                padding: 10px;
                border-radius: 6px;
                font-size: 1.3rem;
                margin-bottom: 20px;
                box-shadow: inset 0 0 5px rgba(0,0,0,0.5);
            }

            input[type="number"] {
                width: 60px;
                height: 38px;
                font-size: 1rem;
                text-align: center;
                border: 1px solid #ccc;
                border-radius: 4px;
                margin-right: 5px;
            }

            .btn { 
                height: 40px; 
                min-width: 80px; 
                margin: 5px;  
                border: none;
                border-radius: 4px;
                background-color: #007bff;
                color: white;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.2s;
            }
            .btn:hover {
                background-color: #0056b3;
            }
            .btn-danger { 
                background-color: #dc3545; 
                width: calc(100% - 10px); /* Makes the alarm button stretch full width */
                margin-top: 10px;
            }
            .btn-danger:hover {
                background-color: #bd2130;
            }

            hr {
                border: 0;
                border-top: 1px solid #eee;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>

        <div class="elevator-panel">
            <h1>Elevator Control</h1> 
            
            <div class="floor-display">
                Floor: <?php echo $curFlr; ?>
            </div>        
            
            <form action="index.php" method="POST">
                <h2>Go to Floor</h2>
                <input type="number" name="newfloor" max="3" min="1" required value="<?php echo $curFlr; ?>" />
                <input type="submit" value="Go" class="btn"/>
            </form>

            <hr />

            <h2>Door Controls</h2>
            <form action="index.php" method="POST">
                <button type="submit" name="action" value="Open" class="btn"><|> Open</button>
                <button type="submit" name="action" value="Closed" class="btn">>|< Close</button>
                <button type="submit" name="action" value="Alarm" class="btn btn-danger">ALARM</button>
            </form>
        </div>

    </body>
</html>
