<?php
class Node {
    protected int $nodeId;
    protected int $floorNumber;
    protected static int $totalNodesCreated = 0;

    public function __construct(int $nodeId, int $floorNumber) {
        $this->nodeId = $nodeId;
        $this->floorNumber = $floorNumber;
        self::$totalNodesCreated++;
    }

    public function getNodeId(): int {
        return $this->nodeId;
    }

    public function getFloorNumber(): int {
        return $this->floorNumber;
    }
    
    public static function getTotalNodesCreated(): int {
        return self::$totalNodesCreated;
    }
}
