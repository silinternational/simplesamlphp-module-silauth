<?php

use Sil\SilAuth\Authenticator;

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
    }

    protected function login($username, $password)
    {
        $authenticator = new Authenticator($username, $password);
        
        if ( ! $authenticator->isAuthenticated()) {
            throw new SimpleSAML_Error_Error('WRONGUSERPASS', new \Exception(
                $authenticator->getErrorMessage(),
                $authenticator->getErrorCode()
            ));
        }
        
        return $authenticator->getUserAttributes();
    }
}
