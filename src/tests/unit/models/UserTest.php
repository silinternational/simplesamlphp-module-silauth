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
                'failedLogins' => 0,
                'isNull' => true,
            ], [
                'failedLogins' => User::MAX_FAILED_LOGINS_BEFORE_BLOCK - 1,
                'isNull' => true,
            ], [
                'failedLogins' => User::MAX_FAILED_LOGINS_BEFORE_BLOCK,
                'isNull' => true,
            ], [
                'failedLogins' => User::MAX_FAILED_LOGINS_BEFORE_BLOCK + 1,
                'isNull' => false,
            ],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = User::calculateBlockUntilUtc($testCase['failedLogins']);
            
            // Assert:
            $this->assertSame($testCase['isNull'], is_null($actual));
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
