<?php

namespace Core;

use Core\Exceptions\ValidationException;

/**
 * Error and exception handler
 */
class Error
{

    /**
     * Error handler. Convert all errors to Exceptions by throwing an ErrorException.
     */
    public static function errorHandler(int $level, string $message, string $file, int $line): void
    {
        if (error_reporting() !== 0) {  // to keep the @ operator working
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Exception handler.
     */
    public static function exceptionHandler(\Exception|\Error $exception): void
    {
        if ($exception instanceof ValidationException) {
            $message = $exception->getErrors();
        } elseif ($exception instanceof \UnexpectedValueException) {
            $message = [$exception->getErrors()];
        } else {
            $message = [
                "Uncaught exception: '". \get_class($exception). "' with message '{$exception->getMessage()}'",
                "Stack trace: " . $exception->getTraceAsString(),
                "Thrown in '{$exception->getFile()}' on line {$exception->getLine()}"
            ];
        }

        echo json_encode($message, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
