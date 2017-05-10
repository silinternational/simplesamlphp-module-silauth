# simplesamlphp-module-silauth
SimpleSAMLphp auth module implementing custom business logic and password 
migration from LDAP to DB.

[![Codeship](https://img.shields.io/codeship/ab32f060-a43b-0134-d104-463a26eaa663.svg?style=flat-square)](https://app.codeship.com/projects/190461)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/silinternational/simplesamlphp-module-silauth.svg?style=flat-square)](https://scrutinizer-ci.com/g/silinternational/simplesamlphp-module-silauth/)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/silinternational/simplesamlphp-module-silauth/develop/LICENSE)

## Database Migrations
To create another database migration file, run the following (replacing 
`YourMigrationName` with whatever you want the migration to be named, using 
CamelCase):

    make migration NAME=YourMigrationName

## IP address based rate limiting
Since this application enforces rate limits based on the number of recent 
failed login attempts by both username and IP address, and since it looks at 
both the REMOTE_ADDR and the X-Forwarded-For header for IP addresses, you will 
want to list any IP addresses that should NOT be rate limited (such as your 
load balancer) in the TRUSTED_IP_ADDRESSES environment variable (see 
`local.env.dist`).

## Debugging
To debug the project in your IDE (such as NetBeans), do the following:

1. Edit your `local.env` file, insert your IP address as the value for 
   `XDEBUG_REMOTE_HOST`.
2. Run `make start enabledebug`.
3. Set your IDE to use debugger port 9000 and a Session ID of netbeans-xdebug.
4. Click the "Debug Project" button in your IDE.
