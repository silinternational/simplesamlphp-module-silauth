Feature: User login
  In order to login
  As a user
  I need to be able to provide a username and password

  Rules:
  - Username and password are both required
  - The user is only allowed through if the username and password are both correct

  Scenario: Failing to provide a username
    Given I provide a password
    But I do not provide a username
    When I try to login
    Then I should see an error message
    And I should not be allowed through

  Scenario: Failing to provide a password
    Given I provide a username
    But I do not provide a password
    When I try to login
    Then I should see an error message
    And I should not be allowed through

  Scenario: Providing an incorrect username-password combination
    Given I provide a username
    And I provide a password
    But I provide the wrong password for that username
    Then I should see an error message
    And I should not be allowed through

  Scenario: Providing a correct username-password combination
    Given I provide a username
    And I provide the correct password for that username
    Then I should not see an error message
    And I should be allowed through
