<?php
namespace Sil\SilAuth\system;

use Psr\Log\LoggerInterface;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\ldap\Ldap;
use Sil\SilAuth\models\User;

class System
{
    protected $logger;
    
    /**
     * Constructor.
     *
     * @param LoggerInterface|null $logger (Optional:) A PSR-3 compatible logger.
     */
    public function __construct($logger = null)
    {
        $this->logger = $logger;
    }
    
    protected function isLdapOkay()
    {
        try {
            $ldap = new Ldap(ConfigManager::getSspConfigFor('ldap'));
            $ldap->userExists(null);
            return true;
        } catch (\Throwable $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
    
    protected function isDatabaseOkay()
    {
        try {
            User::findByUsername(null);
            return true;
        } catch (\Throwable $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Check the status of the system, and throw an exception (that is safe to
     * show to the public) if any serious error conditions are found. Log any
     * problems, even if recoverable.
     * 
     * @throws \Exception
     */
    public function reportStatus()
    {
        if ( ! $this->isDatabaseOkay()) {
            $this->reportError('Database problem', 1485284407);
        }
        
        if ( ! $this->isLdapOkay()) {
            $this->logError('LDAP problem');
        }
        
        echo 'OK';
    }
    
    /**
     * Add an entry to our log about this error.
     *
     * @param string $message The error message.
     */
    protected function logError($message)
    {
        if ($this->logger !== null) {
            $this->logger->error($message);
        } else {
            echo $message . PHP_EOL;
        }
    }
    
    /**
     * Log this error and throw an exception (with an error message) for the
     * calling code to handle.
     *
     * @param string $message The error message.
     * @param int $code An error code.
     * @throws \Exception
     */
    protected function reportError($message, $code)
    {
        $this->logError($message);
        throw new \Exception($message, $code);
    }
}
