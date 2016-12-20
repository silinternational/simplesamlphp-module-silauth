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
    Given the following user exists in the database:
        | username | password | login_attempts |
        | Bob      | MrTomato | 0              |
    And I provide a username of "Bob"
    But I provide a password of "MrsAsparagus"
    When I try to login
    Then I should see an error message
    And I should not be allowed through
    And that user account's failed login attempts should be at 1

  Scenario: Providing a correct username-password combination
    Given the following user exists in the database:
        | username | password |
        | Bob      | MrTomato |
    And I provide a username of "Bob"
    And I provide a password of "MrTomato"
    When I try to login
    Then I should not see an error message
    And I should be allowed through

  Scenario: Providing too many incorrect username-password combinations
    Given the following user exists in the database:
        | username | password | login_attempts |
        | Bob      | MrTomato | 0              |
    When I try to login using "Bob" and "MrsAsparagus" too many times
    Then I should see an error message with "wait" in it
    And that user account should be blocked for awhile
    And I should not be allowed through

  Scenario: Providing correct credentials after one failed login attempt
    Given the following user exists in the database:
        | username | password | login_attempts |
        | Bob      | MrTomato | 0              |
    And I provide a username of "Bob"
    But I provide a password of "MrsAsparagus"
    And I try to login
    Then I provide a username of "Bob"
    And I provide a password of "MrTomato"
    When I try to login
    Then I should not see an error message
    And I should be allowed through
    And that user account's failed login attempts should be at 0

  Scenario: Providing correct credentials to a locked account
    Given the following user exists in the database:
        | username | password | locked |
        | Bob      | MrTomato | Yes    |
    And I provide a username of "Bob"
    And I provide a password of "MrTomato"
    When I try to login
    Then I should see an error message with "username" and "password" in it
    And I should not be allowed through

  Scenario: Providing correct credentials to an inactive account
    Given the following user exists in the database:
        | username | password | active |
        | Bob      | MrTomato | No     |
    And I provide a username of "Bob"
    And I provide a password of "MrTomato"
    When I try to login
    Then I should see an error message with "username" and "password" in it
    And I should not be allowed through

  Scenario: Being told about how long to wait (due to rate limiting bad logins)
    Given the following user exists in the database:
        | username | password | login_attempts |
        | Bob      | MrTomato | 5              |
    And I provide a username of "Bob"
    And I provide a password of "MrTomato"
    When I try to login
    Then I should see an error message with "about 30 seconds" in it
    And that user account should still be blocked for awhile
    And I should not be allowed through

  Scenario: Providing credentials to an account in the ldap but not in the db
    Given there is no user with a username of "BOB_ADAMS" in the database
    But there is a "BOB_ADAMS" user in the ldap with a password of "bob_adams123"
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    When I try to login
    Then I should see an error message with "username" and "password" in it
    And I should not be allowed through

  Scenario: Incorrect password for an account with no password in the db, just in ldap
    Given the following user exists in the database:
        | username  | login_attempts |
        | BOB_ADAMS | 0              |
    And there is a "BOB_ADAMS" user in the ldap
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "ThisIsWrong"
    When I try to login
    Then I should see an error message with "username" and "password" in it
    And I should not be allowed through
    And that user account's failed login attempts should be at 1

  Scenario: Correct password for an account with no password in the db, just in ldap
    Given the following user exists in the database:
        | username | login_attempts |
        | ROB_HOLT | 0              |
    And there is a "ROB_HOLT" user in the ldap with a password of "rob_holt123"
    And I provide a username of "ROB_HOLT"
    And I provide a password of "rob_holt123"
    When I try to login
    Then I should not see an error message
    And I should be allowed through
    And that user account should have a password in the database
    And that user account's failed login attempts should be at 0
