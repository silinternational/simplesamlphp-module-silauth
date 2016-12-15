<?php
namespace Sil\SilAuth\ldap;

use Adldap\Adldap;
use Adldap\Exceptions\Auth\BindException;
use Adldap\Exceptions\Auth\PasswordRequiredException;
use Adldap\Exceptions\Auth\UsernameRequiredException;
use Adldap\Schemas\OpenLDAP;
use Adldap\Connections\Provider;
use Sil\PhpEnv\Env;
use \yii\helpers\ArrayHelper;

class Ldap
{
    private $config = [];
    private $errors = [];
    private $provider = null;
    
    public function __construct(array $config = [])
    {
        $this->config = ArrayHelper::merge([
            'account_suffix' => Env::get('LDAP_ACCT_SUFFIX'),
            'domain_controllers' => explode('|', Env::get('LDAP_DOMAIN_CONTROLLERS')),
            'base_dn' => Env::get('LDAP_BASE_DN'),
            'admin_username' => Env::get('LDAP_ADMIN_USERNAME'),
            'admin_password' => Env::get('LDAP_ADMIN_PASSWORD'),
            'use_ssl' => Env::get('LDAP_USE_SSL', true),
            'use_tls' => Env::get('LDAP_USE_TLS', true),
            'timeout' => Env::get('LDAP_TIMEOUT', 5),
        ], $config);
        
        if ($this->config['use_ssl'] && $this->config['use_tls']) {
            // Prefer TLS over SSL.
            $this->config['use_ssl'] = false;
        }
        
        var_dump($this->config); // TEMP
    }
    
    protected function connect()
    {
        if ($this->provider === null) {
            $schema = new OpenLDAP();
            $provider = new Provider($this->config, null, $schema);
            $ldapClient = new Adldap();
            $ldapClient->addProvider('default', $provider);
            
            try {
                $ldapClient->connect('default');
                $this->provider = $provider;
            } catch (BindException $e) {
                throw new \Exception(sprintf(
                    'There was a problem connecting to the LDAP server: (%s) %s',
                    $e->getCode(),
                    $e->getMessage()
                ), 1481752312, $e);
            }
        }
    }
    
    protected function addError($errorMessage)
    {
        $this->errors[] = $errorMessage;
    }
    
    /**
     * Delete the specified user in the LDAP.
     * 
     * @param string $username The username of the record to delete.
     * @return bool Whether the deletion was successful. If not, check
     *     getErrors() to see why.
     */
    public function deleteUser($username)
    {
        
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function isValidUsernameAndPassword($username, $password)
    {
        try {
            $this->connect();
            return ($this->provider->auth()->attempt($username, $password));
        } catch (UsernameRequiredException $e) {
            return false;
        } catch (PasswordRequiredException $e) {
            return false;
        }
    }
}
