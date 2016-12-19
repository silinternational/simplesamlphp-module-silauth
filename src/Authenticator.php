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
        
        $user = User::findByUsername($username);
        if ($user === null) {
            $ldap = new Ldap();
            $basicUserInfo = $ldap->getBasicInfoAboutUser($username);
            if ($basicUserInfo === null) {
                
                /* "Check" the given password even though we have no such user,
                 * to avoid exposing the existence of certain users (or absence
                 * thereof) through a timing attack.  */
                password_verify($password, null);
                
                // Now proceed with the appropriate error message.
                $this->addWrongUsernameOrPasswordError();
                return;
            }
            
            $user = new User([
                'username' => $basicUserInfo->getUsername(),
                'email' => $basicUserInfo->getEmail(),
                'employee_id' => $basicUserInfo->getEmployeeId(),
                'first_name' => $basicUserInfo->getFirstName(),
                'last_name' => $basicUserInfo->getLastName(),
            ]);
            
            if ( ! $user->save()) {
                \Yii::error(sprintf(
                    'Failed to add password-less record to database for a user record in the LDAP: %s',
                    print_r($user->getErrors(), true)
                ));
                throw new \Exception(\Yii::t(
                    'app',
                    'Hmm... something went wrong. Please try again later. '
                    . print_r($user->getErrors(), true)
                ), 1481914909);
            }
        }
        
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
        
        if ( ! password_verify($password, $user->password_hash)) {
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
