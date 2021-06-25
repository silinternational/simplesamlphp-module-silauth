<?php

namespace SimpleSAML\Module\silauth\Auth\Source;

use Sil\Psr3Adapters\Psr3StdOutLogger;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\auth\IdBroker;
use Sil\SilAuth\captcha\Captcha;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\http\Request;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\Error;
use SimpleSAML\Module;
use SimpleSAML\Module\core\Auth\UserPassBase;
use SimpleSAML\Utils\HTTP;

/**
 * Class SimpleSAML\Module\silauth\Auth\Source\SilAuth.
 *
 * SimpleSAMLphp auth library to support custom business rules
 *
 * Configuration settings defined in src/config/ssp-config.php.
 */
class SilAuth extends UserPassBase
{
    protected $authConfig;
    protected $idBrokerConfig;
    protected $mysqlConfig;
    protected $recaptchaConfig;
    protected $templateData;

    /**
     * Constructor for this authentication source.
     *
     * All subclasses who implement their own constructor must call this constructor before
     * using $config for anything.
     *
     * @param array $info Information about this authentication source.
     * @param array $config Configuration for this authentication source.
     */
    public function __construct($info, $config)
    {
        parent::__construct($info, $config);
        
        $this->authConfig = ConfigManager::getConfigFor('auth', $config);
        $this->idBrokerConfig = ConfigManager::getConfigFor('idBroker', $config);
        $this->mysqlConfig = ConfigManager::getConfigFor('mysql', $config);
        $this->recaptchaConfig = ConfigManager::getConfigFor('recaptcha', $config);
        $this->templateData = ConfigManager::getConfigFor('templateData', $config);

        ConfigManager::initializeYii2WebApp(['components' => ['db' => [
            'dsn' => sprintf(
                'mysql:host=%s;dbname=%s',
                $this->mysqlConfig['host'],
                $this->mysqlConfig['database']
            ),
            'username' => $this->mysqlConfig['user'],
            'password' => $this->mysqlConfig['password'],
        ]]]);
    }

    /**
     * Initialize login.
     *
     * This function saves the information about the login, and redirects to a
     * login page.
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(&$state)
    {
        assert('is_array($state)');

        /*
         * Save the identifier of this authentication source, so that we can
         * retrieve it later. This allows us to call the login()-function on
         * the current object.
         */
        $state[self::AUTHID] = $this->authId;

        $state['templateData'] = $this->templateData;

        /* Save the $state-array, so that we can restore it after a redirect. */
        $id = State::saveState($state, self::STAGEID);

        /*
         * Redirect to the login form. We include the identifier of the saved
         * state array as a parameter to the login form.
         */
        $url = Module::getModuleURL('silauth/loginuserpass.php');
        $params = array('AuthState' => $id);
        HTTP::redirectTrustedURL($url, $params);

        /* The previous function never returns, so this code is never executed. */
        assert('FALSE');
    }
    
    protected function getTrustedIpAddresses()
    {
        $trustedIpAddresses = [];
        $ipAddressesString = $this->authConfig['trustedIpAddresses'] ?? '';
        $stringPieces = explode(',', $ipAddressesString);
        foreach ($stringPieces as $stringPiece) {
            if ( ! empty($stringPiece)) {
                $trustedIpAddresses[] = $stringPiece;
            }
        }
        return $trustedIpAddresses;
    }
    
    protected function login($username, $password)
    {
        $logger = new Psr3StdOutLogger();
        $captcha = new Captcha($this->recaptchaConfig['secret'] ?? null);
        $idBroker = new IdBroker(
            $this->idBrokerConfig['baseUri'] ?? null,
            $this->idBrokerConfig['accessToken'] ?? null,
            $logger,
            $this->idBrokerConfig['idpDomainName'],
            $this->idBrokerConfig['trustedIpRanges'] ?? [],
            $this->idBrokerConfig['assertValidIp'] ?? true
        );
        $request = new Request($this->getTrustedIpAddresses());
        $userAgent = Request::getUserAgent() ?: '(unknown)';
        $authenticator = new Authenticator(
            $username,
            $password,
            $request,
            $captcha,
            $idBroker,
            $logger
        );
        
        if ( ! $authenticator->isAuthenticated()) {
            $authError = $authenticator->getAuthError();
            $logger->warning(json_encode([
                'event' => 'User/pass authentication result: failure',
                'username' => $username,
                'errorCode' => $authError->getCode(),
                'errorMessageParams' => $authError->getMessageParams(),
                'ipAddresses' => join(',', $request->getIpAddresses()),
                'userAgent' => $userAgent,
            ]));
            throw new Error([
                'WRONGUSERPASS',
                $authError->getFullSspErrorTag(),
                $authError->getMessageParams()
            ]);
        }
        
        $logger->warning(json_encode([
            'event' => 'User/pass authentication result: success',
            'username' => $username,
            'ipAddresses' => join(',', $request->getIpAddresses()),
            'userAgent' => $userAgent,
        ]));
        
        return $authenticator->getUserAttributes();
    }
}
