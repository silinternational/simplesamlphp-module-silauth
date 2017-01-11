<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\time\WaitTime;
use PHPUnit\Framework\TestCase;

class WaitTimeTest extends TestCase
{
    public function testGetFriendlyWaitTimeFor()
    {
        // Arrange:
        $testCases = [
            ['secondsToWait' => 0, 'expected' => '5 seconds'],
            ['secondsToWait' => 1, 'expected' => '5 seconds'],
            ['secondsToWait' => 5, 'expected' => '5 seconds'],
            ['secondsToWait' => 6, 'expected' => '10 seconds'],
            ['secondsToWait' => 17, 'expected' => '20 seconds'],
            ['secondsToWait' => 22, 'expected' => '30 seconds'],
            ['secondsToWait' => 41, 'expected' => '1 minute'],
            ['secondsToWait' => 90, 'expected' => '2 minutes'],
        ];
        foreach ($testCases as $testCase) {
            $waitTime = new WaitTime($testCase['secondsToWait']);
            
            // Act:
            $actual = (string)$waitTime;
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual, sprintf(
                'Expected %s second(s) to result in %s, not %s.',
                var_export($testCase['secondsToWait'], true),
                var_export($testCase['expected'], true),
                var_export($actual, true)
            ));
        }
    }
}
