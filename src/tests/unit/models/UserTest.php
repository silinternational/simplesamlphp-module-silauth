<?php
namespace Sil\SilAuth\tests\unit\models;

use Sil\SilAuth\models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testValidationRules()
    {
        // Arrange:
        $testCases = [
            'no uuid' => [
                'attributes' => [
                    
                ],
                'expected' => false,
            ]
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
