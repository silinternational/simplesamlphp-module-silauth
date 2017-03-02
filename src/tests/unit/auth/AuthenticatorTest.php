<?php
namespace Sil\SilAuth\tests\unit\auth;

use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\time\UtcTime;
use PHPUnit\Framework\TestCase;

class AuthenticatorTest extends TestCase
{
    public function testCalculateSecondsToDelay()
    {
        // Arrange:
        $blockAfterNth = Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN;
        $testCases = [
            ['failedLoginAttempts' => 0, 'expected' => 0],
            ['failedLoginAttempts' => $blockAfterNth - 1, 'expected' => 0],
            ['failedLoginAttempts' => $blockAfterNth, 'expected' => pow($blockAfterNth, 2)],
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
    
    public function testGetSecondsUntilUnblocked()
    {
        // Arrange:
        $testCases = [[
            'numRecentFailures' => 0,
            'mostRecentFailureAt' => null,
            'expected' => 0,
        ], [
            'numRecentFailures' => Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN - 1, // no delay yet
            'mostRecentFailureAt' => UtcTime::format('-5 seconds'),
            'expected' => 0,
        ], [
            'numRecentFailures' => 5, // a 25-second delay
            'mostRecentFailureAt' => UtcTime::format('-5 seconds'),
            'expected' => 20,
        ]];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = Authenticator::getSecondsUntilUnblocked(
                $testCase['numRecentFailures'],
                $testCase['mostRecentFailureAt']
            );
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual);
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
