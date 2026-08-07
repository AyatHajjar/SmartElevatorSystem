<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo '<p>Not authorized</p>';
    exit;
}

require_once __DIR__ . '/oop/DatabaseConnector.php';
$pdo = (new DatabaseConnector())->connect();

$message = '';
$action  = $_POST['action'] ?? '';

if ($action === 'insert') {
    $stmt = $pdo->prepare("INSERT INTO elevatorNetwork (currentFloor, requestedFloor, doorState, otherInfo, MAC_address, sabbathMode, maintenanceMode) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['currentFloor'],
        $_POST['requestedFloor'],
        $_POST['doorState'],
        $_POST['otherInfo'],
        $_POST['MAC_address'],
        $_POST['sabbathMode'] ?? 0,
        $_POST['maintenanceMode'] ?? 0,
    ]);
    $message = 'Node added.';
}

if ($action === 'update') {
    $stmt = $pdo->prepare("UPDATE elevatorNetwork SET currentFloor = ?, requestedFloor = ?, doorState = ?, otherInfo = ?, MAC_address = ?, sabbathMode = ?, maintenanceMode = ? WHERE nodeID = ?");
    $stmt->execute([
        $_POST['currentFloor'],
        $_POST['requestedFloor'],
        $_POST['doorState'],
        $_POST['otherInfo'],
        $_POST['MAC_address'],
        $_POST['sabbathMode'] ?? 0,
        $_POST['maintenanceMode'] ?? 0,
        $_POST['nodeID'],
    ]);
    $message = 'Node updated.';
}

if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM elevatorNetwork WHERE nodeID = ?");
    $stmt->execute([$_POST['nodeID']]);
    $message = 'Node deleted.';
}

$nodes = $pdo->query("SELECT * FROM elevatorNetwork ORDER BY nodeID")->fetchAll(PDO::FETCH_ASSOC);

$editRow = null;
foreach ($nodes as $node) {
    if (isset($_GET['edit']) && $node['nodeID'] == $_GET['edit']) {
        $editRow = $node;
    }
}
?>
<!DOCTYPE html>
<html>
<body>

<p>Members Only</p>
<p><a href="index.php">Elevator</a></p>
<p><a href="logout.php">logout</a></p>

<?php if ($message): ?>
<p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<h2>Add New Node</h2>
<form method="post" action="member.php">
    <input type="hidden" name="action" value="insert">
    Current Floor: <input type="number" name="currentFloor" required><br>
    Requested Floor: <input type="number" name="requestedFloor" required><br>
    Door State:
    <select name="doorState">
        <option value="Closed">Closed</option>
        <option value="Open">Open</option>
    </select><br>
    Other Info: <input type="text" name="otherInfo"><br>
    MAC Address: <input type="text" name="MAC_address" required><br>
    Sabbath Mode:
    <select name="sabbathMode">
        <option value="0">Off</option>
        <option value="1">On</option>
    </select><br>
    Maintenance Mode:
    <select name="maintenanceMode">
        <option value="0">Off</option>
        <option value="1">On</option>
    </select><br>
    <button type="submit">Add Node</button>
</form>

<h2>Elevator Network Nodes</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th><th>Current Floor</th><th>Requested Floor</th><th>Door</th><th>Other Info</th><th>MAC Address</th><th>Sabbath</th><th>Maintenance</th><th>Actions</th>
    </tr>
<?php foreach ($nodes as $node): ?>
    <tr>
        <td><?= $node['nodeID'] ?></td>
        <td><?= $node['currentFloor'] ?></td>
        <td><?= $node['requestedFloor'] ?></td>
        <td><?= htmlspecialchars($node['doorState']) ?></td>
        <td><?= htmlspecialchars($node['otherInfo']) ?></td>
        <td><?= htmlspecialchars($node['MAC_address']) ?></td>
        <td><?= $node['sabbathMode'] ? 'On' : 'Off' ?></td>
        <td><?= $node['maintenanceMode'] ? 'On' : 'Off' ?></td>
        <td>
            <a href="member.php?edit=<?= $node['nodeID'] ?>">Edit</a>
            <form method="post" action="member.php" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="nodeID" value="<?= $node['nodeID'] ?>">
                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
</table>

<?php if ($editRow): ?>
<h2>Edit Node</h2>
<form method="post" action="member.php">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="nodeID" value="<?= $editRow['nodeID'] ?>">
    Current Floor: <input type="number" name="currentFloor" value="<?= $editRow['currentFloor'] ?>" required><br>
    Requested Floor: <input type="number" name="requestedFloor" value="<?= $editRow['requestedFloor'] ?>" required><br>
    Door State:
    <select name="doorState">
        <option value="Closed" <?= $editRow['doorState'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
        <option value="Open" <?= $editRow['doorState'] === 'Open' ? 'selected' : '' ?>>Open</option>
    </select><br>
    Other Info: <input type="text" name="otherInfo" value="<?= htmlspecialchars($editRow['otherInfo']) ?>"><br>
    MAC Address: <input type="text" name="MAC_address" value="<?= htmlspecialchars($editRow['MAC_address']) ?>" required><br>
    Sabbath Mode:
    <select name="sabbathMode">
        <option value="0" <?= !$editRow['sabbathMode'] ? 'selected' : '' ?>>Off</option>
        <option value="1" <?= $editRow['sabbathMode'] ? 'selected' : '' ?>>On</option>
    </select><br>
    Maintenance Mode:
    <select name="maintenanceMode">
        <option value="0" <?= !$editRow['maintenanceMode'] ? 'selected' : '' ?>>Off</option>
        <option value="1" <?= $editRow['maintenanceMode'] ? 'selected' : '' ?>>On</option>
    </select><br>
    <button type="submit">Save Changes</button>
</form>
<?php endif; ?>

</body>
</html>
