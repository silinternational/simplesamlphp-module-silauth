<?php
namespace Sil\SilAuth\traits;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LoggerAwareTrait
{
    private $logger;
    
    public function init()
    {
        if (empty($this->logger)) {
            $this->logger = new NullLogger();
        }
        parent::init();
    }
    
    /**
     * Set a logger for this User instance to use.
     *
     * @param LoggerInterface $logger A PSR-3 compliant logger.
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
