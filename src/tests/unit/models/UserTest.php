<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testChangingAnExistingUuid()
    {
        // Arrange:
        $user = new User();
        
        // Pre-assert:
        $this->assertTrue($user->save());
        $this->assertTrue($user->refresh());
        
        // Act:
        $user->uuid = User::generateUuid();
        
        // Assert:
        $this->assertFalse($user->validate(['uuid']));
    }
    
    public function testGenerateUuid()
    {
        // Arrange: (n/a)
        
        // Act:
        $uuid = User::generateUuid();
        
        // Assert:
        $this->assertNotEmpty($uuid);
    }
    
    public function testValidationRules()
    {
        // Arrange:
        $testCases = [
        ];
        foreach ($testCases as $testName => $testData) {

            // Act:
            $user = new User();
            $user->attributes = $testData['attributes'];

            // Assert:
            $this->assertSame($testData['expected'], $user->validate(), sprintf(
                'Incorrectly %s a User with %s.',
                ($testData['expected'] ? 'rejected' : 'allowed'),
                $testName
            ));
        }
    }
}
