<?php
namespace Sil\SilAuth\auth;

use Psr\Log\LoggerInterface;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\SilAuth\saml\User as SamlUser;

class IdBroker
{
    /** @var IdBrokerClient */
    protected $client;
    
    /** @var LoggerInterface */
    protected $logger;
    
    protected $idpDomainName;
    
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
        LoggerInterface $logger,
        string $idpDomainName
    ) {
        $this->logger = $logger;
        $this->idpDomainName = $idpDomainName;
        $this->client = new IdBrokerClient($baseUri, $accessToken, [
            'http_client_options' => [
                'timeout' => 10,
            ],
        ]);
    }
    
    /**
     * Attempt to authenticate with the given username and password, returning
     * the attributes for that user if the credentials were acceptable (or null
     * if they were not acceptable, since there is no authenticated user in that
     * situation). If an unexpected response is received, an exception will be
     * thrown.
     *
     * NOTE: The attributes names used (if any) in the response will be SAML
     *       field names, not ID Broker field names.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return array|null The user's attributes (if successful), otherwise null.
     * @throws \Exception
     */
    public function getAuthenticatedUser(string $username, string $password)
    {
        $userInfo = $this->client->authenticate($username, $password);
        
        if ($userInfo === null) {
            return null;
        }
        
        $pwExpDate = $userInfo['password']['expires_on'] ?? null;
        if ($pwExpDate !== null) {
            $schacExpiryDate = gmdate('YmdHis\Z', strtotime($pwExpDate));
        }
        
        return SamlUser::convertToSamlFieldNames(
            $userInfo['employee_id'],
            $userInfo['first_name'],
            $userInfo['last_name'],
            $userInfo['username'],
            $userInfo['email'],
            $userInfo['uuid'],
            $this->idpDomainName,
            $schacExpiryDate ?? null
        );
    }
}
