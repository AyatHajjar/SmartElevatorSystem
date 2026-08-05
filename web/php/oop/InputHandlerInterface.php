<?php
interface InputHandlerInterface {
    public function handleInput(string $inputSignal): void;
}
