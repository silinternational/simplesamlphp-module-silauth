<?php
namespace Sil\SilAuth\tests\unit\log;

use PHPUnit\Framework\TestCase;
use Sil\SilAuth\log\Psr3SamlLogger;

class Psr3SamlLoggerTest extends TestCase
{
    public function testLogUnknownLogLevel()
    {
        // Arrange:
        $psr3SamlLogger = new Psr3SamlLogger();
        $psrLevel = 'unknown'; // Some unknown log level.
        
        // Pre-assert:
        $this->expectException('\Psr\Log\InvalidArgumentException');
        
        // Act:
        $psr3SamlLogger->log($psrLevel, 'Some message');
    }
}
