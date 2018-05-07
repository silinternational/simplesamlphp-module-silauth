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
        array $mfa
    ) {
        return [
            'eduPersonPrincipalName' => [
                $username . '@' . $idpDomainName,
            ],
            'eduPersonTargetID' => (array)$uuid, // Incorrect, deprecated
            
            // DO NOT INCLUDE until we can format it as a saml:NameID element.
            //'eduPersonTargetedID' => (array)$uuid,
            
            'sn' => (array)$lastName,
            'givenName' => (array)$firstName,
            'mail' => (array)$email,
            'employeeNumber' => (array)$employeeId,
            'cn' => (array)$username,
            'schacExpiryDate' => (array)$passwordExpirationDate,
            'mfa' => $mfa,
        ];
    }
}
