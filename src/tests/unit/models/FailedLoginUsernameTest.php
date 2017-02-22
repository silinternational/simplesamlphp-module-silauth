<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\models\FailedLoginUsername;
use Sil\SilAuth\time\UtcTime;
use PHPUnit\Framework\TestCase;

class FailedLoginUsernameTest extends TestCase
{
    protected function setDbFixture($recordsData)
    {
        FailedLoginUsername::deleteAll();
        foreach ($recordsData as $recordData) {
            $model = new FailedLoginUsername($recordData);
            $this->assertTrue($model->insert(false));
        }
    }
    
    public function testCountRecentFailedLoginsFor()
    {
        // Arrange:
        $username = 'john_smith';
        $fixtures = [[
            'username' => $username,
            'occurred_at_utc' => UtcTime::format('-61 minutes'), // Not recent.
        ], [
            'username' => $username,
            'occurred_at_utc' => UtcTime::format('-59 minutes'), // Recent.
        ], [
            'username' => $username,
            'occurred_at_utc' => UtcTime::format(), // Now (thus, recent).
        ]];
        $this->setDbFixture($fixtures);
        
        // Pre-assert:
        $this->assertCount(
            count($fixtures),
            FailedLoginUsername::getFailedLoginsFor($username)
        );

        // Act:
        $result = FailedLoginUsername::countRecentFailedLoginsFor($username);

        // Assert:
        $this->assertEquals(2, $result);
    }
    public function testIsRateLimitBlocking()
    {
        // Arrange:
        $testCases = [[
            'dbFixture' => [
                ['username' => 'dummy_username', 'occurred_at_utc' => UtcTime::now()],
                ['username' => 'dummy_username', 'occurred_at_utc' => UtcTime::now()],
                ['username' => 'dummy_username', 'occurred_at_utc' => UtcTime::now()],
            ],
            'username' => 'dummy_username',
            'expected' => true,
        ], [
            'dbFixture' => [
                ['username' => 'dummy_other_username', 'occurred_at_utc' => UtcTime::now()],
                ['username' => 'dummy_other_username', 'occurred_at_utc' => UtcTime::now()],
            ],
            'username' => 'dummy_other_username',
            'expected' => false,
        ]];
        foreach ($testCases as $testCase) {
            $this->setDbFixture($testCase['dbFixture']);

            // Act:
            $actual = FailedLoginUsername::isRateLimitBlocking($testCase['username']);

            // Assert:
            $this->assertSame($testCase['expected'], $actual);
        }
    }
    
}
