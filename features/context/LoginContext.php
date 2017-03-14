<?php
namespace Sil\SilAuth\features\context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use PHPUnit_Framework_Assert as Assert;
use Psr\Log\LoggerInterface;
use Sil\PhpEnv\Env;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\auth\IdBroker;
use Sil\SilAuth\captcha\Captcha;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\http\Request;
use Sil\SilAuth\log\Psr3ConsoleLogger;
use Sil\SilAuth\models\FailedLoginIpAddress;
use Sil\SilAuth\models\FailedLoginUsername;
use Sil\SilAuth\tests\fakes\FakeFailedIdBroker;
use Sil\SilAuth\tests\fakes\FakeInvalidIdBroker;
use Sil\SilAuth\tests\fakes\FakeSuccessfulIdBroker;
use Sil\SilAuth\tests\unit\captcha\DummyFailedCaptcha;
use Sil\SilAuth\tests\unit\captcha\DummySuccessfulCaptcha;
use Sil\SilAuth\tests\unit\http\DummyRequest;
use Sil\SilAuth\time\UtcTime;
//use yii\helpers\ArrayHelper;

/**
 * Defines application features from the specific context.
 */
class LoginContext implements Context
{
    /** @var Authenticator|null */
    private $authenticator = null;

    /** @var LoggerInterface */
    private $logger;

    /** @var Captcha */
    private $captcha;
    
    /** @var IdBroker */
    private $idBroker;
    
    /** @var string|null */
    private $password = null;
    
    /** @var Request */
    private $request;
    
    /** @var string|null */
    private $username = null;
    
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        ConfigManager::initializeYii2WebApp(['components' => ['db' => [
            'dsn' => sprintf(
                'mysql:host=%s;dbname=%s',
                Env::get('MYSQL_HOST'),
                Env::get('MYSQL_DATABASE')
            ),
            'username' => Env::get('MYSQL_USER'),
            'password' => Env::get('MYSQL_PASSWORD'),
        ]]]);
        
        $this->logger = new Psr3ConsoleLogger();
        
        $this->captcha = new Captcha();
        $this->idBroker = new IdBroker(
            'http://fake.example.com/api/',
            'FakeAccessToken',
            $this->logger
        );
        $this->request = new Request();
        
