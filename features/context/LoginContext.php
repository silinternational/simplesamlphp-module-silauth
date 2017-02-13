<?php
namespace Sil\SilAuth\features\context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use PHPUnit_Framework_Assert;
use Psr\Log\LoggerInterface;
use Sil\PhpEnv\Env;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\log\Psr3ConsoleLogger;
use Sil\SilAuth\models\User;
//use Sil\SilAuth\time\UtcTime;
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

    /** @var string|null */
    private $csrfToken = null;
    
    /** @var string|null */
    private $password = null;
    
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
    }
    
    protected function loginXTimes($numberOfTimes)
    {
        for ($i = 0; $i < $numberOfTimes; $i++) {
            $this->authenticator = new Authenticator(
                $this->username,
                $this->password,
                null,
                $this->logger
            );
        }   
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
     * @Given I do not provide a CSRF token
     */
    public function iDoNotProvideACsrfToken()
    {
        $this->csrfToken = '';
    }

    /**
     * @When I try to log in
     */
    public function iTryToLogIn()
    {
        $this->authenticator = new Authenticator(
            $this->username,
            $this->password,
            null,
            $this->logger
        );
    }

    /**
     * @Then I should not be allowed through
     */
    public function iShouldNotBeAllowedThrough()
    {
        PHPUnit_Framework_Assert::assertFalse(
            $this->authenticator->isAuthenticated()
        );
        try {
            $this->authenticator->getUserAttributes();
            PHPUnit_Framework_Assert::fail();
        } catch (\Exception $e) {
            PHPUnit_Framework_Assert::assertNotEmpty($e->getMessage());
        }
    }

    /**
     * @Given I provide an incorrect CSRF token
     */
    public function iProvideAnIncorrectCsrfToken()
    {
        $this->csrfToken = 'thisIsWrongAsdfasdfasdf';
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
        PHPUnit_Framework_Assert::assertNotEmpty($authError);
        PHPUnit_Framework_Assert::assertContains($text, (string)$authError);
    }

    /**
     * @Given I do not provide a password
     */
    public function iDoNotProvideAPassword()
    {
        $this->password = '';
    }

    /**
     * @Given a captcha is required for that username
     */
    public function aCaptchaIsRequiredForThatUsername()
    {
        PHPUnit_Framework_Assert::assertNotEmpty($this->username);
        PHPUnit_Framework_Assert::assertTrue(
            User::isCaptchaRequiredFor($this->username)
        );
    }

    /**
     * @Given I fail the captcha
     */
    public function iFailTheCaptcha()
    {
        throw new PendingException();
    }

    /**
     * @Then I should see a generic invalid-login error message
     */
    public function iShouldSeeAGenericInvalidLoginErrorMessage()
    {
        $authError = $this->authenticator->getAuthError();
        PHPUnit_Framework_Assert::assertNotEmpty($authError);
        PHPUnit_Framework_Assert::assertContains('invalid_login', (string)$authError);
    }

    /**
     * @Given the username :username has triggered the rate limit
     */
    public function theUsernameHasTriggeredTheRateLimit($username)
    {
        throw new PendingException();
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
        PHPUnit_Framework_Assert::assertNotEmpty($authError);
        PHPUnit_Framework_Assert::assertContains('rate_limit', (string)$authError);
    }

    /**
     * @Then that user account should (still) be blocked for awhile
     */
    public function thatUserAccountShouldBeBlockedForAwhile()
    {
        $user = User::findByUsername($this->username);
        PHPUnit_Framework_Assert::assertTrue(
            $user->isBlockedByRateLimit()
        );
    }

    /**
     * @Given I provide an incorrect password
     */
    public function iProvideAnIncorrectPassword()
    {
        $this->password = 'ThisIsWrong';
    }

    /**
     * @Given that username will be rate limited after one more failed attempt
     */
    public function thatUsernameWillBeRateLimitedAfterOneMoreFailedAttempt()
    {
        throw new PendingException();
    }

    /**
     * @Given I (then) provide the correct password for that username
     */
    public function iProvideTheCorrectPasswordForThatUsername()
    {
        PHPUnit_Framework_Assert::assertNotEmpty($this->username);
        $this->password = $this->username . '123'; // Scheme for dummy data for tests.
    }

    /**
     * @Then I should not see an error message
     */
    public function iShouldNotSeeAnErrorMessage()
    {
        $authError = $this->authenticator->getAuthError();
        PHPUnit_Framework_Assert::assertEmpty(
            $authError,
            "Unexpected error: \n- " . $authError
        );
    }

    /**
     * @Then I should be allowed through
     */
    public function iShouldBeAllowedThrough()
    {
        PHPUnit_Framework_Assert::assertTrue(
            $this->authenticator->isAuthenticated()
        );
        $userInfo = $this->authenticator->getUserAttributes();
        PHPUnit_Framework_Assert::assertNotEmpty($userInfo);
    }

    /**
     * @When I try to log in using enough times to trigger the rate limit
     */
    public function iTryToLogInUsingEnoughTimesToTriggerTheRateLimit()
    {
        throw new PendingException();
    }

    /**
     * @Then that user account's failed login attempts should be at :number
     */
    public function thatUserAccountSFailedLoginAttemptsShouldBeAt($number)
    {
        $user = User::findByUsername($this->username);
        PHPUnit_Framework_Assert::assertEquals($number, $user->login_attempts);
    }

    /**
     * @Given the username :username has :number failed logins in the last hour
     */
    public function theUsernameHasFailedLoginsInTheLastHour($username, $number)
    {
        throw new PendingException();
    }

    /**
     * @Then I should see an error message with :text1 and :text2 in it
     */
    public function iShouldSeeAnErrorMessageWithAndInIt($text1, $text2)
    {
        $authError = $this->authenticator->getAuthError();
        PHPUnit_Framework_Assert::assertNotEmpty($authError);
        $authErrorString = (string)$authError;
        PHPUnit_Framework_Assert::assertContains($text1, $authErrorString);
        PHPUnit_Framework_Assert::assertContains($text2, $authErrorString);
    }

    /**
     * @Given the username :username had :number failed logins more than an hour ago
     */
    public function theUsernameHadFailedLoginsMoreThanAnHourAgo($username, $number)
    {
        throw new PendingException();
    }

    /**
     * @Given the username :username had :number failed logins in the last hour
     */
    public function theUsernameHadFailedLoginsInTheLastHour($username, $number)
    {
        throw new PendingException();
    }
    
    /**
     * @Then I should not have to pass a captcha test for that user
     */
    public function iShouldNotHaveToPassACaptchaTestForThatUser()
    {
        PHPUnit_Framework_Assert::assertNotEmpty($this->username);
        PHPUnit_Framework_Assert::assertFalse(
            User::isCaptchaRequiredFor($this->username)
        );
    }

    /**
     * @When I try to log in with an incorrect password enough times to require a captcha
     */
    public function iTryToLogInWithAnIncorrectPasswordEnoughTimesToRequireACaptcha()
    {
        // Arrange:
        $this->password = 'ThisIsWrong';
        $user = User::findByUsername($this->username);
        
        // Pre-assert:
        PHPUnit_Framework_Assert::assertNotNull($user, sprintf(
            'Unable to find a user with that username (%s).',
            var_export($this->username, true)
        ));
        PHPUnit_Framework_Assert::assertFalse(
            $user->isPasswordCorrect($this->password)
        );
        
        // Act:
        $this->loginXTimes(Authenticator::REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN);
    }

    /**
     * @Then I should have to pass a captcha test for that user
     */
    public function iShouldHaveToPassACaptchaTestForThatUser()
    {
        PHPUnit_Framework_Assert::assertNotEmpty($this->username);
        PHPUnit_Framework_Assert::assertTrue(
            User::isCaptchaRequiredFor($this->username)
        );
    }
}
