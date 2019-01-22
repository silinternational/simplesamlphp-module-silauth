<?php
namespace Sil\SilAuth\saml;

class User
{
    public static function convertToSamlFieldNames(
        string $employeeId,
        string $firstName,
        string $lastName,
        string $username,
        string $email,
        string $uuid,
        string $idpDomainName,
        $passwordExpirationDate,
        array $mfa,
        array $method,
        $managerEmail
    ) {
        return [
            'eduPersonPrincipalName' => [
                $username . '@' . $idpDomainName,
            ],
            
            /**
             * Misspelled version of eduPersonTargetedID.
             * @deprecated
             */
            'eduPersonTargetID' => (array)$uuid, // Incorrect, deprecated
            
            /**
             * NOTE: Do NOT include eduPersonTargetedID. If you need it, use the
             * core:TargetedID module (at the Hub, if using one) to generate an
             * eduPersonTargetedID.
             */
            
            'sn' => (array)$lastName,
            'givenName' => (array)$firstName,
            'mail' => (array)$email,
            'employeeNumber' => (array)$employeeId,
            'cn' => (array)$username,
            'schacExpiryDate' => (array)$passwordExpirationDate,
            'mfa' => $mfa,
            'method' => $method,
            'uuid' => (array)$uuid,
            'manager_email' => [$managerEmail ?? ''],
        ];
    }
}
