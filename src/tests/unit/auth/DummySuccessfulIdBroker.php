<?php
namespace Sil\SilAuth\tests\unit\auth;

use Sil\SilAuth\auth\IdBroker;

class DummySuccessfulIdBroker extends IdBroker
{
    public function isValidCredentials(string $username, string $password)
    {
        $this->logger->info('DUMMY: {username} and {password} considered VALID.', [
            'username' => var_export($username, true),
            'password' => var_export($password, true),
        ]);
        return true;
    }    
    
    public function getUserAttributesFor($username)
    {
        return [
            'eduPersonTargetID' => ['abc-123-dummy-id'],
            'sn' => ['Dummylastname'],
            'givenName' => ['Dummyfirstname'],
            'mail' => [$username . '@example.com'],
            'username' => [$username],
            'employeeId' => ['11111'],
        ];
    }
}