        $this->resetDatabase();
    }
    
    protected function addXFailedLoginUsernames(int $number, $username)
    {
        Assert::assertNotEmpty($username);
        
        for ($i = 0; $i < $number; $i++) {
            $newRecord = new FailedLoginUsername(['username' => $username]);
            Assert::assertTrue($newRecord->save());
        }
        
        Assert::assertCount(
            $number,
            FailedLoginUsername::getFailedLoginsFor($username)
        );
    }
    
    protected function login()
    {
        $this->authenticator = new Authenticator(
            $this->username,
            $this->password,
            $this->request,
            $this->captcha,
            $this->idBroker,
            $this->logger
        );
    }
    
    protected function loginXTimes($numberOfTimes)
    {
        for ($i = 0; $i < $numberOfTimes; $i++) {
            $this->login();
        }   
    }
    
    protected function resetDatabase()
    {
        FailedLoginIpAddress::deleteAll();
        FailedLoginUsername::deleteAll();
    }
    
    /**
     * @Given I provide a username
     */
    public function iProvideAUsername()
    {
        $this->username = 'a username';
    }

    /**
     * @Given I provide a password
     */
    public function iProvideAPassword()
    {
        $this->password = 'a password';
    }

    /**
     * @When I try to log in
     */
    public function iTryToLogIn()
    {
        $this->login();
    }

    /**
     * @Then I should not be allowed through
     */
    public function iShouldNotBeAllowedThrough()
    {
        Assert::assertFalse(
            $this->authenticator->isAuthenticated()
        );
        try {
            $this->authenticator->getUserAttributes();
            Assert::fail();
        } catch (\Exception $e) {
            Assert::assertNotEmpty($e->getMessage());
        }
    }

    /**
     * @Given I do not provide a username
     */
    public function iDoNotProvideAUsername()
    {
        $this->username = '';
    }

    /**
     * @Then I should see an error message with :text in it
     */
    public function iShouldSeeAnErrorMessageWithInIt($text)
    {
        $authError = $this->authenticator->getAuthError();
        Assert::assertNotEmpty($authError);
        Assert::assertContains($text, (string)$authError);
    }

    /**
     * @Given I do not provide a password
     */
    public function iDoNotProvideAPassword()
    {
        $this->password = '';
    }

    /**
     * @Given I fail the captcha
     */
    public function iFailTheCaptcha()
    {
        $this->captcha = new DummyFailedCaptcha();
    }

    /**
     * @Then I should see a generic invalid-login error message
     */
    public function iShouldSeeAGenericInvalidLoginErrorMessage()
    {
        $authError = $this->authenticator->getAuthError();
        Assert::assertNotEmpty($authError);
        Assert::assertContains('invalid_login', (string)$authError);
    }

    /**
     * @Given I provide a username of :username
     */
    public function iProvideAUsernameOf($username)
    {
        $this->username = $username;
    }

    /**
     * @Then I should see an error message telling me to wait
     */
    public function iShouldSeeAnErrorMessageTellingMeToWait()
    {
        $authError = $this->authenticator->getAuthError();
        Assert::assertNotEmpty($authError);
        Assert::assertContains('rate_limit', (string)$authError);
    }

    /**
     * @Given I provide an incorrect password
     */
    public function iProvideAnIncorrectPassword()
    {
        $this->password = 'dummy incorrect password';
        $this->idBroker = new FakeFailedIdBroker('fake', 'fake', $this->logger);
    }

    /**
     * @Given that username will be rate limited after one more failed attempt
     */
    public function thatUsernameWillBeRateLimitedAfterOneMoreFailedAttempt()
    {
        FailedLoginUsername::resetFailedLoginsBy($this->username);
        
        $this->addXFailedLoginUsernames(
            Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN - 1,
            $this->username
        );
    }

    /**
     * @Given I (then) provide the correct password for that username
     */
    public function iProvideTheCorrectPasswordForThatUsername()
    {
        Assert::assertNotEmpty($this->username);
        $this->password = 'dummy correct password';
        $this->idBroker = new FakeSuccessfulIdBroker('fake', 'fake', $this->logger);
    }

    /**
     * @Then I should not see an error message
     */
    public function iShouldNotSeeAnErrorMessage()
    {
        $authError = $this->authenticator->getAuthError();
        Assert::assertEmpty(
            $authError,
            "Unexpected error: \n- " . $authError
        );
    }

    /**
     * @Then I should be allowed through
     */
    public function iShouldBeAllowedThrough()
    {
        Assert::assertTrue(
            $this->authenticator->isAuthenticated()
        );
        $userInfo = $this->authenticator->getUserAttributes();
        Assert::assertNotEmpty($userInfo);
    }

    /**
     * @When I try to log in enough times to trigger the rate limit
     */
    public function iTryToLogInEnoughTimesToTriggerTheRateLimit()
    {
        $this->loginXTimes(
            Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN
        );
    }

    /**
     * @Given that username has :number recent failed logins
     */
    public function thatUsernameHasRecentFailedLogins($number)
    {
        Assert::assertTrue(is_numeric($number));
        
        FailedLoginUsername::resetFailedLoginsBy($this->username);
        
        $this->addXFailedLoginUsernames($number, $this->username);
    }

    /**
     * @Then I should see an error message with :text1 and :text2 in it
     */
    public function iShouldSeeAnErrorMessageWithAndInIt($text1, $text2)
    {
        $authError = $this->authenticator->getAuthError();
        Assert::assertNotEmpty($authError);
        $authErrorString = (string)$authError;
        Assert::assertContains($text1, $authErrorString);
        Assert::assertContains($text2, $authErrorString);
    }

    /**
     * @Given that username has enough failed logins to require a captcha
     */
    public function thatUsernameHasEnoughFailedLoginsToRequireACaptcha()
    {
        FailedLoginUsername::resetFailedLoginsBy($this->username);
        
        $this->addXFailedLoginUsernames(
            Authenticator::REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN,
            $this->username
        );
    }
    
    /**
     * @Given that username has no recent failed login attempts
     */
    public function thatUsernameHasNoRecentFailedLoginAttempts()
    {
        Assert::assertNotEmpty($this->username);
        Assert::assertEquals(
            0,
            FailedLoginUsername::countRecentFailedLoginsFor($this->username)
        );
    }

    /**
     * @Then that username should be blocked for awhile
     */
    public function thatUsernameShouldBeBlockedForAwhile()
    {
        Assert::assertNotEmpty($this->username);
        Assert::assertTrue(
            FailedLoginUsername::isRateLimitBlocking($this->username)
        );
    }

    /**
     * @Given my request comes from IP address :ipAddress
     */
    public function myRequestComesFromIpAddress($ipAddress)
    {
        if ( ! $this->request instanceof DummyRequest) {
            $this->request = new DummyRequest();
        }
        
        $this->request->setDummyIpAddress($ipAddress);
    }

    /**
     * @Then that IP address should be blocked for awhile
     */
    public function thatIpAddressShouldBeBlockedForAwhile()
    {
        $ipAddresses = $this->request->getUntrustedIpAddresses();
        Assert::assertCount(1, $ipAddresses);
        $ipAddress = $ipAddresses[0];
        
        Assert::assertTrue(
            FailedLoginIpAddress::isRateLimitBlocking($ipAddress)
        );
    }

    /**
     * @Then that username's failed login attempts should be at :number
     */
    public function thatUsernameSFailedLoginAttemptsShouldBeAt($number)
    {
        Assert::assertNotEmpty($this->username);
        Assert::assertTrue(is_numeric($number));
        Assert::assertCount(
            (int)$number,
            FailedLoginUsername::getFailedLoginsFor($this->username)
        );
    }

    /**
     * @Given that username does not have enough failed logins to require a captcha
     */
    public function thatUsernameDoesNotHaveEnoughFailedLoginsToRequireACaptcha()
    {
        Assert::assertNotEmpty($this->username);
        FailedLoginUsername::deleteAll();
        Assert::assertEmpty(FailedLoginUsername::getFailedLoginsFor($this->username));
    }

    /**
     * @Given my IP address has enough failed logins to require a captcha
     */
    public function myIpAddressHasEnoughFailedLoginsToRequireACaptcha()
    {
        $ipAddress = $this->request->getMostLikelyIpAddress();
        Assert::assertNotNull($ipAddress, 'No IP address was provided.');
        FailedLoginIpAddress::deleteAll();
        Assert::assertEmpty(FailedLoginIpAddress::getFailedLoginsFor($ipAddress));
        
        $desiredCount = Authenticator::REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN;
        
        for ($i = 0; $i < $desiredCount; $i++) {
            $failedLoginUsername = new FailedLoginIpAddress([
                'ip_address' => $ipAddress,
            ]);
            Assert::assertTrue($failedLoginUsername->save());
        }
        
        Assert::assertEquals(
            Authenticator::REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN,
            FailedLoginIpAddress::countRecentFailedLoginsFor($ipAddress)
        );
    }

    /**
     * @Given that username has enough failed logins to be blocked by the rate limit
     */
    public function thatUsernameHasEnoughFailedLoginsToBeBlockedByTheRateLimit()
    {
        FailedLoginUsername::resetFailedLoginsBy($this->username);
        
        $this->addXFailedLoginUsernames(
            Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN,
            $this->username
        );
    }

    /**
     * @Given that IP address has triggered the rate limit
     */
    public function thatIpAddressHasTriggeredTheRateLimit()
    {
        $ipAddresses = $this->request->getUntrustedIpAddresses();
        Assert::assertCount(1, $ipAddresses);
        $ipAddress = $ipAddresses[0];
        
        FailedLoginIpAddress::deleteAll();
        Assert::assertEmpty(FailedLoginIpAddress::getFailedLoginsFor($ipAddress));
        
        $desiredCount = Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN;
        
        for ($i = 0; $i < $desiredCount; $i++) {
            $failedLoginIpAddress = new FailedLoginIpAddress([
                'ip_address' => $ipAddress,
            ]);
            Assert::assertTrue($failedLoginIpAddress->save());
        }
        
        Assert::assertTrue(
            FailedLoginIpAddress::isRateLimitBlocking($ipAddress)
        );
    }

    /**
     * @Given /^I pass (the|any) captchas?$/
     */
    public function iPassTheCaptcha()
    {
        $this->captcha = new DummySuccessfulCaptcha();
    }

    /**
     * @Given that username has :number non-recent failed logins
     */
    public function thatUsernameHasNonRecentFailedLogins($number)
    {
        Assert::assertNotEmpty($this->username);
        Assert::assertTrue(is_numeric($number));
        
        $numTotalFailures = count(FailedLoginUsername::getFailedLoginsFor($this->username));
        $numRecentFailures = FailedLoginUsername::countRecentFailedLoginsFor($this->username);
        $numNonRecentFailures = $numTotalFailures - $numRecentFailures;
        
        for ($i = $numNonRecentFailures; $i < $number; $i++) {
            $failedLoginUsername = new FailedLoginUsername([
                'username' => $this->username,
                
                // NOTE: Use some time (UTC) longer ago than we consider "recent".
                'occurred_at_utc' => new UtcTime('-1 month'),
            ]);
            // NOTE: Don't validate, as that would overwrite the datetime field.
            Assert::assertTrue($failedLoginUsername->save(false));
        }
        
        $numTotalFailuresPost = count(FailedLoginUsername::getFailedLoginsFor($this->username));
        $numRecentFailuresPost = FailedLoginUsername::countRecentFailedLoginsFor($this->username);
        $numNonRecentFailuresPost = $numTotalFailuresPost - $numRecentFailuresPost;
        
        Assert::assertEquals($number, $numNonRecentFailuresPost);
    }

    /**
     * @Then I should not have to pass a captcha test for that user
     */
    public function iShouldNotHaveToPassACaptchaTestForThatUser()
    {
        Assert::assertNotEmpty($this->username);
        Assert::assertFalse(
            FailedLoginUsername::isCaptchaRequiredFor($this->username)
        );
    }

    /**
     * @Given :ipAddress is a trusted IP address
     */
    public function isATrustedIpAddress($ipAddress)
    {
        $this->request->trustIpAddress($ipAddress);
    }

    /**
     * @Then the IP address :ipAddress should not have any failed login attempts
     */
    public function theIpAddressShouldNotHaveAnyFailedLoginAttempts($ipAddress)
    {
        Assert::assertTrue(Request::isValidIpAddress($ipAddress));
        Assert::assertEmpty(FailedLoginIpAddress::getFailedLoginsFor($ipAddress));
    }

    /**
     * @Given the ID Broker is returning invalid responses
     */
    public function theIdBrokerIsReturningInvalidResponses()
    {
        $this->idBroker = new FakeInvalidIdBroker('fake', 'fake', $this->logger);
    }

    /**
     * @Then I should see a generic try-later error message
     */
    public function iShouldSeeAGenericTryLaterErrorMessage()
    {
        $authError = $this->authenticator->getAuthError();
        Assert::assertNotEmpty($authError);
        Assert::assertContains('later', (string)$authError);
    }
}
