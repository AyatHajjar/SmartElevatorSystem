<?php
    function update_elevatorNetwork(int $node_ID, int $new_floor = 1): int {
        try {
            $db1 = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
            $db1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = 'UPDATE elevatorNetwork 
                    SET currentFloor = :floor
                    WHERE nodeID = :id';
            $statement = $db1->prepare($query);
            $statement->bindValue(':floor', $new_floor, PDO::PARAM_INT);
            $statement->bindValue(':id', $node_ID, PDO::PARAM_INT);
            $statement->execute();    
            
            return $new_floor;
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
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
        update_elevatorNetwork(1, (int)$_POST['newfloor']); 
        header('Location: index.php');
        exit;
    } 

    if (isset($_POST['action'])) {
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
            
            <h2>Current Floor</h2>
            <div class="floor-display">
                Floor: <?php echo $curFlr; ?>
            </div>        
            
            <hr />
            
            <h2>Floor Control</h2>
            <form action="index.php" method="POST" class="floor-buttons">
                <button type="submit" name="newfloor" value="1" class="btn <?php echo ($curFlr == 1) ? 'active' : ''; ?>">Floor 1</button>
                <button type="submit" name="newfloor" value="2" class="btn <?php echo ($curFlr == 2) ? 'active' : ''; ?>">Floor 2</button>
                <button type="submit" name="newfloor" value="3" class="btn <?php echo ($curFlr == 3) ? 'active' : ''; ?>">Floor 3</button>
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

    </body>
</html>
