<?php
require_once 'Node.php';
require_once 'InputHandlerInterface.php';
require_once 'ElevatorExceptions.php'; // Ensure your custom exceptions are included

class ElevatorCar extends Node implements InputHandlerInterface {
    private PDO $db;
    private string $doorState;
    private string $motionState;

    public function __construct(PDO $db, int $nodeId = 1, int $currentFloor = 1, string $doorState = 'Closed', string $motionState = 'Idle') {
        parent::__construct($nodeId, $currentFloor);
        $this->db = $db;
        $this->doorState = $doorState;
        $this->motionState = $motionState;
    }

    public function handleInput(string $inputSignal): void {
        if ($inputSignal === 'OPEN_DOORS') {
            $this->doorState = 'Open';
        } elseif ($inputSignal === 'CLOSE_DOORS') {
            $this->doorState = 'Closed';
        } else {
            throw new InvalidNodeInputException("Error: Unrecognized input signal received: {$inputSignal}");
        }
    }

    public function getDoorState(): string {
        return $this->doorState;
    }

    public function getMotionState(): string {
        return $this->motionState;
    }

    public function getStatus(): array {
        $default_data = ['currentFloor' => 1, 'doorState' => 'Closed', 'otherInfo' => 'Normal'];
        try {
            $stmt = $this->db->prepare('SELECT currentFloor, doorState, otherInfo FROM elevatorNetwork WHERE nodeID = :id LIMIT 1');
            $stmt->execute([':id' => $this->nodeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row : $default_data;
        } catch (PDOException $e) {
            throw new CommunicationException("Error: Failed to fetch status from database network node.");
        }
    }

    public function updateNetwork(?int $new_floor = null, ?int $requested_floor = null, ?string $door_state = null, ?string $other_info = null): void {
        try {
            if ($this->nodeId <= 0) {
                throw new InvalidNodeInputException("Error: Invalid node ID provided.");
            }

            if ($new_floor !== null && ($new_floor < 1 || $new_floor > 3)) {
                throw new InvalidNodeInputException("Error: Floor number {$new_floor} out of bounds (1-3).");
            }

            if ($door_state !== null && $door_state !== 'Open' && $door_state !== 'Closed') {
                throw new InvalidNodeInputException("Error: Invalid door state value.");
            }

            $this->db->beginTransaction();

            $stmtCurrent = $this->db->prepare("SELECT currentFloor, requestedFloor, doorState, otherInfo, MAC_address FROM elevatorNetwork WHERE nodeID = :id FOR UPDATE");
            $stmtCurrent->execute([':id' => $this->nodeId]);
            $currentData = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

            if (!$currentData) {
                throw new CommunicationException("Error: Node ID {$this->nodeId} not found on the network.");
            }

            $currFlr = $new_floor ?? $currentData['currentFloor'] ?? 1;
            $reqFlr = $requested_floor ?? $currentData['requestedFloor'] ?? 1;
            $doorSt = $door_state ?? $currentData['doorState'] ?? 'Closed';
            $otherIn = $other_info ?? $currentData['otherInfo'] ?? 'Normal';
            $macAddr = $currentData['MAC_address'] ?? '00:00:00:00:00:00';

            $fields = [];
            $params = [':id' => $this->nodeId];

            if ($new_floor !== null) {
                $fields[] = "currentFloor = :floor";
                $params[':floor'] = $new_floor;
                $fields[] = "otherInfo = :clearedInfo";
                $params[':clearedInfo'] = 'Normal';
            }
            if ($requested_floor !== null) {
                $fields[] = "requestedFloor = :reqFloor";
                $params[':reqFloor'] = $requested_floor;
            }
            if ($door_state !== null) {
                $fields[] = "doorState = :doorState";
                $params[':doorState'] = $door_state;
            }
            if ($other_info !== null) {
                $fields[] = "otherInfo = :otherInfo";
                $params[':otherInfo'] = $other_info;
            }

            if (!empty($fields)) {
                $query = 'UPDATE elevatorNetwork SET ' . implode(', ', $fields) . ' WHERE nodeID = :id';
                $statement = $this->db->prepare($query);
                foreach ($params as $key => $val) {
                    $statement->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $statement->execute();
            }

            $logQuery = 'INSERT INTO elevatorLogs (nodeID, date, time, currentFloor, requestedFloor, doorState, otherInfo, MAC_address)  
                         VALUES (:nodeID, CURDATE(), CURTIME(), :currFlr, :reqFlr, :doorSt, :otherIn, :macAddr)';
            $logStmt = $this->db->prepare($logQuery);
            $logStmt->execute([
                ':nodeID' => $this->nodeId,
                ':currFlr' => $currFlr,
                ':reqFlr' => $reqFlr,
                ':doorSt' => $doorSt,
                ':otherIn' => $otherIn,
                ':macAddr' => $macAddr
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Re-throw custom exceptions or wrap standard ones
            if ($e instanceof InvalidNodeInputException || $e instanceof CommunicationException) {
                throw $e;
            }
            throw new CommunicationException("Database operation failed: " . $e->getMessage(), 500, $e);
        }
    }

    public function getSabbathMode(): bool {
        try {
            $stmt = $this->db->query('SELECT sabbathMode FROM elevatorNetwork LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_NUM);
            return $row ? (bool)$row[0] : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function setSabbathMode(bool $enabled): void {
        try {
            $statement = $this->db->prepare('UPDATE elevatorNetwork SET sabbathMode = :value');
            $statement->bindValue(':value', $enabled ? 1 : 0, PDO::PARAM_INT);
            $statement->execute();
        } catch (PDOException $e) {
            throw new CommunicationException("Database Error during Sabbath mode update: " . $e->getMessage());
        }
    }

    public function getMaintenanceMode(): bool {
        try {
            $stmt = $this->db->query('SELECT maintenanceMode FROM elevatorNetwork LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_NUM);
            return $row ? (bool)$row[0] : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function setMaintenanceMode(bool $enabled): void {
        try {
            $statement = $this->db->prepare('UPDATE elevatorNetwork SET maintenanceMode = :value');
            $statement->bindValue(':value', $enabled ? 1 : 0, PDO::PARAM_INT);
            $statement->execute();
        } catch (PDOException $e) {
            throw new CommunicationException("Database Error during Maintenance mode update: " . $e->getMessage());
        }
    }
}
