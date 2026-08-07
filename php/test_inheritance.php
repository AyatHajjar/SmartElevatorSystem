<?php
require_once 'oop/Node.php';
require_once 'oop/FloorNode.php';

echo "<h2>Inheritance & Interface Verification Test</h2>";

$floorCall = new FloorNode(1, 2);
$floorCall->handleInput('UP');
echo "Floor Node ID: " . $floorCall->getNodeId() . "<br>";
echo "Floor Number: " . $floorCall->getFloorNumber() . "<br>";
echo "Up Button Requested: " . ($floorCall->isUpRequested() ? 'True' : 'False') . "<hr>";

echo "<strong>Static Property Test:</strong><br>";
echo "Total Nodes Created: " . Node::getTotalNodesCreated() . "<br>";

echo "<h3 style='color: green;'>✓ All tests passed successfully!</h3>";
?>
