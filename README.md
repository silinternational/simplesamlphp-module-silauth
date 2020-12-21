# simplesamlphp-module-silauth
SimpleSAMLphp auth module implementing custom business logic

[![Codeship](https://img.shields.io/codeship/ab32f060-a43b-0134-d104-463a26eaa663.svg?style=flat-square)](https://app.codeship.com/projects/190461)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/silinternational/simplesamlphp-module-silauth.svg?style=flat-square)](https://scrutinizer-ci.com/g/silinternational/simplesamlphp-module-silauth/)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/silinternational/simplesamlphp-module-silauth/develop/LICENSE)

## Database Migrations
To create another database migration file, run the following (replacing 
`YourMigrationName` with whatever you want the migration to be named, using 
CamelCase):

    make migration NAME=YourMigrationName

## Rate Limiting
SilAuth will rate limit failed logins by username and by every untrusted IP
address from a login attempt. For each login attempt, if it has too many failed
logins within the last hour (aka. recent failed logins) for the given username
OR for any single untrusted IP address associated with the current request, it
will do one of the following:

- If there are fewer than `Authenticator::REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN`
  recent failures: process the request normally.
- If there are at least that many, but fewer than
  `Authenticator::BLOCK_AFTER_NTH_FAILED_LOGIN`: require the user to pass a
  captcha.
- If there are more than that: block that login attempt for `(recent failures
  above the limit)^2` seconds after the most recent failed login, with a
  minimum of 3 (so blocking for 9 seconds).
- Note: the blocking time is capped at an hour, so if no more failures occur,
  then the user will be unblocked in no more than an hour.

See `features/login.feature` for descriptions of how various situations are
handled. That file not only contains human-readable scenarios, but those are
also actual tests that are run to ensure those descriptions are correct.

### Example 1

- If `BLOCK_AFTER_NTH_FAILED_LOGIN` is 50, and
- if `REQUIRE_CAPTCHA_AFTER_NTH_FAILED_LOGIN` is 10, and
- if there have been 4 failed login attempts for `john_smith`, and
- there have been 10 failed login attempts from `11.22.33.44`, and
- there have been 3 failed login attempts from `192.168.1.2`, and
- someone tries to login as `john_smith` from `192.168.1.2` and their request
  goes through a proxy at `11.22.33.44`, then
- they will have to pass a captcha, but they will not yet be blocked.

### Example 2

- However, if all of the above is true, but
- there have now been 55 failed login attempts from `11.22.33.44`, then
- any request involving that IP address will be blocked for 25 seconds after
  the most recent of those failed logins.

## Excluding trusted IP addresses from IP address based rate limiting
Since this application enforces rate limits based on the number of recent 
failed login attempts by both username and IP address, and since it looks at 
both the REMOTE_ADDR and the X-Forwarded-For header for IP addresses, you will 
want to list any IP addresses that should NOT be rate limited (such as your 
load balancer) in the TRUSTED_IP_ADDRESSES environment variable (see 
`local.env.dist`).

## Status Check
To check the status of the website, you can access this URL:  
`https://(your domain name)/module.php/silauth/status.php`

## Debugging
To debug the project in your IDE (such as NetBeans), do the following:

1. Edit your `local.env` file, insert your IP address as the value for 
   `XDEBUG_REMOTE_HOST`.
2. Run `make start enabledebug`.
3. Set your IDE to use debugger port 9000 and a Session ID of netbeans-xdebug.
4. Click the "Debug Project" button in your IDE.

### Manual Testing ###
1. Run `make`
1. Go to <http://localhost/module.php/core/authenticate.php?as=silauth> in
your browser.
