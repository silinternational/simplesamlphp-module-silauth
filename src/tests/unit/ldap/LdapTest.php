<?php
namespace Sil\SilAuth\tests\unit\ldap;

use PHPUnit\Framework\TestCase;
use Sil\SilAuth\ldap\Ldap;

class LdapTest extends TestCase
{
    public function testIsValidUsernameAndPassword()
    {
        // Arrange:
        $testCases = [
            ['u' => 'LDAP_ACCESS',         'p' => 'ldap_access', 'expected' => true],
            ['u' => 'BOB_ADAMS',           'p' => 'asdf',        'expected' => false],
            ['u' => '',                    'p' => 'asdf',        'expected' => false],
            ['u' => null,                  'p' => 'asdf',        'expected' => false],
            ['u' => 'BOB_ADAMS',           'p' => null,          'expected' => false],
            ['u' => 'BOB_ADAMS',           'p' => '',            'expected' => false],
            ['u' => '',                    'p' => '',            'expected' => false],
            ['u' => null,                  'p' => null,          'expected' => false],
            ['u' => null,                  'p' => '',            'expected' => false],
            ['u' => '',                    'p' => null,          'expected' => false],
            ['u' => 'NON_EXISTENT_PERSON', 'p' => 'fdsa',        'expected' => false],
        ];
        $ldap = new Ldap();
        foreach ($testCases as $testCase) {
            $userCn = $testCase['u'];
            $password = $testCase['p'];
            
            // Act:
            $actual = $ldap->isCorrectPasswordForUser($userCn, $password);
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual, sprintf(
                'Expected %s and %s to be %s as %s credentials.',
                var_export($userCn, true),
                var_export($password, true),
                ($testCase['expected'] ? 'accepted' : 'rejected'),
                ($testCase['expected'] ? 'valid' : 'invalid')
            ));
        }
    }
    
    public function testUserExists()
    {
        // Arrange:
        $testCases = [
            ['username' => 'BOB_ADAMS', 'expected' => true],
            ['username' => 'NON_EXISTENT_PERSON', 'expected' => false],
        ];
        $ldap = new Ldap();
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = $ldap->userExists($testCase['username']);
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual, sprintf(
                'Expected %s user%s to exist.',
                var_export($testCase['username'], true),
                ($testCase['expected'] ? '' : ' not')
            ));
        }
    }
}
