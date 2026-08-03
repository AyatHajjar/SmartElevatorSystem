<?php
class Node {
    protected int $nodeId;
    protected int $floorNumber;

    public function __construct(int $nodeId, int $floorNumber) {
        $this->nodeId = $nodeId;
        $this->floorNumber = $floorNumber;
    }

    public function getNodeId(): int {
        return $this->nodeId;
    }

    public function getFloorNumber(): int {
        return $this->floorNumber;
    }
}
