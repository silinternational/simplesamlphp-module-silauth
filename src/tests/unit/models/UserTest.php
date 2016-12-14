<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCalculateBlockUntilUtc()
    {
        // Arrange:
        $testCases = [
            [
                'afterNthFailedLogin' => 0,
                'blockUntilShouldBeNull' => true,
            ], [
                'afterNthFailedLogin' => User::BLOCK_AFTER_NTH_FAILED_LOGIN - 1,
                'blockUntilShouldBeNull' => true,
            ], [
                'afterNthFailedLogin' => User::BLOCK_AFTER_NTH_FAILED_LOGIN,
                'blockUntilShouldBeNull' => false,
            ], [
                'afterNthFailedLogin' => User::BLOCK_AFTER_NTH_FAILED_LOGIN + 1,
                'blockUntilShouldBeNull' => false,
            ],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = User::calculateBlockUntilUtc($testCase['afterNthFailedLogin']);
            
            // Assert:
            $this->assertSame($testCase['blockUntilShouldBeNull'], is_null($actual));
        }
    }
    
    public function testChangingAnExistingUuid()
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
            'Failed to create User for test: %s',
            print_r($user->getErrors(), true)
        ));
        $this->assertTrue($user->refresh());
        
        // Act:
        $user->uuid = User::generateUuid();
        
        // Assert:
        $this->assertFalse($user->validate(['uuid']));
    }
    
    public function testGenerateUuid()
    {
        // Arrange: (n/a)
        
        // Act:
        $uuid = User::generateUuid();
        
        // Assert:
        $this->assertNotEmpty($uuid);
    }
    
    public function testIsBlockedByRateLimit()
    {
        // Arrange:
        $utc = new \DateTimeZone('UTC');
        $yesterday = new \DateTime('yesterday', $utc);
        $tomorrow = new \DateTime('tomorrow', $utc);
        $testCases = [
            [
                'attributes' => [
                    'block_until_utc' => null,
                ],
                'expectedResult' => false,
            ], [
                'attributes' => [
                    'block_until_utc' => $yesterday->format(User::TIME_FORMAT),
                ],
                'expectedResult' => false,
            ], [
                'attributes' => [
                    'block_until_utc' => $tomorrow->format(User::TIME_FORMAT),
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
        $valueBefore = $user->login_attempts;
        $this->assertNotNull($valueBefore);
        
        // Act:
        $user->recordLoginAttemptInDatabase();
        $user->refresh();
        
        // Assert:
        $this->assertSame($valueBefore + 1, $user->login_attempts, sprintf(
            'The value after (%s) was not 1 more than the value before (%s).',
            var_export($user->login_attempts, true),
            var_export($valueBefore, true)
        ));
    }
}
