<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Sil\SilAuth\Authenticator;
use Sil\SilAuth\db\DatabaseConfigurer;
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
        DatabaseConfigurer::init();
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
            $user = User::firstOrNew(['username' => $row['username']]);
            $user->password_hash = password_hash($row['password'], PASSWORD_DEFAULT);
            $user->saveOrFail();
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
}
