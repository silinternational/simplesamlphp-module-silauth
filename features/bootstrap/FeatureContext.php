<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Sil\SilAuth\Authenticator;
use Sil\SilAuth\models\User;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /** @var Authenticator|null */
    private $authenticator = null;
    
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
        PHPUnit_Framework_Assert::assertEmpty(
            $this->authenticator->getErrors()
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
     * @Given the following users exist in the database:
     */
    public function theFollowingUsersExistInTheDatabase(TableNode $table)
    {
        foreach ($table as $row) {
            $user = User::findOne(['username' => $row['username']]);
            if ($user === null) {
                $user = new User();
                $user->username = $row['username'];
            }
            $user->setAttributes([
                'password_hash' => password_hash($row['password'], PASSWORD_DEFAULT),
                'email' => $row['email'] ?? ($row['username'] . '@example.com'),
                'employee_id' => $row['employee_id'] ?? uniqid(),
                'first_name' => $row['first_name'] ?? $row['username'],
                'last_name' => $row['last_name'] ?? 'User',
            ], false);
            if (array_key_exists('login_attempts', $row)) {
                $user->login_attempts = $row['login_attempts'];
            }
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
        
        $maxFailedLoginsBeforeBlock = User::MAX_FAILED_LOGINS_BEFORE_BLOCK;
        
        PHPUnit_Framework_Assert::assertGreaterThan(
            0,
            $maxFailedLoginsBeforeBlock,
            'The number of failed logins to allow before blocking an account must be positive.'
        );
        
        for ($i = 0; $i <= ($maxFailedLoginsBeforeBlock + 1) ; $i++) {
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
     * @Then that user account should be blocked for awhile
     */
    public function thatUserAccountShouldBeBlockedForAwhile()
    {
        $user = User::findByUsername($this->username);
        PHPUnit_Framework_Assert::assertTrue(
            $user->isBlockedByRateLimit()
        );
    }
}
