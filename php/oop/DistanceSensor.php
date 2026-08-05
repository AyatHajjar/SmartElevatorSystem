<?php
require_once 'Node.php';

class DistanceSensor extends Node {
    private float $distanceMm;

    public function __construct(int $nodeId, int $floorNumber, float $distanceMm = 0.0) {
        parent::__construct($nodeId, $floorNumber);
        $this->distanceMm = $distanceMm;
    }

    public function updateDistance(float $distance): void {
        $this->distanceMm = $distance;
    }

    public function getDistance(): float {
        return $this->distanceMm;
    }
}
