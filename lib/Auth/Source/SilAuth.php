<?php

use Sil\Psr3Adapters\Psr3SamlLogger;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\auth\IdBroker;
use Sil\SilAuth\captcha\Captcha;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\http\Request;

/**
 * Class sspmod_silauth_Auth_Source_SilAuth.
 *
 * SimpleSAMLphp auth library to support custom business rules support migrating
 * accounts from LDAP to DB.
 *
 * Configuration settings defined in src/config/ssp-config.php.
 */
class sspmod_silauth_Auth_Source_SilAuth extends sspmod_core_Auth_UserPassBase
{
    protected $authConfig;
    protected $idBrokerConfig;
    protected $mysqlConfig;
    protected $recaptchaConfig;
    
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

        /* Save the $state-array, so that we can restore it after a redirect. */
        $id = \SimpleSAML\Auth\State::saveState($state, self::STAGEID);

        /*
         * Redirect to the login form. We include the identifier of the saved
         * state array as a parameter to the login form.
         */
        $url = \SimpleSAML\Module::getModuleURL('silauth/loginuserpass.php');
        $params = array('AuthState' => $id);
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, $params);

        /* The previous function never returns, so this code is never executed. */
        assert('FALSE');
    }
    
    protected function getTrustedIpAddresses()
    {
        $trustedIpAddresses = [];
        $ipAddressesString = $this->authConfig['trustedIpAddresses'] ?? '';
        $stringPieces = explode(',', $ipAddressesString);
        foreach ($stringPieces as $stringPiece) {
            if (! empty($stringPiece)) {
                $trustedIpAddresses[] = $stringPiece;
            }
        }
        return $trustedIpAddresses;
    }
    
    protected function login($username, $password)
    {
        $logger = new Psr3SamlLogger();
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
        $untrustedIpAddresses = $request->getUntrustedIpAddresses();
        $userAgent = Request::getUserAgent() ?: '(unknown)';
        $authenticator = new Authenticator(
            $username,
            $password,
            $request,
            $captcha,
            $idBroker,
            $logger
        );
        
        if (! $authenticator->isAuthenticated()) {
            $authError = $authenticator->getAuthError();
            $logger->warning(json_encode([
                'event' => 'User/pass authentication result: failure',
                'username' => $username,
                'errorCode' => $authError->getCode(),
                'errorMessageParams' => $authError->getMessageParams(),
                'ipAddresses' => join(',', $untrustedIpAddresses),
                'userAgent' => $userAgent,
            ]));
            throw new \SimpleSAML\Error\Error([
                'WRONGUSERPASS',
                $authError->getFullSspErrorTag(),
                $authError->getMessageParams()
            ]);
        }
        
        $logger->warning(json_encode([
            'event' => 'User/pass authentication result: success',
            'username' => $username,
            'ipAddresses' => join(',', $untrustedIpAddresses),
            'userAgent' => $userAgent,
        ]));
        
        return $authenticator->getUserAttributes();
    }
}
