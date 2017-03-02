<?php
namespace Sil\SilAuth\log;

/**
 * A basic PSR-3 compliant logger that merely echoes logs to the console
 * (primarily intended for use in tests).
 */
class Psr3ConsoleLogger extends LoggerBase
{
    /**
     * Log a message.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        echo sprintf(
            'LOG: [%s] %s',
            $level,
            $this->interpolate($message, $context)
        ) . PHP_EOL;
    }
}
