<?php
    try {
        $db = new PDO('mysql:host=127.0.0.1;dbname=elevator', 'ese', 'ese');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Fetch full history logs ordered by newest first from elevatorLogs
        $query = 'SELECT logID, nodeID, date, time, currentFloor, requestedFloor, doorState, otherInfo, MAC_address FROM elevatorLogs ORDER BY logID DESC';
        $statement = $db->query($query);
        $records = $statement->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Elevator Diagnostic Report</title>
        <link rel="stylesheet" href="../css/elevator.css">
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                background-color: #f4f4f9; 
            }
            .report-container { 
                max-width: 1000px; 
                margin: auto; 
                background: white; 
                padding: 20px; 
                border-radius: 8px; 
                box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 20px; 
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 10px; 
                text-align: center; 
                font-size: 14px; 
            }
            th { 
                background-color: #007bff; 
                color: white; 
            }
            tr:nth-child(even) { 
                background-color: #f9f9f9; 
            }
            .back-btn { 
                display: inline-block; 
                margin-bottom: 15px; 
                padding: 8px 15px; 
                background: #6c757d; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
            }
            .back-btn:hover { 
                background: #5a6268; 
            }
        </style>
    </head>
    <body>
        <div class="report-container">
            <a href="index.php" class="back-btn">&larr; Back to Control Panel</a>
            <h2>Diagnostic Report</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Node ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Current Floor</th>
                        <th>Requested Floor</th>
                        <th>Door State</th>
                        <th>Other Info / Alarm</th>
                        <th>MAC Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['logID']); ?></td>
                                <td><?php echo htmlspecialchars($row['nodeID']); ?></td>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><?php echo htmlspecialchars($row['time']); ?></td>
                                <td><?php echo htmlspecialchars($row['currentFloor']); ?></td>
                                <td><?php echo htmlspecialchars($row['requestedFloor']); ?></td>
                                <td><?php echo htmlspecialchars($row['doorState']); ?></td>
                                <td><?php echo htmlspecialchars($row['otherInfo']); ?></td>
                                <td><?php echo htmlspecialchars($row['MAC_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No diagnostic logs found. Try interacting with the elevator panel.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
</html>
