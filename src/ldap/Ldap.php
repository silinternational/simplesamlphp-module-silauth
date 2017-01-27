<?php
namespace Sil\SilAuth\ldap;

use Adldap\Adldap;
use Adldap\Exceptions\Auth\BindException;
use Adldap\Exceptions\Auth\PasswordRequiredException;
use Adldap\Exceptions\Auth\UsernameRequiredException;
use Adldap\Schemas\OpenLDAP;
use Adldap\Connections\Provider;

class Ldap
{
    private $config = [];
    private $errors = [];
    private $provider = null;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
        
        if ($this->config['use_ssl'] && $this->config['use_tls']) {
            // Prefer TLS over SSL.
            $this->config['use_ssl'] = false;
        }
    }
    
    /**
     * Connect to the LDAP (if not yet connected).
     *
     * @throws LdapConnectionException
     */
    public function connect()
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
                throw new LdapConnectionException(sprintf(
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
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Look for an LDAP User record with the given CN value. If found, return
     * it. Otherwise return null.
     *
     * @param string $userCn The CN value to search for.
     * @return \Adldap\Models\User|null The LDAP User record, or null.
     */
    protected function getUserByCn($userCn)
    {
        $this->connect();
        $results = $this->provider->search()->select([
            'mail', // Email address
            'givenname', // First name
            'sn', // Last name
            'employeenumber', // Employee ID
            'dn', // Distinguished name
        ])->where(['cn' => $userCn])->get();
        foreach ($results as $ldapUser) {
            /* @var $ldapUser \Adldap\Models\User */
            return $ldapUser;
        }
        return null;
    }
    
    /**
     * See if the given credentials are correct (according to the LDAP).
     *
     * @param string $userCn
     * @param string $password
     * @return boolean
     */
    public function isPasswordCorrectForUser($userCn, $password)
    {
        try {
            $ldapUser = $this->getUserByCn($userCn);
            if ($ldapUser === null) {
                return false;
            }
            return $this->provider->auth()->attempt($ldapUser->dn, $password);
        } catch (UsernameRequiredException $e) {
            return false;
        } catch (PasswordRequiredException $e) {
            return false;
        }
    }
    
    /**
     * Determine whether the specified user exists in the LDAP.
     * 
     * @param string $userCn The CN attribute value to match against.
     * @return bool Whether the user exists.
     */
    public function userExists($userCn)
    {
        $ldapUser = $this->getUserByCn($userCn);
        return (( ! empty($ldapUser)) && $ldapUser->exists);
    }
}
