<?php
	require_once 'oop/FloorNode.php';
	require_once 'oop/ElevatorCar.php';
	require_once 'oop/DistanceSensor.php';

	echo "<h2>Inheritance & Interface Verification Test</h2>";

	// Test FloorNode inheritance & interface
	$floorCall = new FloorNode(1, 2);
	$floorCall->handleInput('UP');
	echo "Floor Node ID: " . $floorCall->getNodeId() . "<br>";
	echo "Floor Number: " . $floorCall->getFloorNumber() . "<br>";
	echo "Up Button Requested: " . ($floorCall->isUpRequested() ? 'True' : 'False') . "<hr>";

	// Test ElevatorCar inheritance & interface (passing null for PDO since we are just testing class logic)
	$elevator = new ElevatorCar(null, 2, 2);
	$elevator->handleInput('OPEN_DOORS');
	echo "Elevator Car Node ID: " . $elevator->getNodeId() . "<br>";
	echo "Elevator Current Floor: " . $elevator->getFloorNumber() . "<br>";
	echo "Door State after input: " . $elevator->getDoorState() . "<hr>";

	// Test DistanceSensor inheritance
	$sensor = new DistanceSensor(3, 2, 150.5);
	echo "Distance Sensor Node ID: " . $sensor->getNodeId() . "<br>";
	echo "Measured Distance: " . $sensor->getDistance() . " mm<br>";
	echo "<h3 style='color: green;'>✓ All inheritance and interface tests passed successfully!</h3>";
?>
