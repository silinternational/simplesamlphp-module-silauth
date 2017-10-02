<?php
namespace Sil\SilAuth\auth;

use Psr\Log\LoggerInterface;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\SilAuth\mfa\MfaInfo;
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
     * @param LoggerInterface $logger A PSR-3 compliant logger.
     * @param string $idpDomainName Unique identifier for this IdP-in-a-Box
     *     instance. This is used for assembling the eduPersonPrincipalName for
     *     users (e.g. "username@idp.domain.name").
     *     EXAMPLE: idp.domain.name
     * @param array $trustedIpRanges List of valid IP address ranges (CIDR) for
     *     the ID Broker API.
     * @param bool $assertValidIp (Optional:) Whether or not to assert that the
     *     IP address for the ID Broker API is trusted.
     */
    public function __construct(
        string $baseUri,
        string $accessToken,
        LoggerInterface $logger,
        string $idpDomainName,
        array $trustedIpRanges,
        bool $assertValidIp = true
    ) {
        $this->logger = $logger;
        $this->idpDomainName = $idpDomainName;
        $this->client = new IdBrokerClient($baseUri, $accessToken, [
            'http_client_options' => [
                'timeout' => 10,
            ],
            IdBrokerClient::TRUSTED_IPS_CONFIG => $trustedIpRanges,
            IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => $assertValidIp,
        ]);
    }
    
    /**
     * Attempt to authenticate with the given username and password.
     * - If the credentials were acceptable and we do NOT need to prompt the
     *   user for Multi-Factor Authentication (MFA), we return the attributes
     *   for that user.
     * - If acceptable and we DO need to prompt for MFA, we return info about
     *   MFA for that user.
     * - If the credentials were NOT acceptable, we return null, since there is
     *   no authenticated user in that situation.
     *
     * If an unexpected response is received, an exception will be thrown.
     *
     * NOTE: The attributes names used (if any) in the response will be SAML
     *       field names, not ID Broker field names.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return array|MfaInfo|null An array of user attributes, or info about
     *     prompting for MFA, or null.
     * @throws \Exception
     */
    public function getAuthenticatedUser(string $username, string $password)
    {
        $userInfo = $this->client->authenticate($username, $password);
        
        if ($userInfo === null) {
            return null;
        }
        
        $mfaInfo = MfaInfo::createFromArray($userInfo);
        if ($mfaInfo->shouldPromptForMfa()) {
            return $mfaInfo;
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
    
    /**
     * Ping the /site/status URL. If the ID Broker's status is fine, the
     * response string is returned. If not, an exception is thrown.
     *
     * @return string "OK"
     * @throws Exception
     */
    public function getSiteStatus()
    {
        return $this->client->getSiteStatus();
    }
}
