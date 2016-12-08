<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use SilAuth\db\DatabaseConfigurer;
use SilAuth\models\User;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private $user = null;
    private $username = null;
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
        if ($this->username === null) {
            throw new Exception('Please provide a username.');
        }
        $this->user = User::where('username', $this->username)->first();
    }

    /**
     * @Then I should see an error message
     */
    public function iShouldSeeAnErrorMessage()
    {
        throw new PendingException();
    }

    /**
     * @Then I should not be allowed through
     */
    public function iShouldNotBeAllowedThrough()
    {
        throw new PendingException();
    }

    /**
     * @Given I provide a username
     */
    public function iProvideAUsername()
    {
        throw new PendingException();
    }

    /**
     * @Given I do not provide a password
     */
    public function iDoNotProvideAPassword()
    {
        throw new PendingException();
    }

    /**
     * @Given I provide the correct password for that username
     */
    public function iProvideTheCorrectPasswordForThatUsername()
    {
        throw new PendingException();
    }

    /**
     * @Then I should not see an error message
     */
    public function iShouldNotSeeAnErrorMessage()
    {
        throw new PendingException();
    }

    /**
     * @Then I should be allowed through
     */
    public function iShouldBeAllowedThrough()
    {
        throw new PendingException();
    }

    /**
     * @Given I provide the wrong password for that username
     */
    public function iProvideTheWrongPasswordForThatUsername()
    {
        throw new PendingException();
    }
}
