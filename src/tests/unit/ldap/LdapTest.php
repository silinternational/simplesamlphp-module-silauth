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
            ['u' => 'LDAP_ACCESS',  'p' => 'ldap_access',  'expected' => true],
            ['u' => 'BOB_ADAMS',    'p' => 'bob_adams123', 'expected' => true],
            ['u' => '',             'p' => 'bob_adams123', 'expected' => false],
            ['u' => null,           'p' => 'bob_adams123', 'expected' => false],
            ['u' => 'BOB_ADAMS',    'p' => null,           'expected' => false],
            ['u' => 'BOB_ADAMS',    'p' => '',             'expected' => false],
            ['u' => '',             'p' => '',             'expected' => false],
            ['u' => null,           'p' => null,           'expected' => false],
            ['u' => null,           'p' => '',             'expected' => false],
            ['u' => '',             'p' => null,           'expected' => false],
            ['u' => 'ROB_HOLT',     'p' => 'rob_holt123',  'expected' => true],
            ['u' => 'NON_EXISTENT', 'p' => 'bob_adams123', 'expected' => false],
        ];
        $ldap = new Ldap();
        foreach ($testCases as $testCase) {
            $userCn = $testCase['u'];
            $password = $testCase['p'];
            
            // Act:
            $actual = $ldap->isPasswordCorrectForUser($userCn, $password);
            
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
