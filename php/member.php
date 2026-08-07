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
    $stmt = $pdo->prepare("INSERT INTO elevatorNetwork (nodeName, nodeType, floorNumber, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['nodeName'], $_POST['nodeType'], $_POST['floorNumber'], $_POST['status']]);
    $message = 'Node added.';
}

if ($action === 'update') {
    $stmt = $pdo->prepare("UPDATE elevatorNetwork SET nodeName = ?, nodeType = ?, floorNumber = ?, status = ? WHERE nodeID = ?");
    $stmt->execute([$_POST['nodeName'], $_POST['nodeType'], $_POST['floorNumber'], $_POST['status'], $_POST['nodeID']]);
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
    Node Name: <input type="text" name="nodeName" required><br>
    Node Type: <input type="text" name="nodeType" required><br>
    Floor Number: <input type="number" name="floorNumber" required><br>
    Status:
    <select name="status">
        <option value="active">active</option>
        <option value="inactive">inactive</option>
    </select><br>
    <button type="submit">Add Node</button>
</form>

<h2>Elevator Network Nodes</h2>
<table border="1" cellpadding="5">
    <tr><th>ID</th><th>Name</th><th>Type</th><th>Floor</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($nodes as $node): ?>
    <tr>
        <td><?= $node['nodeID'] ?></td>
        <td><?= htmlspecialchars($node['nodeName']) ?></td>
        <td><?= htmlspecialchars($node['nodeType']) ?></td>
        <td><?= $node['floorNumber'] ?></td>
        <td><?= htmlspecialchars($node['status']) ?></td>
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
    Node Name: <input type="text" name="nodeName" value="<?= htmlspecialchars($editRow['nodeName']) ?>" required><br>
    Node Type: <input type="text" name="nodeType" value="<?= htmlspecialchars($editRow['nodeType']) ?>" required><br>
    Floor Number: <input type="number" name="floorNumber" value="<?= $editRow['floorNumber'] ?>" required><br>
    Status:
    <select name="status">
        <option value="active" <?= $editRow['status'] === 'active' ? 'selected' : '' ?>>active</option>
        <option value="inactive" <?= $editRow['status'] === 'inactive' ? 'selected' : '' ?>>inactive</option>
    </select><br>
    <button type="submit">Save Changes</button>
</form>
<?php endif; ?>

</body>
</html>
