<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\time\UtcTime;
use PHPUnit\Framework\TestCase;

class UtcTimeTest extends TestCase
{
    public function testFormat()
    {
        // Arrange:
        $testCases = [
            [
                'dateTimeString' => '1 Jan 2000 00:00:00 -0000',
                'expected' => '2000-01-01 00:00:00',
            ], [
                'dateTimeString' => '2016-Dec-25 12:00pm',
                'expected' => '2016-12-25 12:00:00',
            ],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = UtcTime::format($testCase['dateTimeString']);
            
            // Assert:
            $this->assertSame($testCase['expected'], $actual);
        }
    }
    
    public function testGetSecondsSinceDateTime()
    {
        // Arrange:
        $testCases = [
            ['value' => '1970-01-01 00:00:00', 'expected' => time()],
            ['value' => UtcTime::format(), 'expected' => 0],
            ['value' => UtcTime::format('-10 seconds'), 'expected' => 10],
            ['value' => UtcTime::format('-2 hours'), 'expected' => 7200],
        ];
        foreach ($testCases as $testCase) {
            
            // Act:
            $actual = UtcTime::getSecondsSinceDateTime($testCase['value']);
            
            // Assert:
            $this->assertEquals($testCase['expected'], $actual, sprintf(
                'Expected %s to result in %s, not %s.',
                var_export($testCase['value'], true),
                var_export($testCase['expected'], true),
                var_export($actual, true)
            ), 1);
        }
    }
    
    public function testGetSecondsUntil()
    {
        // Arrange:
        $dayOneString = 'Tue, 13 Dec 2016 00:00:00 -0500';
        $dayTwoString = 'Wed, 14 Dec 2016 00:00:00 -0500';
        $expected = 86400; // 86400 = seconds in a day
        $dayOneUtcTime = new UtcTime($dayOneString);
        $dayTwoUtcTime = new UtcTime($dayTwoString);
        
        // Act:
        $actual = $dayOneUtcTime->getSecondsUntil($dayTwoUtcTime);
        
        // Assert:
        $this->assertSame($expected, $actual);
    }
    
    public function testGetTimestamp()
    {
        // Arrange:
        $timestamp = time();
        $utcTime = new UtcTime(date('r', $timestamp));
        
        // Act:
        $result = $utcTime->getTimestamp();
        
        // Assert:
        $this->assertSame($timestamp, $result);
    }
}
