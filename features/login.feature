Feature: User login
  In order to log in
  As a user
  I need to be able to provide a username and password

  Rules:
  - Username and password are both required.
  - The user is only allowed through if all of the necessary checks pass.

  Scenario: Not providing a CSRF token
    Given I provide a username
      And I provide a password
      But I do not provide a CSRF token
    When I try to log in
    Then I should not be allowed through

  Scenario: Providing an incorrect CSRF token
    Given I provide a username
      And I provide a password
      But I provide an incorrect CSRF token
    When I try to log in
    Then I should not be allowed through

  Scenario: Failing to provide a username
    Given I provide a password
      But I do not provide a username
    When I try to log in
    Then I should see an error message with "username" in it
      And I should not be allowed through

  Scenario: Failing to provide a password
    Given I provide a username
      But I do not provide a password
    When I try to log in
    Then I should see an error message with "password" in it
      And I should not be allowed through

  Scenario: Failing to pass the captcha when it's required
    Given I provide a username
      And I provide a password
      And a captcha is required for that username
      But I fail the captcha
    When I try to log in
    Then I should see a generic invalid-login error message
      And I should not be allowed through

  Scenario: Trying to log in with a rate-limited username
    Given the username "BOB_ADAMS" has triggered the rate limit
      And I provide a username of "bob_adams"
      And I provide a password
    When I try to log in
    Then I should see an error message telling me to wait
      And that user account should be blocked for awhile
      And I should not be allowed through

  Scenario: Providing unacceptable credentials
    Given I provide a username of "bob_adams"
      But I provide an incorrect password
    When I try to log in
    Then I should see a generic invalid-login error message
      And I should not be allowed through

  Scenario: Providing unacceptable credentials that trigger a rate limit
    Given I provide a username of "bob_adams"
      And that username will be rate limited after one more failed attempt
      And I provide an incorrect password
    When I try to log in
    Then I should see an error message telling me to wait
      And that user account should be blocked for awhile
      And I should not be allowed through

  Scenario: Providing a correct username-password combination
    Given I provide a username of "bob_adams"
      And I provide the correct password for that username
    When I try to log in
    Then I should not see an error message
      And I should be allowed through

  Scenario: Providing a correct password but using the wrong upper/lowercase username
    Given I provide a username of "boB_aDaMs"
      And I provide the correct password for that username
    When I try to log in
    Then I should not see an error message
      And I should be allowed through

  Scenario: Providing too many incorrect username-password combinations
    Given I provide a username of "bob_adams"
      And I provide an incorrect password
    When I try to log in using enough times to trigger the rate limit
    Then I should see an error message telling me to wait
      And that user account should be blocked for awhile
      And I should not be allowed through

  Scenario: Providing correct credentials after one failed login attempt
    Given I provide a username of "bob_adams"
      And I provide an incorrect password
      And I try to log in
      But I then provide the correct password for that username
    When I try to log in
    Then I should not see an error message
      And I should be allowed through
      And that user account's failed login attempts should be at 0

  Scenario: Being told about how long to wait (due to rate limiting bad logins)
    Given the username "bob_adams" has 5 failed logins in the last hour
      And I provide a username of "bob_adams"
      And I provide the correct password for that username
    When I try to log in
    Then I should see an error message with "30" and "seconds" in it
      And that user account should still be blocked for awhile
      And I should not be allowed through

  Scenario: Logging in after a rate limit has expired
    Given the username "bob_adams" had 5 failed logins more than an hour ago
      And I provide a username of "BOB_ADAMS"
      And I provide the correct password for that username
    When I try to log in
    Then I should not see an error message
      And I should be allowed through
      And that user account's failed login attempts should be at 0

  Scenario: No failed logins (and thus no captcha requirement)
    Given the username "bob_adams" had 0 failed logins in the last hour
    When I provide a username of "bob_adams"
    Then I should not have to pass a captcha test for that user

  Scenario: Enough failed logins to require a captcha
    Given I provide a username of "bob_adams"
    When I try to log in with an incorrect password enough times to require a captcha
    Then I should have to pass a captcha test for that user
