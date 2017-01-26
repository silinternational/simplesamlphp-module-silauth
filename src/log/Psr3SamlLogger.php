<?php
namespace Sil\SilAuth\log;

use Psr\Log\LogLevel as PsrLogLevel;
use SimpleSAML_Logger;

/**
 * A minimalist wrapper library (for SimpleSAML_Logger) to make it PSR-3
 * compatible.
 */
class Psr3SamlLogger extends LoggerBase
{
    /**
     * Log a message.
     *
     * @param mixed $level One of the \Psr\Log\LogLevel::* constants.
     * @param string $message The message to log, possibly with {placeholder}s.
     * @param array $context An array of placeholder => value entries to insert
     *     into the message.
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        $messageWithContext = $this->interpolate($message, $context);
        switch ($level) {
            case PsrLogLevel::EMERGENCY:
                SimpleSAML_Logger::emergency($messageWithContext);
                break;
            case PsrLogLevel::ALERT:
                SimpleSAML_Logger::alert($messageWithContext);
                break;
            case PsrLogLevel::CRITICAL:
                SimpleSAML_Logger::critical($messageWithContext);
                break;
            case PsrLogLevel::ERROR:
                SimpleSAML_Logger::error($messageWithContext);
                break;
            case PsrLogLevel::WARNING:
                SimpleSAML_Logger::warning($messageWithContext);
                break;
            case PsrLogLevel::NOTICE:
                SimpleSAML_Logger::notice($messageWithContext);
                break;
            case PsrLogLevel::INFO:
                SimpleSAML_Logger::info($messageWithContext);
                break;
            case PsrLogLevel::DEBUG:
                SimpleSAML_Logger::debug($messageWithContext);
                break;
            default:
                throw new \Psr\Log\InvalidArgumentException(
                    'Unknown log level: ' . var_export($level, true),
                    1485455196
                );
                break;
        }
    }
}
