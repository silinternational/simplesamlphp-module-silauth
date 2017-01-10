<?php

use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\auth\AuthError;

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
        
        require_once __DIR__ . '/../../../src/bootstrap-yii2.php';
    }
    
    protected function login($username, $password)
    {
        $authenticator = new Authenticator($username, $password);
        
        if ( ! $authenticator->isAuthenticated()) {
            $authError = $authenticator->getAuthError();
            throw new SimpleSAML_Error_Error([
                'WRONGUSERPASS',
                $authError->getFullSspErrorTag(),
                $authError->getMessageParams()
            ]);
        }
        
        return $authenticator->getUserAttributes();
    }
}
