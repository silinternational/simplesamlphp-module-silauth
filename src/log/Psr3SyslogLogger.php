<?php
namespace Sil\SilAuth\log;

use Psr\Log\LogLevel as PsrLogLevel;

/**
 * A basic PSR-3 compliant logger that sends logs to syslog.
 */
class Psr3SyslogLogger extends LoggerBase
{
    protected $openlogIdent;
    protected $openlogOptions = LOG_CONS | LOG_NDELAY | LOG_PID | LOG_PERROR;
    protected $openlogFacility = LOG_USER;
    
    public function __construct($openlogIdent = '')
    {
        $this->openlogIdent = $openlogIdent;
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
        $messageWithContext = $this->interpolate($message, $context);
        switch ($level) {
            case PsrLogLevel::EMERGENCY:
                $this->sendToSyslog(LOG_EMERG, $messageWithContext);
                break;
            case PsrLogLevel::ALERT:
                $this->sendToSyslog(LOG_ALERT, $messageWithContext);
                break;
            case PsrLogLevel::CRITICAL:
                $this->sendToSyslog(LOG_CRIT, $messageWithContext);
                break;
            case PsrLogLevel::ERROR:
                $this->sendToSyslog(LOG_ERR, $messageWithContext);
                break;
            case PsrLogLevel::WARNING:
                $this->sendToSyslog(LOG_WARNING, $messageWithContext);
                break;
            case PsrLogLevel::NOTICE:
                $this->sendToSyslog(LOG_NOTICE, $messageWithContext);
                break;
            case PsrLogLevel::INFO:
                $this->sendToSyslog(LOG_INFO, $messageWithContext);
                break;
            case PsrLogLevel::DEBUG:
                $this->sendToSyslog(LOG_DEBUG, $messageWithContext);
                break;
            default:
                throw new \Psr\Log\InvalidArgumentException(
                    'Unknown log level: ' . var_export($level, true),
                    1485467257
                );
                break;
        }
    }
    
    /**
     * 
     * @param int $phpLogLevel See syslog()'s $priority parameter.
     * @param string $message The message to send to syslog.
     */
    protected function sendToSyslog($phpLogLevel, $message)
    {
        $openedLog = openlog(
            $this->openlogIdent,
            $this->openlogOptions,
            $this->openlogFacility
        );
        if ($openedLog) {
            syslog($phpLogLevel, $message);
        }
        closelog();
    }
}
