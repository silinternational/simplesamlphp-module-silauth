Feature: User login
  In order to log in
  As a user
  I need to be able to provide a username and password

  Rules:
  - Username and password are both required
  - The user is only allowed through if the username and password are both correct

  Scenario: Failing to provide a username
    Given I provide a password
    But I do not provide a username
    When I try to log in
    Then I should see an error message
    And I should not have access to any information about that user
    And I should not be allowed through

  Scenario: Failing to provide a password
    Given I provide a username
    But I do not provide a password
    When I try to log in
    Then I should see an error message
    And I should not have access to any information about that user
    And I should not be allowed through

  Scenario: Providing an incorrect username-password combination
    Given the following user exists in the database:
        | username  | password     | login_attempts |
        | BOB_ADAMS | bob_adams123 | 0              |
    And I provide a username of "BOB_ADAMS"
    But I provide a password of "something_else"
    When I try to log in
    Then I should see an error message
    And I should not have access to any information about that user
    And I should not be allowed through
    And that user account's failed login attempts should be at 1

  Scenario: Providing a correct username-password combination
    Given the following user exists in the database:
        | username  | password     |
        | BOB_ADAMS | bob_adams123 |
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    When I try to log in
    Then I should not see an error message
    And I should have access to some information about that user
    And I should be allowed through

  Scenario: Providing a correct password but using the wrong upper/lowercase username
    Given the following user exists in the database:
        | username  | password     |
        | BOB_ADAMS | bob_adams123 |
    And I provide a username of "bob_adams"
    And I provide a password of "bob_adams123"
    When I try to log in
    Then I should not see an error message
    And I should have access to some information about that user
    And I should be allowed through

  Scenario: Providing too many incorrect username-password combinations
    Given the following user exists in the database:
        | username  | password     | login_attempts |
        | BOB_ADAMS | bob_adams123 | 0              |
    When I try to log in using "BOB_ADAMS" and "aWrongPassword" too many times
    Then I should see an error message telling me to wait
    And that user account should be blocked for awhile
    And I should not have access to any information about that user
    And I should not be allowed through

  Scenario: Providing correct credentials after one failed login attempt
    Given the following user exists in the database:
        | username  | password     | login_attempts |
        | BOB_ADAMS | bob_adams123 | 0              |
    And I provide a username of "BOB_ADAMS"
    But I provide a password of "bob_adams789"
    And I try to log in
    Then I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    When I try to log in
    Then I should not see an error message
    And I should have access to some information about that user
    And I should be allowed through
    And that user account's failed login attempts should be at 0

  Scenario: Providing correct credentials to a locked account
    Given the following user exists in the database:
        | username  | password     | locked |
        | BOB_ADAMS | bob_adams123 | Yes    |
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    When I try to log in
    Then I should see a generic invalid-login error message
    And I should not have access to any information about that user
    And I should not be allowed through

  Scenario: Providing correct credentials to an inactive account
    Given the following user exists in the database:
        | username  | password     | active |
        | BOB_ADAMS | bob_adams123 | No     |
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    When I try to log in
    Then I should see a generic invalid-login error message
    And I should not have access to any information about that user
    And I should not be allowed through

  Scenario: Being told about how long to wait (due to rate limiting bad logins)
    Given the following user exists in the database:
        | username  | password     | login_attempts |
        | BOB_ADAMS | bob_adams123 | 5              |
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    When I try to log in
    Then I should see an error message with "30" and "seconds" in it
    And that user account should still be blocked for awhile
    And I should not have access to any information about that user
    And I should not be allowed through

  Scenario: Logging in after a rate limit has expired
    Given the following user exists in the database:
        | username  | password     | login_attempts |
        | BOB_ADAMS | bob_adams123 | 5              |
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    And that user account's block-until time is in the past
    When I try to log in
    Then I should not see an error message
    And I should have access to some information about that user
    And I should be allowed through
    And that user account's failed login attempts should be at 0

  Scenario: Providing credentials to an account in the ldap but not in the db
    Given there is no user with a username of "BOB_ADAMS" in the database
    But there is a "BOB_ADAMS" user in the ldap with a password of "bob_adams123"
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "bob_adams123"
    When I try to log in
    Then I should see a generic invalid-login error message
    And I should not have access to any information about that user
    And I should not be allowed through

  Scenario: Incorrect password for an account with no password in the db, just in ldap
    Given the following user exists in the database:
        | username  | login_attempts |
        | BOB_ADAMS | 0              |
    And there is a "BOB_ADAMS" user in the ldap
    And I provide a username of "BOB_ADAMS"
    And I provide a password of "ThisIsWrong"
    When I try to log in
    Then I should see a generic invalid-login error message
    And I should not have access to any information about that user
    And I should not be allowed through
    And that user account's failed login attempts should be at 1

  Scenario: Correct password for an account with no password in the db, just in ldap
    Given the following user exists in the database:
        | username | login_attempts |
        | ROB_HOLT | 0              |
    And there is a "ROB_HOLT" user in the ldap with a password of "rob_holt123"
    And I provide a username of "ROB_HOLT"
    And I provide a password of "rob_holt123"
    When I try to log in
    Then I should not see an error message
    And I should be allowed through
    And I should have access to some information about that user
    And that user account should have a password in the database
    And that user account's failed login attempts should be at 0

  Scenario: Failing to provide any captcha value when it is required
    Given the following user exists in the database:
        | username  | password     | login_attempts |
        | BOB_ADAMS | bob_adams123 | 1              |
    And I provide a username of "BOB_ADAMS"
    And a captcha is required for that user
    And I provide a password of "bob_adams123"
    But I do not provide a captcha value
    When I try to login
    Then I should see an error message
    And I should not have access to any information about that user
    And I should not be allowed through
    And that user account's failed login attempts should be at 2
