<?php
namespace Sil\SilAuth\tests\unit\config;

use Sil\SilAuth\config\ConfigManager;
use PHPUnit\Framework\TestCase;

class ConfigManagerTest extends TestCase
{
    public function testGetSspConfig()
    {
        // Arrange: (n/a)
        
        // Act:
        $sspConfig = ConfigManager::getSspConfig();
        
        // Assert:
        $this->assertTrue(is_array($sspConfig), sprintf(
            'Expected an array, got this: %s',
            var_export($sspConfig, true)
        ));
    }
    
    public function testGetSspConfigFor()
    {
        // Arrange:
        $category = 'ldap';
        
        // Act:
        $result = ConfigManager::getSspConfigFor($category);
        
        // Assert:
        $this->assertArrayHasKey('use_tls', $result);
    }
}
