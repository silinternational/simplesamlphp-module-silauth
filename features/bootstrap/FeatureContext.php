<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Sil\SilAuth\auth\Authenticator;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\ldap\Ldap;
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

    /** @var Ldap|null */
    private $ldap = null;
    
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
    }
    
    protected function initializeDependencies()
    {
        require_once __DIR__ . '/../../src/bootstrap-yii2.php';
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
     * @When I try to login
     */
    public function iTryToLogin()
    {
        $this->authenticator = new Authenticator(
            $this->username,
            $this->password,
            $this->ldap
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
     * @When I try to login using :username and :password too many times
     */
    public function iTryToLoginUsingAndTooManyTimes($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        
        $blockAfterNthFailedLogin = User::BLOCK_AFTER_NTH_FAILED_LOGIN;
        
        PHPUnit_Framework_Assert::assertGreaterThan(
            0,
            $blockAfterNthFailedLogin,
            'The number of failed logins to allow before blocking an account must be positive.'
        );
        
        // Try to log in one too many times (so that we'll see the "wait" message).
        for ($i = 0; $i < ($blockAfterNthFailedLogin + 1) ; $i++) {
            $this->authenticator = new Authenticator(
                $this->username,
                $this->password,
                $this->ldap
            );
        }
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
}
