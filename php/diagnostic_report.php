<?php

require_once 'db_connect.php';


// Count each action type
$sql = "
    SELECT action_type, COUNT(*) AS total
    FROM elevator_logs
    GROUP BY action_type
";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Count Go To Floor requests for each floor
$floorSql = "
    SELECT floor_to, COUNT(*) AS total
    FROM elevator_logs
    WHERE action_type = 'Go To Floor'
    AND floor_to IS NOT NULL
    GROUP BY floor_to
    ORDER BY floor_to
";

$floorStmt = $pdo->query($floorSql);
$floorResults = $floorStmt->fetchAll(PDO::FETCH_ASSOC);


// Get the latest 10 activities
$recentSql = "
    SELECT action_type, floor_to, created_at
    FROM elevator_logs
    ORDER BY created_at DESC
    LIMIT 10
";

$recentStmt = $pdo->query($recentSql);
$recentResults = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Diagnostic Report</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background-color: #f4f6f8;

            color: #222;

        }


        .report-container {

            width: 90%;

            max-width: 1100px;

            margin: 40px auto;

        }


        h1 {

            text-align: center;

            margin-bottom: 30px;

        }


        h2 {

            margin-top: 0;

        }


        .section {

            background-color: white;

            padding: 25px;

            margin-bottom: 25px;

            border-radius: 10px;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.08);

        }


        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(180px, 1fr));

            gap: 15px;

        }


        .summary-card {

            padding: 20px;

            background-color: #eef3f8;

            border-radius: 8px;

            text-align: center;

        }


        .summary-card h3 {

            margin: 0 0 10px;

        }


        .summary-card p {

            margin: 0;

            font-size: 28px;

            font-weight: bold;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 15px;

        }


        th,
        td {

            padding: 12px;

            border-bottom: 1px solid #ddd;

            text-align: left;

        }


        th {

            background-color: #eef3f8;

        }


        tr:hover {

            background-color: #f8f9fa;

        }

    </style>

</head>


<body>


<div class="report-container">


    <h1>Elevator Diagnostic Report</h1>



    <!-- ACTION SUMMARY -->

    <section class="section">


        <h2>Action Summary</h2>


        <div class="summary-grid">


            <?php foreach ($results as $row): ?>


                <div class="summary-card">


                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $row['action_type']
                        );
                        ?>

                    </h3>


                    <p>

                        <?php
                        echo (int) $row['total'];
                        ?>

                    </p>


                </div>


            <?php endforeach; ?>


        </div>


    </section>




    <!-- REQUESTS BY FLOOR -->

    <section class="section">


        <h2>Requests by Floor</h2>


        <div class="summary-grid">


            <?php foreach ($floorResults as $floor): ?>


                <div class="summary-card">


                    <h3>

                        Floor

                        <?php
                        echo htmlspecialchars(
                            $floor['floor_to']
                        );
                        ?>

                    </h3>


                    <p>

                        <?php
                        echo (int) $floor['total'];
                        ?>

                    </p>


                </div>


            <?php endforeach; ?>


        </div>


    </section>




    <!-- RECENT ACTIVITY -->

    <section class="section">


        <h2>Recent Activity</h2>


        <table>


            <thead>


                <tr>

                    <th>Action</th>

                    <th>Floor</th>

                    <th>Date and Time</th>

                </tr>


            </thead>


            <tbody>


                <?php foreach ($recentResults as $activity): ?>


                    <tr>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $activity['action_type']
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            if ($activity['floor_to'] !== null) {

                                echo htmlspecialchars(
                                    $activity['floor_to']
                                );

                            } else {

                                echo "-";

                            }

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $activity['created_at']
                            );

                            ?>

                        </td>


                    </tr>


                <?php endforeach; ?>


            </tbody>


        </table>


    </section>


</div>


</body>


</html>