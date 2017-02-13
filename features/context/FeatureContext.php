<?php
namespace Sil\SilAuth\features\context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use PHPUnit_Framework_Assert;
use Psr\Log\LoggerInterface;
use Sil\PhpEnv\Env;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\auth\AuthError;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\ldap\Ldap;
use Sil\SilAuth\log\Psr3ConsoleLogger;
use Sil\SilAuth\models\User;
use Sil\SilAuth\time\UtcTime;
use yii\helpers\ArrayHelper;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /** @var Authenticator|null */
    private $authenticator = null;

    /** @var Ldap */
    private $ldap;

    /** @var LoggerInterface */
    private $logger;
    
    /** @var string|null */
    private $username = null;
    
    /** @var string|null */
    private $password = null;
    
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->initializeDependencies();
        $this->ldap = new Ldap(ConfigManager::getSspConfigFor('ldap'));
        $this->logger = new Psr3ConsoleLogger();
    }
    
    protected function initializeDependencies()
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
    }
    
    protected function loginXTimes($numberOfTimes)
    {
        for ($i = 0; $i < $numberOfTimes; $i++) {
            $this->authenticator = new Authenticator(
                $this->username,
                $this->password,
                $this->ldap,
                $this->logger
            );
        }   
    }

    /**
     * @Given I provide a password
     */
    public function iProvideAPassword()
    {
        $this->password = 'a password';
    }

    /**
     * @Given I do not provide a username
     */
    public function iDoNotProvideAUsername()
    {
        $this->username = '';
    }

    /**
     * @When I try to log in
     */
    public function iTryToLogIn()
    {
        $this->authenticator = new Authenticator(
            $this->username,
            $this->password,
            $this->ldap,
            $this->logger
        );
    }

    /**
     * @Then I should see an error message
     */
    public function iShouldSeeAnErrorMessage()
    {
        PHPUnit_Framework_Assert::assertNotEmpty(
            $this->authenticator->getAuthError()
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
     * @Given I provide a username
     */
    public function iProvideAUsername()
    {
        $this->username = 'a username';
    }

    /**
     * @Given I do not provide a password
     */
    public function iDoNotProvideAPassword()
    {
        $this->password = '';
    }

    /**
     * @Given I provide the correct password for that username
     */
    public function iProvideTheCorrectPasswordForThatUsername()
    {
        $this->password = 'the right password';
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
     * @Given I provide the wrong password for that username
     */
    public function iProvideTheWrongPasswordForThatUsername()
    {
        $this->password = 'the wrong password';
    }

    /**
     * @Given the following user(s) exist(s) in the database:
     */
    public function theFollowingUsersExistInTheDatabase(TableNode $table)
    {
        foreach ($table as $row) {
            $existingUser = User::findByUsername($row['username']);
            if ($existingUser !== null) {
                PHPUnit_Framework_Assert::assertTrue(
                    ($existingUser->delete() !== false),
                    'Failed to delete existing user record before test.'
                );
            }
            
            $user = new User();
            if (array_key_exists('password', $row)) {
                $user->setPassword($row['password']);
                unset($row['password']);
            }
            
            $defaults = [
                'email' => strtolower($row['username'] . '@example.com'),
                'employee_id' => uniqid(),
                'first_name' => $row['username'],
                'last_name' => 'User',
            ];
            $user->setAttributes(ArrayHelper::merge($defaults, $row), false);
            
            PHPUnit_Framework_Assert::assertTrue($user->save(), sprintf(
                'Failed to set up user for test: %s',
                print_r($user->getErrors(), true)
            ));
        }
    }

    /**
     * @Given I provide a username of :username
     */
    public function iProvideAUsernameOf($username)
    {
        $this->username = $username;
    }

    /**
     * @Given I provide a password of :password
     */
    public function iProvideAPasswordOf($password)
    {
        $this->password = $password;
    }

    /**
     * @When I try to log in using :username and :password too many times
     */
    public function iTryToLogInUsingAndTooManyTimes($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        
        $blockAfterNthFailedLogin = Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN;
        
        PHPUnit_Framework_Assert::assertGreaterThan(
            0,
            $blockAfterNthFailedLogin,
            'The number of failed logins to allow before blocking an account must be positive.'
        );
        
        // Try to log in one too many times (so that we'll see the "wait" message).
        $this->loginXTimes($blockAfterNthFailedLogin + 1);
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
     * @Then that user account's failed login attempts should be at :number
     */
    public function thatUserAccountSFailedLoginAttemptsShouldBeAt($number)
    {
        $user = User::findByUsername($this->username);
        PHPUnit_Framework_Assert::assertEquals($number, $user->login_attempts);
    }

    /**
     * @Given there is no user with a username of :username in the database
     */
    public function thereIsNoUserWithAUsernameOfInTheDatabase($username)
    {
        $user = User::findByUsername($username);
        if ($user !== null) {
            PHPUnit_Framework_Assert::assertTrue(
                ($user->delete() !== false)
            );
        }
    }

    /**
     * @Given there is no user with a username of :username in the ldap
     */
    public function thereIsNoUserWithAUsernameOfInTheLdap($username)
    {
        PHPUnit_Framework_Assert::assertFalse($this->ldap->userExists($username));
    }

    /**
     * @Given there is a(n) :username user in the ldap with a password of :password
     */
    public function thereIsAnUserInTheLdapWithAPasswordOf($username, $password)
    {
        $isCorrect = $this->ldap->isPasswordCorrectForUser($username, $password);
        PHPUnit_Framework_Assert::assertTrue($isCorrect);
    }

    /**
     * @Then there should now be a(n) :username user in the database with a password of :password
     */
    public function thereShouldNowBeAnUserInTheDatabaseWithAPasswordOf($username, $password)
    {
        $user = User::findByUsername($username);
        PHPUnit_Framework_Assert::assertNotNull($user);
        $isCorrect = $user->isPasswordCorrect($password);
        PHPUnit_Framework_Assert::assertTrue($isCorrect);
    }

    /**
     * @Given there is a(n) :username user in the ldap
     */
    public function thereIsAnUserInTheLdap($username)
    {
        $userExists = $this->ldap->userExists($username);
        PHPUnit_Framework_Assert::assertTrue($userExists);
    }

    /**
     * @Then there should now be a(n) :username user in the database
     */
    public function thereShouldNowBeAnUserInTheDatabase($username)
    {
        $user = User::findByUsername($username);
        PHPUnit_Framework_Assert::assertNotNull($user);
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
     * @Then that user account should have a password in the database
     */
    public function thatUserAccountShouldHaveAPasswordInTheDatabase()
    {
        $user = User::findByUsername($this->username);
        PHPUnit_Framework_Assert::assertNotNull($user);
        PHPUnit_Framework_Assert::assertTrue($user->hasPasswordInDatabase());
    }

    /**
     * @Given that user account's block-until time is in the past
     */
    public function thatUserAccountSBlockUntilTimeIsInThePast()
    {
        $user = User::findByUsername($this->username);
        PHPUnit_Framework_Assert::assertNotNull($user, 'Could not find that user.');
        $user->block_until_utc = UtcTime::format('-1 second');
        PHPUnit_Framework_Assert::assertTrue(
            $user->save(true, ['block_until_utc']),
            'Failed to set the block-until time to in the past.'
        );
        PHPUnit_Framework_Assert::assertSame(0, $user->getSecondsUntilUnblocked());
    }

    /**
     * @Then I should have access to some information about that user
     */
    public function iShouldHaveAccessToSomeInformationAboutThatUser()
    {
        $userInfo = $this->authenticator->getUserAttributes();
        PHPUnit_Framework_Assert::assertNotEmpty($userInfo);
    }

    /**
     * @Then I should not have access to any information about that user
     */
    public function iShouldNotHaveAccessToAnyInformationAboutThatUser()
    {
        try {
            $this->authenticator->getUserAttributes();
            PHPUnit_Framework_Assert::fail();
        } catch (\Exception $e) {
            PHPUnit_Framework_Assert::assertNotEmpty($e->getMessage());
        }
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
     * @Then I should see a generic invalid-login error message
     */
    public function iShouldSeeAGenericInvalidLoginErrorMessage()
    {
        $authError = $this->authenticator->getAuthError();
        PHPUnit_Framework_Assert::assertNotEmpty($authError);
        PHPUnit_Framework_Assert::assertContains('invalid_login', (string)$authError);
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

    /**
     * @Given that user account does not have a password in the database
     */
    public function thatUserAccountDoesNotHaveAPasswordInTheDatabase()
    {
        $user = User::findByUsername($this->username);
        PHPUnit_Framework_Assert::assertNotNull($user);
        PHPUnit_Framework_Assert::assertFalse($user->hasPasswordInDatabase());
    }

    /**
     * @Given the LDAP is offline
     */
    public function theLdapIsOffline()
    {
        $ldapConfig = ConfigManager::getSspConfigFor('ldap');
        $ldapConfig['domain_controllers'] = ['wrongdomainname'];
        $this->ldap = new Ldap($ldapConfig);
    }

    /**
     * @Then I should see an error message about needing to set my password
     */
    public function iShouldSeeAnErrorMessageAboutNeedingToSetMyPassword()
    {
        $authError = $this->authenticator->getAuthError();
        PHPUnit_Framework_Assert::assertNotEmpty($authError);
        PHPUnit_Framework_Assert::assertContains(
            AuthError::CODE_NEED_TO_SET_ACCT_PASSWORD,
            (string)$authError
        );
    }
}
