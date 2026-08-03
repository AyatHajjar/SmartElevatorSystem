<?php
require_once 'Node.php';
require_once 'InputHandlerInterface.php';

class FloorNode extends Node implements InputHandlerInterface {
    private bool $upRequested = false;
    private bool $downRequested = false;
    private int $minFloor;
    private int $maxFloor;
    private int $sabbathDwellMs;

    public function __construct(int $nodeId = 1, int $floorNumber = 3, int $sabbathDwellMs = 5000, int $minFloor = 1, int $maxFloor = 3) {
        parent::__construct($nodeId, $floorNumber);
        $this->sabbathDwellMs = $sabbathDwellMs;
        $this->minFloor = $minFloor;
        $this->maxFloor = $maxFloor;
    }

    public function handleInput(string $inputSignal): void {
        if ($inputSignal === 'UP') {
            $this->upRequested = true;
        } elseif ($inputSignal === 'DOWN') {
            $this->downRequested = true;
        }
    }

    public function isUpRequested(): bool {
        return $this->upRequested;
    }

    public function isDownRequested(): bool {
        return $this->downRequested;
    }

    public function getMinFloor(): int {
        return $this->minFloor;
    }

    public function getMaxFloor(): int {
        return $this->maxFloor;
    }

    public function getSabbathDwellMs(): int {
        return $this->sabbathDwellMs;
    }
}
