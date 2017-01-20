<?php
namespace Sil\SilAuth\auth;

use Sil\SilAuth\auth\AuthError;
use Sil\SilAuth\ldap\Ldap;
use Sil\SilAuth\time\WaitTime;
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
     * @param string $username The username to check.
     * @param string $password The password to check.
     * @param Ldap $ldap An object for interacting with the LDAP server.
     */
    public function __construct($username, $password, $ldap)
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
            $this->setErrorBlockedByRateLimit(
                $user->getWaitTimeUntilUnblocked()
            );
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
            'eduPersonTargetID' => [$user->uuid],
            'sn' => [$user->last_name],
            'givenName' => [$user->first_name],
            'mail' => [$user->email],
            'username' => [$user->username],
            'employeeId' => [$user->employee_id],
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
    
    protected function setError($code, $messageParams = [])
    {
        $this->authError = new AuthError($code, $messageParams);
    }
    
    /**
     * @param WaitTime $waitTime
     */
    protected function setErrorBlockedByRateLimit($waitTime)
    {
        $unit = $waitTime->getUnit();
        $number = $waitTime->getFriendlyNumber();
        
        if ($unit === WaitTime::UNIT_SECOND) {
            $errorCode = AuthError::CODE_RATE_LIMIT_SECONDS;
        } else { // = minute
            if ($number === 1) {
                $errorCode = AuthError::CODE_RATE_LIMIT_1_MINUTE;
            } else {
                $errorCode = AuthError::CODE_RATE_LIMIT_MINUTES;
            }
        }
        
        $this->setError($errorCode, ['{number}' => $number]);
    }
    
    protected function setErrorGenericTryLater()
    {
        $this->setError(AuthError::CODE_GENERIC_TRY_LATER);
    }
    
    protected function setErrorInvalidLogin()
    {
        $this->setError(AuthError::CODE_INVALID_LOGIN);
    }
    
    protected function setErrorPasswordRequired()
    {
        $this->setError(AuthError::CODE_PASSWORD_REQUIRED);
    }
    
    protected function setErrorUsernameRequired()
    {
        $this->setError(AuthError::CODE_USERNAME_REQUIRED);
    }
    
    protected function setUserAttributes($attributes)
    {
        $this->userAttributes = $attributes;
    }
}
