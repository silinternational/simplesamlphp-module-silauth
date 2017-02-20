<?php
namespace Sil\SilAuth\tests\unit\time;

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
            ['secondsToWait' => 119, 'expected' => '2 minutes'],
            ['secondsToWait' => 120, 'expected' => '2 minutes'],
            ['secondsToWait' => 121, 'expected' => '3 minutes'],
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
    
    public function testGetLongerWaitTime()
    {
        // Arrange:
        $testCases = [
            ['a' => 0, 'b' => 0, 'expected' => new WaitTime(0)],
            ['a' => 0, 'b' => 1, 'expected' => new WaitTime(1)],
            ['a' => 1, 'b' => 0, 'expected' => new WaitTime(1)],
            ['a' => 5, 'b' => 6, 'expected' => new WaitTime(6)],
            ['a' => 6, 'b' => 5, 'expected' => new WaitTime(6)],
            ['a' => 0, 'b' => 17, 'expected' => new WaitTime(17)],
            ['a' => 17, 'b' => 5, 'expected' => new WaitTime(17)],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = WaitTime::getLongerWaitTime($testCase['a'], $testCase['b']);
            
            // Assert:
            $this->assertEquals($testCase['expected'], $actual, sprintf(
                'Expected the longer of %s and %s second(s) to be a wait time of %s, not %s.',
                var_export($testCase['a'], true),
                var_export($testCase['b'], true),
                $testCase['expected'],
                $actual
            ));
        }
    }
}
