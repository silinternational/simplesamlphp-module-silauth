<?php
namespace Sil\SilAuth\log;

use Monolog\Handler\SyslogHandler;
use Monolog\Logger;

/**
 * A basic PSR-3 compliant logger that sends logs to syslog.
 */
class Psr3SyslogLogger extends LoggerBase
{
    private $logger;
    
    public function __construct($name = 'name', $ident = 'ident')
    {
        $this->logger = new Logger($name);
        $this->logger->pushHandler(new SyslogHandler(
            $ident,
            LOG_USER,
            Logger::WARNING
        ));
    }
    
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
        $this->logger->log($level, $message, $context);
    }
}
