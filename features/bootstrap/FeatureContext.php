<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Sil\SilAuth\Authenticator;
use Sil\SilAuth\ldap\Ldap;
use Sil\SilAuth\models\User;
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
        $this->ldap = new Ldap();
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
            $this->password
        );
    }

    /**
     * @Then I should see an error message
     */
    public function iShouldSeeAnErrorMessage()
    {
        PHPUnit_Framework_Assert::assertNotEmpty(
            $this->authenticator->getErrors()
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
        $errors = $this->authenticator->getErrors();
        PHPUnit_Framework_Assert::assertEmpty(
            $errors,
            "Unexpected error(s): \n- " . implode("\n- ", $errors)
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
            $user->password_hash = password_hash($row['password'], PASSWORD_DEFAULT);
            unset($row['password']);
            
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
                $this->password
            );
        }
    }

    /**
     * @Then I should see an error message with :text in it
     */
    public function iShouldSeeAnErrorMessageWithInIt($text)
    {
        PHPUnit_Framework_Assert::assertContains(
            $text,
            implode("\n", $this->authenticator->getErrors())
        );
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
            PHPUnit_Framework_Assert::assertNotFalse($user->delete());
        }
    }

    /**
     * @Given there is no user with a username of :username in the ldap
     */
    public function thereIsNoUserWithAUsernameOfInTheLdap($username)
    {
        if ($this->ldap->userExists($username)) {
            PHPUnit_Framework_Assert::assertTrue($this->ldap->deleteUser($username));
        }
    }
}
