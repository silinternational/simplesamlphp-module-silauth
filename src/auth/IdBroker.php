<?php
namespace Sil\SilAuth\auth;

use Psr\Log\LoggerInterface;
use Sil\Idp\IdBroker\Client\IdBrokerClient;

class IdBroker
{
    /** @var IdBrokerClient */
    protected $client;
    
    /** @var LoggerInterface */
    protected $logger;
    
    /**
     * 
     * @param string $baseUri The base of the API's URL.
     *     Example: 'https://api.example.com/'.
     * @param string $accessToken Your authorization access (bearer) token.
     * @param LoggerInterface $logger
     */
    public function __construct(
        string $baseUri,
        string $accessToken,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->client = new IdBrokerClient($baseUri, $accessToken);
    }
    
    /**
     * Attempt to authenticate with the given username and password, returning
     * the attributes for that user if the credentials were acceptable (or null
     * if they were not acceptable, since there is no authenticated user in that
     * situation).
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return array|null The user's attributes (if successful), otherwise null.
     */
    public function getAuthenticatedUser(string $username, string $password)
    {
        $result = $this->client->authenticate([
            'username' => $username,
            'password' => $password,
        ]);
        $statusCode = $result['statusCode'] ?? null;
        if (intval($statusCode) === 200) {
            unset($result['statusCode']);
            return $result;
        }
        return null;
    }
}
