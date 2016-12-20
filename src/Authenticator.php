<?php
namespace Sil\SilAuth;

use Sil\SilAuth\ldap\Ldap;
use Sil\SilAuth\models\User;

class Authenticator
{
    private $errors = [];
    
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
            $this->addUsernameRequiredError();
            return;
        }
        
        if (empty($password)) {
            $this->addPasswordRequiredError();
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
            $this->addInvalidLoginError();
            return;
        }
        
        if ($user->isBlockedByRateLimit()) {
            $friendlyWaitTime = $user->getFriendlyWaitTimeUntilUnblocked();
            $this->addBlockedByRateLimitError($friendlyWaitTime);
            return;
        }
        
        if ( ! $user->isActive()) {
            $this->addInvalidLoginError();
            return;
        }
        
        if ($user->isLocked()) {
            $this->addInvalidLoginError();
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
                    $this->addGenericTryLaterError();
                    return;
                }
            }
        }
        
        if ( ! $user->isPasswordCorrect($password)) {
            $user->recordLoginAttemptInDatabase();
            $this->addInvalidLoginError();
            return;
        }
        
        // NOTE: If we reach this point, the user successfully authenticated.
        
        $user->resetFailedLoginAttemptsInDatabase();
    }
    
    protected function addError($errorMessage)
    {
        $this->errors[] = $errorMessage;
    }
    
    protected function addBlockedByRateLimitError($friendlyWaitTime)
    {
        $this->addError(\Yii::t(
            'app',
            'There have been too many failed logins for this account. Please wait {friendlyWaitTime}, then try again.',
            ['friendlyWaitTime' => $friendlyWaitTime]
        ));
    }
    
    protected function addGenericTryLaterError()
    {
        $this->addError(\Yii::t(
            'app',
            'Hmm... something went wrong. Please try again later. '
        ));
    }
    
    protected function addInvalidLoginError()
    {
        $this->addError(\Yii::t(
            'app',
            'Either the username or password was not correct or this account is disabled. '
            . "Please try again or contact your organization's help desk."
        ));
    }
    
    protected function addPasswordRequiredError()
    {
        $this->addError(\Yii::t('app', 'Please provide a password.'));
    }
    
    protected function addUsernameRequiredError()
    {
        $this->addError(\Yii::t('app', 'Please provide a username.'));
    }
    
    /**
     * Get any error messages.
     * 
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    protected function hasErrors()
    {
        return (count($this->errors) > 0);
    }
    
    /**
     * Check whether authentication was successful. If not, call getErrors() to
     * find out why not.
     * 
     * @return bool
     */
    public function isAuthenticated()
    {
        return ( ! $this->hasErrors());
    }
}
