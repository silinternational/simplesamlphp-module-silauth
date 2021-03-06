<?php
namespace Sil\SilAuth\tests\unit\csrf;

use Sil\SilAuth\csrf\CsrfProtector;
use Sil\SilAuth\tests\unit\csrf\FakeSession;
use PHPUnit\Framework\TestCase;

class CsrfProtectorTest extends TestCase
{
    public function testChangeMasterToken()
    {
        // Arrange:
        $csrfProtector = new CsrfProtector(FakeSession::getSession());
        $firstToken = $csrfProtector->getMasterToken();
        $firstTokenAgain = $csrfProtector->getMasterToken();
        
        // Act:
        $csrfProtector->changeMasterToken();
        $secondToken = $csrfProtector->getMasterToken();
        $secondTokenAgain = $csrfProtector->getMasterToken();
        
        // Assert:
        $this->assertSame($firstToken, $firstTokenAgain);
        $this->assertNotEquals($firstToken, $secondToken);
        $this->assertSame($secondToken, $secondTokenAgain);
    }
}
