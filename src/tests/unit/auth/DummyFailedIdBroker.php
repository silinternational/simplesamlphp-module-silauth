<?php
namespace Sil\SilAuth\tests\unit\auth;

use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\SilAuth\auth\IdBroker;

class DummyFailedIdBroker extends IdBroker
{
    /**
     * 
     * @param string $baseUri The base of the API's URL.
     *     Example: 'https://api.example.com/'.
     * @param string $accessToken Your authorization access (bearer) token.
     * @param LoggerInterface $logger
     */
    public function __construct(
        $baseUri,
        $accessToken,
        LoggerInterface $logger,
        array $responses
    ) {
        $this->logger = $logger;
        $this->client = new IdBrokerClient($baseUri, $accessToken);
    }
    
    public function isValidCredentials(string $username, string $password)
    {
        $this->logger->info('DUMMY: {username} and {password} considered NOT valid.', [
            'username' => var_export($username, true),
            'password' => var_export($password, true),
        ]);
        return false;
    }
}
