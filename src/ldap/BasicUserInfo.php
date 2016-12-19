<?php
namespace Sil\SilAuth\ldap;

class BasicUserInfo
{
    public function __construct(
        $username,
        $email,
        $employeeId,
        $firstName,
        $lastName
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->employeeId = $employeeId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
    
    public function getEmail()
    {
        return $this->email;
    }
    
    public function getEmployeeId()
    {
        return $this->employeeId;
    }
    
    public function getFirstName()
    {
        return $this->firstName;
    }
    
    public function getLastName()
    {
        return $this->lastName;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
}
