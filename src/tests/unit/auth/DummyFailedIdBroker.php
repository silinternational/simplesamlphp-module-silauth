<?php
namespace Sil\SilAuth\tests\unit\auth;

use Sil\SilAuth\auth\IdBroker;

class DummyFailedIdBroker extends IdBroker
{
    public function isValidCredentials(string $username, string $password)
    {
        $this->logger->info('DUMMY: {username} and {password} considered NOT valid.', [
            'username' => var_export($username, true),
            'password' => var_export($password, true),
        ]);
        return false;
    }
}
