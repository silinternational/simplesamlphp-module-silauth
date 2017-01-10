<?php
namespace Sil\SilAuth\auth;

use Sil\SilAuth\auth\AuthError;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\ldap\Ldap;
use Sil\SilAuth\models\User;

/**
 * An immutable class for making a single attempt to authenticate using a given
 * username and password.
 */
class Authenticator
{
    /** @var AuthError|null */
    private $authError = null;
    private $userAttributes = null;
    
    /**
     * Attempt to authenticate using the given username and password. Check
     * isAuthenticated() to see whether authentication was successful.
     * 
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        if (empty($username)) {
            $this->setErrorUsernameRequired();
            return;
        }
        
        if (empty($password)) {
            $this->setErrorPasswordRequired();
            return;
        }
        
        /* @todo Make sure the CSRF has been validated. */
        
        /* @todo If enough failed logins have occurred that we require a
         *       Captcha, make sure the Captcha is correct.  */
        
        $user = User::findByUsername($username);
        if ($user === null) {

            /* "Check" the given password even though we have no such user,
             * to avoid exposing the existence of certain users (or absence
             * thereof) through a timing attack. Technically, they could still
             * deduce it since we don't rate-limit non-existent accounts (in
             * order to protect our database from a DDoS attack), but this at
             * least reduces the number of available side channels.  */
            $dummyUser = new User();
            $dummyUser->isPasswordCorrect($password);

            // Now proceed with the appropriate error message.
            $this->setErrorInvalidLogin();
            return;
        }
        
        if ($user->isBlockedByRateLimit()) {
            $friendlyWaitTime = $user->getFriendlyWaitTimeUntilUnblocked();
            $this->setErrorBlockedByRateLimit($friendlyWaitTime);
            return;
        }
        
        if ( ! $user->isActive()) {
            $this->setErrorInvalidLogin();
            return;
        }
        
        if ($user->isLocked()) {
            $this->setErrorInvalidLogin();
            return;
        }
        
        if ( ! $user->hasPasswordInDatabase()) {
            $ldap = new Ldap(ConfigManager::getSspConfigFor('ldap'));
            if ($ldap->isPasswordCorrectForUser($username, $password)) {
                $user->setPassword($password);
                if ( ! $user->save()) {
                    AuthError::logError(sprintf(
                        'Failed to record password from LDAP into database for %s: %s',
                        var_export($username, true),
                        print_r($user->getErrors(), true)
                    ));
                    $this->setErrorGenericTryLater();
                    return;
                }
            }
        }
        
        if ( ! $user->isPasswordCorrect($password)) {
            $user->recordLoginAttemptInDatabase();
            $this->setErrorInvalidLogin();
            return;
        }
        
        // NOTE: If we reach this point, the user successfully authenticated.
        
        $user->resetFailedLoginAttemptsInDatabase();
        
        $this->setUserAttributes([
            'eduPersonTargetID' => $user->uuid,
            'sn' => $user->last_name,
            'givenName' => $user->first_name,
            'mail' => $user->email,
            'username' => $user->username,
            'employeeId' => $user->employee_id,
        ]);
    }
    
    /**
     * Get the error information (if any).
     * 
     * @return AuthError|null
     */
    public function getAuthError()
    {
        return $this->authError;
    }
    
    public function getUserAttributes()
    {
        if ($this->userAttributes === null) {
            throw new \Exception(
                "You cannot get the user's attributes until you have authenticated the user.",
                1482270373
            );
        }
        
        return $this->userAttributes;
    }
    
    protected function hasError()
    {
        return ($this->authError !== null);
    }
    
    /**
     * Check whether authentication was successful. If not, call
     * getErrorMessage() and/or getErrorCode() to find out why not.
     * 
     * @return bool
     */
    public function isAuthenticated()
    {
        return ( ! $this->hasError());
    }
    
    protected function setError($code, $message, $messageParams = [])
    {
        $this->authError = new AuthError($code, $message, $messageParams);
    }
    
    protected function setErrorBlockedByRateLimit($friendlyWaitTime)
    {
        
        
        
        /** @todo Change this to use the appropriate code based on wait time. */
        
        
        
        $this->setError(
            AuthError::CODE_BLOCKED_BY_RATE_LIMIT,
            'There have been too many failed logins for this account. '
            . 'Please wait {friendlyWaitTime}, then try again.',
            ['friendlyWaitTime' => $friendlyWaitTime]
        );
    }
    
    protected function setErrorGenericTryLater()
    {
        $this->setError(
            AuthError::CODE_GENERIC_TRY_LATER,
            'Hmm... something went wrong. Please try again later.'
        );
    }
    
    protected function setErrorInvalidLogin()
    {
        $this->setError(
            AuthError::CODE_INVALID_LOGIN_ERROR,
            'Either the username or password was not correct or this account is disabled. '
            . "Please try again or contact your organization's help desk."
        );
    }
    
    protected function setErrorPasswordRequired()
    {
        $this->setError(
            AuthError::CODE_PASSWORD_REQUIRED,
            'Please provide a password.'
        );
    }
    
    protected function setErrorUsernameRequired()
    {
        $this->setError(
            AuthError::CODE_USERNAME_REQUIRED,
            'Please provide a username.'
        );
    }
    
    protected function setUserAttributes($attributes)
    {
        $this->userAttributes = $attributes;
    }
}
