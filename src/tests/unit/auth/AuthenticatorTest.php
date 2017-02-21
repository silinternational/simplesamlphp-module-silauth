<?php
namespace Sil\SilAuth\tests\unit\auth;

use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\time\UtcTime;
use PHPUnit\Framework\TestCase;

class AuthenticatorTest extends TestCase
{
    public function testCalculateBlockUntilUtc()
    {
        // Arrange:
        $testCases = [
            [
                'afterNthFailedLogin' => 0,
                'blockUntilShouldBeNull' => true,
            ], [
                'afterNthFailedLogin' => Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN - 1,
                'blockUntilShouldBeNull' => true,
            ], [
                'afterNthFailedLogin' => Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN,
                'blockUntilShouldBeNull' => false,
            ], [
                'afterNthFailedLogin' => Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN + 1,
                'blockUntilShouldBeNull' => false,
            ],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = Authenticator::calculateBlockUntilUtc(
                $testCase['afterNthFailedLogin']
            );
            
            // Assert:
            $this->assertSame($testCase['blockUntilShouldBeNull'], is_null($actual));
        }
    }
    
    public function testCalculateBlockUntilUtcMaxDelay()
    {
        // Arrange:
        $expected = Authenticator::MAX_SECONDS_TO_BLOCK;
        $nowUtc = new UtcTime();
        
        // Act:
        $blockUntilUtcString = Authenticator::calculateBlockUntilUtc(100);
        $blockUntilUtcTime = new UtcTime($blockUntilUtcString);
        $actual = $nowUtc->getSecondsUntil($blockUntilUtcTime);
        
        // Assert:
        $this->assertEquals($expected, $actual, sprintf(
            'Maximum delay should be no more than %s seconds (not %s).',
            $expected,
            $actual
        ), 1);
    }
    
    public function testCalculateSecondsToDelay()
    {
        // Arrange:
        $testCases = [
            ['failedLoginAttempts' => 0, 'expected' => 0],
            ['failedLoginAttempts' => 1, 'expected' => 1],
            ['failedLoginAttempts' => 5, 'expected' => 25],
            ['failedLoginAttempts' => 6, 'expected' => 36],
            ['failedLoginAttempts' => 10, 'expected' => 100],
            ['failedLoginAttempts' => 20, 'expected' => 400],
            ['failedLoginAttempts' => 50, 'expected' => 2500],
            ['failedLoginAttempts' => 60, 'expected' => 3600],
            ['failedLoginAttempts' => 61, 'expected' => 3600],
            ['failedLoginAttempts' => 100, 'expected' => 3600],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = Authenticator::calculateSecondsToDelay(
                $testCase['failedLoginAttempts']
            );
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual, sprintf(
                'Expected %s failed login attempts to result in %s second(s), not %s.',
                var_export($testCase['failedLoginAttempts'], true),
                var_export($testCase['expected'], true),
                var_export($actual, true)
            ));
        }
    }
    
    public function testIsEnoughFailedLoginsToBlock()
    {
        // Arrange:
        $testCases = [
            ['expected' => false, 'failedLogins' => 0],
            ['expected' => false, 'failedLogins' => Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN - 1],
            ['expected' => true, 'failedLogins' => Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN],
            ['expected' => true, 'failedLogins' => Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN + 1],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = Authenticator::isEnoughFailedLoginsToBlock(
                $testCase['failedLogins']
            );
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual);
        }
    }
}
