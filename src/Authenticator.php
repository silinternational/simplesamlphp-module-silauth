<?php
namespace Sil\SilAuth;

use Sil\SilAuth\ldap\Ldap;
use Sil\SilAuth\models\User;

class Authenticator
{
    const ERROR_GENERIC_TRY_LATER = 1;
    const ERROR_USERNAME_REQUIRED = 2;
    const ERROR_PASSWORD_REQUIRED = 3;
    const ERROR_INVALID_LOGIN_ERROR = 4;
    const ERROR_BLOCKED_BY_RATE_LIMIT = 5;
    
    private $errorCode = null;
    private $errorMessage = null;
    
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
            $ldap = new Ldap();
            if ($ldap->isPasswordCorrectForUser($username, $password)) {
                $user->setPassword($password);
                if ( ! $user->save()) {
                    \Yii::error(sprintf(
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
    }
    
    protected function setError($code, $message, $messageParams = [])
    {
        $this->errorCode = $code;
        $this->errorMessage = \Yii::t('app', $message, $messageParams);
    }
    
    protected function setErrorBlockedByRateLimit($friendlyWaitTime)
    {
        $this->setError(
            self::ERROR_BLOCKED_BY_RATE_LIMIT,
            'There have been too many failed logins for this account. '
            . 'Please wait {friendlyWaitTime}, then try again.',
            ['friendlyWaitTime' => $friendlyWaitTime]
        );
    }
    
    protected function setErrorGenericTryLater()
    {
        $this->setError(
            self::ERROR_GENERIC_TRY_LATER,
            'Hmm... something went wrong. Please try again later. '
        );
    }
    
    protected function setErrorInvalidLogin()
    {
        $this->setError(
            self::ERROR_INVALID_LOGIN_ERROR,
            'Either the username or password was not correct or this account is disabled. '
            . "Please try again or contact your organization's help desk."
        );
    }
    
    protected function setErrorPasswordRequired()
    {
        $this->setError(
            self::ERROR_PASSWORD_REQUIRED,
            'Please provide a password.'
        );
    }
    
    protected function setErrorUsernameRequired()
    {
        $this->setError(
            self::ERROR_USERNAME_REQUIRED,
            'Please provide a username.'
        );
    }
    
    /**
     * Get the error code (if any).
     * 
     * @return int|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
    
    /**
     * Get the error message (if any).
     * 
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
    
    protected function hasError()
    {
        return (($this->errorMessage !== null) || ($this->errorCode !== null));
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
}
