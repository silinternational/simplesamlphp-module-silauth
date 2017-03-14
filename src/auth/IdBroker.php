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
     * situation). If an unexpected response is received, an exception will be
     * thrown.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return array|null The user's attributes (if successful), otherwise null.
     * @throws \Exception
     */
    public function getAuthenticatedUser(string $username, string $password)
    {
        $result = $this->client->authenticate([
            'username' => $username,
            'password' => $password,
        ]);
        $statusCode = $result['statusCode'] ?? null;
        switch (intval($statusCode)) {
            
            case 200: // Credentials were acceptable.
                unset($result['statusCode']);
                return $result;
            
            case 400: // Credentials were NOT acceptable.
                return null;

            default:
                throw new \Exception(sprintf(
                    'Unexpected response from ID Broker: %s',
                    var_export($result->toArray(), true)
                ), 1489500140);
        }
    }
}
