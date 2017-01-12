<?php

ini_set('display_errors', 'On');

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

	/**
	 * Initialize login.
	 *
	 * This function saves the information about the login, and redirects to a
	 * login page.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/*
		 * Save the identifier of this authentication source, so that we can
		 * retrieve it later. This allows us to call the login()-function on
		 * the current object.
		 */
		$state[self::AUTHID] = $this->authId;

		/* Save the $state-array, so that we can restore it after a redirect. */
		$id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);

		/*
		 * Redirect to the login form. We include the identifier of the saved
		 * state array as a parameter to the login form.
		 */
		$url = SimpleSAML_Module::getModuleURL('silauth/loginuserpass.php');
		$params = array('AuthState' => $id);
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, $params);

		/* The previous function never returns, so this code is never executed. */
		assert('FALSE');
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
