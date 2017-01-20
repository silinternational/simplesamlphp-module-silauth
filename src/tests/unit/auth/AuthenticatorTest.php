<?php
namespace Sil\SilAuth\tests\unit\auth;

use Sil\SilAuth\auth\Authenticator;
use PHPUnit\Framework\TestCase;

class AuthenticatorTest extends TestCase
{
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
