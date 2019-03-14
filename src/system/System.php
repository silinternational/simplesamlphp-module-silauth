<?php
namespace Sil\SilAuth\system;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sil\SilAuth\auth\IdBroker;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\models\FailedLoginIpAddress;
use Throwable;

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
        $this->logger = $logger ?? new NullLogger();
    }
    
    protected function isIdBrokerOkay()
    {
        try {
            $idBrokerConfig = ConfigManager::getSspConfigFor('idBroker');
            $idBroker = new IdBroker(
                $idBrokerConfig['baseUri'] ?? null,
                $idBrokerConfig['accessToken'] ?? null,
                $this->logger,
                $idBrokerConfig['idpDomainName'],
                $idBrokerConfig['trustedIpRanges'] ?? [],
                $idBrokerConfig['assertValidIp'] ?? true
            );
            $idBroker->getSiteStatus();
            return true;
        } catch (Throwable $t) {
            $this->logError($t->getMessage());
            return false;
        }
    }
    
    protected function isDatabaseOkay()
    {
        try {
            FailedLoginIpAddress::getMostRecentFailedLoginFor('');
            return true;
        } catch (Throwable $t) {
            $this->logError($t->getMessage());
            return false;
        }
    }
    
    protected function isRequiredConfigPresent()
    {
        $globalConfig = \SimpleSAML\Configuration::getInstance();
        
        /*
         * NOTE: We require that SSP's baseurlpath configuration is set (and
         *       matches the corresponding RegExp) in order to prevent a
         *       security hole in \SimpleSAML\Utils\HTTP::getBaseURL() where the
         *       HTTP_HOST value (provided by the user's request) is used to
         *       build a trusted URL (see SimpleSaml\Module::authenticate()).
         */
        $baseURL = $globalConfig->getString('baseurlpath', '');
        $avoidsSecurityHole = (preg_match('#^https?://.*/$#D', $baseURL) === 1);
        return $avoidsSecurityHole;
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
        if ( ! $this->isRequiredConfigPresent()) {
            $this->reportError('Config problem', 1485984755);
        }
        
        if ( ! $this->isDatabaseOkay()) {
            $this->reportError('Database problem', 1485284407);
        }
        
        if ( ! $this->isIdBrokerOkay()) {
            $this->reportError('ID Broker problem', 1503587665);
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
        $this->logger->error($message);
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
