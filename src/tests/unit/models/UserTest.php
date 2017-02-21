<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\models\User;
use Sil\SilAuth\time\UtcTime;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetSecondsUntilUnblocked()
    {
        // Arrange:
        $testCases = [
            ['blockUntilUtc' => null, 'expected' => 0],
            ['blockUntilUtc' => UtcTime::format('+8 seconds'), 'expected' => 8],
            ['blockUntilUtc' => UtcTime::format('+1 minute'), 'expected' => 60],
        ];
        foreach ($testCases as $testCase) {
            $user = new User();
            $user->block_until_utc = $testCase['blockUntilUtc'];
            
            // Act:
            $actual = $user->getSecondsUntilUnblocked();
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual);
        }
    }
    
    public function testIsBlockedByRateLimit()
    {
        // Arrange:
        $testCases = [
            [
                'attributes' => [
                    'block_until_utc' => null,
                ],
                'expectedResult' => false,
            ], [
                'attributes' => [
                    'block_until_utc' => UtcTime::format('yesterday'),
                ],
                'expectedResult' => false,
            ], [
                'attributes' => [
                    'block_until_utc' => UtcTime::format('tomorrow'),
                ],
                'expectedResult' => true,
            ],
        ];
        foreach ($testCases as $testCase) {
            $user = new User();
            $user->attributes = $testCase['attributes'];
            
            // Act:
            $actualResult = $user->isBlockedByRateLimit();
            
            // Assert:
            $this->assertSame($testCase['expectedResult'], $actualResult);
        }
    }
    
    public function testIsLocked()
    {
        // Arrange:
        $testCases = [
            ['value' => User::LOCKED_YES, 'expected' => true],
            ['value' => 'YES', 'expected' => true],
            ['value' => 'Yes', 'expected' => true],
            ['value' => 'yes', 'expected' => true],
            ['value' => User::LOCKED_NO, 'expected' => false],
            ['value' => 'NO', 'expected' => false],
            ['value' => 'No', 'expected' => false],
            ['value' => 'no', 'expected' => false],
            // Handle invalid types/values securely:
            ['value' => true, 'expected' => true],
            ['value' => 1, 'expected' => true],
            ['value' => false, 'expected' => true],
            ['value' => 0, 'expected' => true],
            ['value' => 'other', 'expected' => true],
            ['value' => '', 'expected' => true],
            ['value' => null, 'expected' => true],
        ];
        foreach ($testCases as $testCase) {
            $user = new User();
            $user->locked = $testCase['value'];
            
            // Act:
            $actual = $user->isLocked();
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual, sprintf(
                'A User with a "locked" value of %s should%s be considered locked.',
                var_export($user->locked, true),
                ($testCase['expected'] ? '' : ' not')
            ));
        }
    }
    
    public function testLastUpdatedUtc()
    {
        // Arrange:
        $uniqId = uniqid();
        $user = new User();
        $user->attributes = [
            'email' => $uniqId . '@example.com',
            'employee_id' => $uniqId,
            'first_name' => 'Test ' . $uniqId,
            'last_name' => 'User',
            'username' => 'user' . $uniqId,
        ];
        
        // Pre-assert:
        $this->assertTrue($user->save(), sprintf(
            'Failed to create User record for test: %s',
            print_r($user->getErrors(), true)
        ));
        $user->refresh();
        $lastUpdatedPre = $user->last_updated_utc;
        $this->assertNotNull($lastUpdatedPre);
        $user->last_updated_utc = UtcTime::format('-2 seconds');
        $this->assertTrue($user->save(false), sprintf(
            'Failed to set last_updated_time to the past for test: %s',
            print_r($user->getErrors(), true)
        ));
        $user->refresh();
        $lastUpdatedMid = $user->last_updated_utc;
        $this->assertNotSame($lastUpdatedPre, $lastUpdatedMid);
        
        // Act:
        $user->last_name = 'Something Else';
        
        // Assert:
        $this->assertTrue($user->save(), sprintf(
            'Failed to update User record for test: %s',
            print_r($user->getErrors(), true)
        ));
        $this->assertNotSame($lastUpdatedMid, $user->last_updated_utc, sprintf(
            'The last_updated_utc value (%s) should have changed, but did not.',
            var_export($user->last_updated_utc, true)
        ));
    }
    
    public function testIsPasswordRehashNeeded()
    {
        // Arrange:
        $testCases = [
            ['expected' => true, 'cost' => User::PASSWORD_HASH_DESIRED_COST - 3],
            ['expected' => true, 'cost' => User::PASSWORD_HASH_DESIRED_COST - 2],
            ['expected' => true, 'cost' => User::PASSWORD_HASH_DESIRED_COST - 1],
            ['expected' => false, 'cost' => User::PASSWORD_HASH_DESIRED_COST],
            ['expected' => true, 'cost' => User::PASSWORD_HASH_DESIRED_COST + 1],
        ];
        foreach ($testCases as $testCase) {
            $user = new User();
            $user->password_hash = password_hash('abc', PASSWORD_DEFAULT, [
                'cost' => $testCase['cost'],
            ]);
            
            // Act:
            $actual = $user->isPasswordRehashNeeded();

            // Assert:
            $this->assertSame($testCase['expected'], $actual, sprintf(
                'Expected password hash with a cost of %s would%s need '
                . 'rehashed when the desired cost is %s.',
                $testCase['cost'],
                ($testCase['expected'] ? '' : ' not'),
                User::PASSWORD_HASH_DESIRED_COST
            ));
        }
    }
    
    public function testRecordLoginAttemptInDatabase()
    {
        // Arrange:
        $uniqId = uniqid();
        $user = new User();
        $user->attributes = [
            'email' => $uniqId . '@example.com',
            'employee_id' => $uniqId,
            'first_name' => 'Test ' . $uniqId,
            'last_name' => 'User',
            'username' => 'user' . $uniqId,
        ];
        
        // Pre-assert:
        $this->assertTrue($user->save(), sprintf(
            'Failed to save User record for test: %s',
            print_r($user->getErrors(), true)
        ));
        $user->refresh();
        $loginAttemptsPre = $user->login_attempts;
        $this->assertNotNull($loginAttemptsPre);
        
        // Act:
        $user->recordLoginAttemptInDatabase();
        $user->refresh();
        
        // Assert:
        $this->assertSame($loginAttemptsPre + 1, $user->login_attempts, sprintf(
            'The value after (%s) was not 1 more than the value before (%s).',
            var_export($user->login_attempts, true),
            var_export($loginAttemptsPre, true)
        ));
    }
    
    public function testSetPassword()
    {
        // Arrange:
        $user = new User();
        $password = 'dummy-password';
        $user->setPassword($password);
        
        // Act:
        $result = $user->isPasswordRehashNeeded();
        
        // Assert:
        $this->assertFalse(
            $result,
            'Incorrectly said that a brand new password hash needs to be rehashed.'
        );
    }
}
