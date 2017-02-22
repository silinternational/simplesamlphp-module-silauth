<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\log\Psr3ConsoleLogger;
use Sil\SilAuth\models\FailedLoginIpAddress;
use Sil\SilAuth\time\UtcTime;
use PHPUnit\Framework\TestCase;

class FailedLoginIpAddressTest extends TestCase
{
    protected function setDbFixture($recordsData)
    {
        FailedLoginIpAddress::deleteAll();
        foreach ($recordsData as $recordData) {
            $model = new FailedLoginIpAddress($recordData);
            $this->assertTrue($model->insert(false));
        }
    }
    
    public function testGetMostRecentFailedLoginFor()
    {
        // Arrange:
        $ipAddress = '100.110.120.130';
        $nowDateTimeString = UtcTime::now();
        $fixtures = [[
            'ip_address' => $ipAddress,
            'occurred_at_utc' => UtcTime::format('-61 minutes'),
        ], [
            'ip_address' => $ipAddress,
            'occurred_at_utc' => $nowDateTimeString,
        ], [
            'ip_address' => $ipAddress,
            'occurred_at_utc' => UtcTime::format('-59 minutes'),
        ]];
        $this->setDbFixture($fixtures);
        
        // Act:
        $fliaRecord = FailedLoginIpAddress::getMostRecentFailedLoginFor($ipAddress);

        // Assert:
        $this->assertSame($nowDateTimeString, $fliaRecord->occurred_at_utc);
    }
    
    public function testIsCaptchaRequiredFor()
    {
        // Arrange:
        $testCases = [[
            'dbFixture' => [
                ['ip_address' => '11.11.11.11', 'occurred_at_utc' => UtcTime::now()],
                ['ip_address' => '11.11.11.11', 'occurred_at_utc' => UtcTime::now()],
            ],
            'ipAddress' => '11.11.11.11',
            'expected' => true,
        ], [
            'dbFixture' => [
                ['ip_address' => '22.22.22.22', 'occurred_at_utc' => UtcTime::now()],
            ],
            'ipAddress' => '22.22.22.22',
            'expected' => false,
        ]];
        foreach ($testCases as $testCase) {
            $this->setDbFixture($testCase['dbFixture']);

            // Act:
            $actual = FailedLoginIpAddress::isCaptchaRequiredFor($testCase['ipAddress']);

            // Assert:
            $this->assertSame($testCase['expected'], $actual);
        }
    }
    
}
