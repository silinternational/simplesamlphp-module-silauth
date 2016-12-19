<?php
namespace Sil\SilAuth;

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
        
        /* @var $user User */
        $user = User::findByUsername($username) ?? (new User());
        
        if ($user->isBlockedByRateLimit()) {
            $friendlyWaitTime = $user->getFriendlyWaitTimeUntilUnblocked();
            $this->addBlockedByRateLimitError($friendlyWaitTime);
            return;
        }
        
        if ( ! $user->isActive()) {
            $this->addInactiveAccountError();
            return;
        }
        
        if ($user->isLocked()) {
            $this->addLockedAccountError();
            return;
        }
        
        /* Check the given password even if we have no such user, to avoid
         * exposing the existence of certain users through a timing attack.  */
        $passwordHash = (($user === null) ? null : $user->password_hash);
        if ( ! password_verify($password, $passwordHash)) {
            if ( ! $user->isNewRecord) {
                $user->recordLoginAttemptInDatabase();
            }
            $this->addWrongUsernameOrPasswordError();
            return;
        }
        
        $user->resetFailedLoginAttemptsInDatabase();
        
        // NOTE: If we reach this point, the user successfully authenticated.
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
    
    protected function addInactiveAccountError()
    {
        $this->addError(\Yii::t(
            'app',
            "That account is not active. If it is your account, please contact your organization's help desk."
        ));
    }
    
    protected function addLockedAccountError()
    {
        $this->addError(\Yii::t(
            'app',
            "That account is locked. If it is your account, please contact your organization's help desk."
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
    
    protected function addWrongUsernameOrPasswordError()
    {
        $this->addError(\Yii::t(
            'app',
            'Either the username or the password was not correct. Please try again.'
        ));
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
