<?php
require_once 'oop/ElevatorExceptions.php';
require_once 'oop/ElevatorCar.php';

echo "<h2>ExceptionsTest</h2>";

try {
    echo "Attempting to send elevator to floor 4 (out of bounds)...<br>";
    $targetFloor = 4;
    if ($targetFloor > 3 || $targetFloor < 1) {
        throw new InvalidNodeInputException("Exception Caught: Floor {$targetFloor} is invalid! Max floor is 3.");
    }
} catch (InvalidNodeInputException $e) {
    echo "<p>[Caught Exception]: " . $e->getMessage() . "</p>";
}

echo "<hr>";

try {
    echo "Attempting communication with offline network module...<br>";
    $isNodeOnline = false; // Simulating connection failure
    if (!$isNodeOnline) {
        throw new CommunicationException("Exception Caught: No response from CAN-bus interface module.");
    }
} catch (CommunicationException $e) {
    echo "<p>[Caught Exception]: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3> All custom exceptions successfully tested and handled via try-catch blocks!</h3>";
?>
