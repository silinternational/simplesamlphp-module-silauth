<?php
namespace Sil\SilAuth\auth;

use Psr\Log\LoggerInterface;

class IdBroker
{
    /** @var LoggerInterface */
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function getUserAttributesFor($username)
    {
        throw new \Exception('Not yet implemented.');
    }
}
