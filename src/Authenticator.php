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
     * @return void
     */
    public function __construct($username, $password)
    {
        if (empty($username)) {
            $this->addError('Please provide a username');
            return;
        }
        
        if (empty($password)) {
            $this->addError('Please provide a password');
            return;
        }
        
        $user = User::where('username', $username)->first();
        
        /* Check the given password even if we have no such user, to avoid
         * exposing the existence of certain users through a timing attack.  */
        $passwordHash = (($user === null) ? null : $user->password_hash);
        if ( ! password_verify($password, $passwordHash)) {
            $this->addError('Either the username or the password was not correct. Please try again.');
            return;
        }
        
        /* @todo If we reach this point, are we authenticated? */
        
    }
    
    protected function addError($errorMessage)
    {
        $this->errors[] = $errorMessage;
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
