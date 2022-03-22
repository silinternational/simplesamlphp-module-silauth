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
        $managerEmail,
        $profileReview,
        array $member
    ) {
        return [
            'eduPersonPrincipalName' => [
                $username . '@' . $idpDomainName,
            ],

            /**
             * Misspelled version of eduPersonTargetedID. (Accidentally used in the past)
             * @deprecated
             */
            'eduPersonTargetID' => (array)$uuid, // Incorrect, deprecated

            // Proper spelling of eduPersonTargetedID.
            'eduPersonTargetedID' => (array)$uuid,

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
            'profile_review' => [$profileReview],
            'member' => $member,
        ];
    }
}
