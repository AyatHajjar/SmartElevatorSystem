<?php
// Custom Exception for out-of-range inputs or invalid nodes
class InvalidNodeInputException extends Exception {
    public function __construct(string $message = "Error: Invalid input or floor out of bounds.", int $code = 400, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

// Custom Exception for communication or database connection errors
class CommunicationException extends Exception {
    public function __construct(string $message = "Error: Communication failure with network node.", int $code = 503, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
