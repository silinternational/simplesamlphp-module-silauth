<?php

/**
 * Class sspmod_silauth_Auth_Source_SilAuth
 * simpleSAMLphp auth library to support custom business rules support migrating accounts from LDAP to DB
 *
 * Configuration settings (defined in authsources.php):
 *  - db.driver
 *  - db.host
 *  - db.database
 *  - db.username
 *  - db.password
 *  - db.charset
 *  - db.collation
 *  - db.prefix
 *  - ldap.baseDn
 *  - ldap.host
 *  - ldap.port
 *  - ldap.useSsl
 *  - ldap.useTls
 *  - recaptcha.clientId
 *  - recaptcha.secret
 *
 */
class sspmod_silauth_Auth_Source_SilAuth extends SimpleSAML_Auth_Source
{

    /**
     * The string used to identify our states.
     */
    const STAGEID = 'sspmod_silauth_Auth_Source_SilAuth.state';


    /**
     * The key of the AuthId field in the state.
     */
    const AUTHID = 'sspmod_silauth_Auth_Source_SilAuth.AuthId';


    /**
     * sspmod_silauth_Auth_Source_SilAuth constructor.
     * Call parent constructor and then load in config needed for LDAP and DB connections
     * @param array $info
     * @param array $config
     */
    public function __construct(array $info, array $config)
    {
        parent::__construct($info, $config);


    }

    /**
     * Method required by interface, it simply saves state and redirects to login page.
     * Login page submission is processed by self::handleLogin
     * @param array $state
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

    /**
     * Handle login request.
     *
     * This function is used by the login form (core/www/loginuserpass.php) when the user
     * enters a username and password. On success, it will not return. On wrong
     * username/password failure, and other errors, it will throw an exception.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @throws \Exception
     */
    public static function handleLogin($authStateId, $username, $password)
    {
        assert('is_string($authStateId)');
        assert('is_string($username)');
        assert('is_string($password)');

        /* Here we retrieve the state array we saved in the authenticate-function. */
        $state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);

        /* Retrieve the authentication source we are executing. */
        assert('array_key_exists(self::AUTHID, $state)');
        $source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
        if ($source === NULL) {
            throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }

    }
}